<?php


use Symfony\Component\Yaml\Yaml;

include __DIR__ . '/../vendor/autoload.php';
$config = Yaml::parse(file_get_contents(__DIR__ . "/../app/config/app.yml"));
$timezone = $config['setting']['timezone'];
ini_set("date.timezone", $timezone);
ini_set('memory_limit', -1);
ini_set('max_execution_time', '0');

class Stats
{

    private $_RUN_TIME_START;
    private $_RUN_TIME_END;
    private $cField = 'amount_usd';
    private $date;
    private $month;
    private $cfg;
    private $retention_days = [0, 1, 2, 3, 4, 5, 6, 13, 29, 59];
    private $_redis;


    public function __construct()
    {
        $this->_RUN_TIME_START = time();
    }


    public function __destruct()
    {
    }


    public function run()
    {
        $this->readCfg();

        $this->getOptions();

        $this->logger('START');

        // 基本
        $this->logger('analysis report');
        $report = $this->statsReport();
        $this->saveToBI($report, 'report');


        // 留存
        $this->logger('analysis retention');
        $retention = $this->statsRetention();
        $this->saveToBI($retention, 'retention');


        // 付费
        $this->logger('analysis payment');
        $payment = $this->statsPayment();
        $this->saveToBI($payment, 'payment');

        // 留存账户
        $this->logger('analysis retention user');
        $retention = $this->statsRetentionUser();
        $this->saveToBI($retention, 'retention_user');

        $this->takeUp();
    }


    /**
     * 数据保存到BI
     * @param array $data
     * @param string $type
     * @return bool
     */
    public function saveToBI($data = [], $type = '')
    {
        if (!$data) {
            return false;
        }

        // get table
        if ($type == 'report') {
            $table = 'report_day';
            // 删除旧数据
            $sqlDelete = "DELETE FROM $table WHERE `date`='{$this->date}'";
            $this->db('bi')->exec($sqlDelete);
        }

        if ($type == 'payment') {
            $table = 'payment_day';
            // 删除旧数据
            $sqlDelete = "DELETE FROM $table WHERE `date`='{$this->date}'";
            $this->db('bi')->exec($sqlDelete);
        }

        if ($type == 'retention') {
            $table = 'retention';
        }

        if ($type == 'retention_user') {
            $table = 'retention_user';
        }


        // 插入新数据
        $sql = '';
        foreach ($data as $key => $value) {
            // 仅过滤空值
            $value = array_filter($value, function ($v) {
                if ($v === '') {
                    return false;
                }
                return true;
            });


            // 日期
            if ($type == 'retention' || $type == 'retention_user') {
                // 删除旧数据
                $sqlDelete = "DELETE FROM $table WHERE `date`='{$value['date']}' AND days='{$value['days']}'";
                $this->db('bi')->exec($sqlDelete);
            }
            else {
                $value['date'] = $this->date;
            }


            $k = array_keys($value);
            $v = array_values($value);

            $k = "`" . implode("`,`", $k) . "`";
            $v = "'" . implode("','", $v) . "'";
            $sql .= "INSERT INTO `{$table}` ($k) VALUES ($v);";
        }
        $this->db('bi')->exec($sql);
    }


    /**
     * 区域分析; 按设备分析
     */
    public function statsArea()
    {
    }


    /**
     * 用户价值LTV
     * 新增设备价值
     */
    public function statsLTV()
    {
    }


    /**
     * 新增设备留存
     * 需要执行基本概况后执行
     * KEY格式: 1021012:20170601:uuid:new:baidu:ios
     */
    public function statsRetention()
    {
        $k_now = ':' . str_replace('-', '', $this->date) . ':uuid:act:';
        $result = [];
        foreach ($this->retention_days as $day) {
            $stats_day = date('Y-m-d', strtotime("{$this->date} - {$day} day"));
            $stats_day_trim = str_replace('-', '', $stats_day);
            $k_new = ':' . $stats_day_trim . ':uuid:new:';
            $k_act = ':' . $stats_day_trim . ':uuid:act:';


            // 强制删除key
            //$this->redis_del('*' . $k_new . '*');
            //$this->redis_del('*' . $k_act . '*');


            // 导入新增
            $keys = $this->redis()->keys('*' . $k_new . '*');
            if (!$keys) {
                $device_list = $this->_getNewDevice($stats_day);
                if ($device_list) {
                    foreach ($device_list as $device) {
                        $app_id = $device['app_id'];
                        $channel = $device['channel'];
                        $os = $device['device'];
                        $this->redis()->sAdd($app_id . $k_new . $channel . ':' . $os, $device['uuid']);
                    }
                }
            }

            // 导入活跃
            $keys = $this->redis()->keys('*' . $k_act . '*');
            if (!$keys) {
                $device_list = $this->_getActiveDevice($stats_day);
                if ($device_list) {
                    foreach ($device_list as $device) {
                        $app_id = $device['app_id'];
                        $channel = $device['channel'];
                        $os = $device['device'];
                        $this->redis()->sAdd($app_id . $k_act . $channel . ':' . $os, $device['uuid']);
                    }
                }
            }


            // 计算交集, 新增是活跃的子集
            $all_keys = $this->redis()->keys('*' . $k_now . '*');
            foreach ($all_keys as $key) {
                $c = explode(':', $key);
                $app_id = $c['0'];
                $channel = $c['4'];
                $os = $c['5'];

                $result[] = [
                    'app_id'       => $app_id,
                    'channel'      => $channel,
                    'device'       => $os,
                    'date'         => $stats_day,
                    'days'         => $day,
                    'count_device' => count($this->redis()->sInter(
                        $app_id . $k_now . $channel . ':' . $os,
                        $app_id . $k_new . $channel . ':' . $os)
                    )
                ];
            }

        }


        return $result;
    }

    /**
     * 新增用户留存
     * 需要执行基本概况后执行
     * KEY格式: 1021012:20170601:uuid:new:baidu:ios
     */
    public function statsRetentionUser()
    {
        $k_now = ':' . str_replace('-', '', $this->date) . ':user_id:act:';
        $result = [];
        foreach ($this->retention_days as $day) {
            $stats_day = date('Y-m-d', strtotime("{$this->date} - {$day} day"));
            $stats_day_trim = str_replace('-', '', $stats_day);
            $k_new = ':' . $stats_day_trim . ':user_id:new:';
            $k_act = ':' . $stats_day_trim . ':user_id:act:';


            // 强制删除key
            //$this->redis_del('*' . $k_new . '*');
            //$this->redis_del('*' . $k_act . '*');


            // 导入新增
            $keys = $this->redis()->keys('*' . $k_new . '*');
            if (!$keys) {
                $device_list = $this->_getNewUser($stats_day);
                if ($device_list) {
                    foreach ($device_list as $device) {
                        $app_id = $device['app_id'];
                        $channel = $device['channel'];
                        $os = $device['device'];
                        $this->redis()->sAdd($app_id . $k_new . $channel . ':' . $os, $device['user_id']);
                    }
                }
            }

            // 导入活跃
            $keys = $this->redis()->keys('*' . $k_act . '*');
            if (!$keys) {
                $device_list = $this->_getActiveUser($stats_day);
                if ($device_list) {
                    foreach ($device_list as $device) {
                        $app_id = $device['app_id'];
                        $channel = $device['channel'];
                        $os = $device['device'];
                        $this->redis()->sAdd($app_id . $k_act . $channel . ':' . $os, $device['user_id']);
                    }
                }
            }


            // 计算交集, 新增是活跃的子集
            $all_keys = $this->redis()->keys('*' . $k_now . '*');
            foreach ($all_keys as $key) {
                $c = explode(':', $key);
                $app_id = $c['0'];
                $channel = $c['4'];
                $os = $c['5'];

                $result[] = [
                    'app_id'       => $app_id,
                    'channel'      => $channel,
                    'device'       => $os,
                    'date'         => $stats_day,
                    'days'         => $day,
                    'count_device' => count($this->redis()->sInter(
                        $app_id . $k_now . $channel . ':' . $os,
                        $app_id . $k_new . $channel . ':' . $os)
                    )
                ];
            }

        }


        return $result;
    }

    /**
     * 付费分析
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function statsPayment()
    {
        // 计算 - 付费账号数, 付费设备数, 付费次数, 付费总额(美金)
        $sql = <<<END
SELECT
    t.app_id, channel, device,
    COUNT(DISTINCT t.user_id) count_account,
    COUNT(DISTINCT t.uuid) count_device,
    COUNT(1) times,
    IFNULL(SUM({$this->cField}), 0.00) amount
FROM
    `transactions` t
WHERE
    t.status = 'complete'
    AND complete_time >= '{$this->date} 00:00:00'
    AND complete_time <= '{$this->date} 23:59:59'
GROUP BY
    t.app_id,channel, device
END;
        $query = $this->db('trade')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result_payment = $query->fetchAll();

        // 计算 - 新增付费账号
        $sql = <<<END
SELECT t.app_id, t.channel, t.device, COUNT(1) `new_account`
FROM
  (
    SELECT app_id, user_id, channel, device, MIN(complete_time) min_time
    FROM `transactions`
    WHERE `status` = 'complete'
    GROUP BY app_id, user_id, channel, device
) t
WHERE t.min_time >= '{$this->date} 00:00:00' AND t.min_time <= '{$this->date} 23:59:59'
GROUP BY t.app_id, t.channel, t.device
END;
        $query = $this->db('trade')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $payment_new_account = $query->fetchAll();


        // 计算 - 新增付费设备
        $sql = <<<END
SELECT t.app_id, t.channel, t.device, COUNT(1) `new_device`
FROM
  (
    SELECT app_id, uuid, channel, device, MIN(complete_time) min_time
    FROM `transactions`
    WHERE `status` = 'complete'
    GROUP BY app_id, uuid, channel, device
) t
WHERE t.min_time >= '{$this->date} 00:00:00' AND t.min_time <= '{$this->date} 23:59:59'
GROUP BY t.app_id, t.channel, t.device
END;
        $query = $this->db('trade')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $payment_new_device = $query->fetchAll();

        return $this->dataMerge($this->dataMerge($result_payment, $payment_new_account), $payment_new_device);
    }


    /**
     * 概况分析
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function statsReport()
    {
        // 导入 - 新增设备
        $sql = <<<END
INSERT IGNORE INTO account_uuid (uuid, app_id, user_id, channel, device, create_time)
SELECT uuid, app_id, user_id, channel, device, create_time FROM `account_login_{$this->month}`
WHERE uuid<>'' AND create_time BETWEEN '{$this->date} 00:00:00' AND '{$this->date} 23:59:59'
GROUP BY uuid, app_id
END;
        $this->db('logs')->exec($sql);


        // 计算 - 活跃账号，活跃设备，账号登录次数
        $sql = <<<END
SELECT
    app_id,channel,device,
    COUNT(1) login_times,
    COUNT(DISTINCT user_id) active_account,
    COUNT(DISTINCT uuid) active_device
FROM
    account_login_{$this->month}
WHERE
    create_time BETWEEN '{$this->date} 00:00:00' AND '{$this->date} 23:59:59'
GROUP BY
    app_id,channel,device
END;
        $query = $this->db('logs')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result_active = $query->fetchAll();


        // 计算 - 新增设备 TODO :: create_time 无索引导致的效率问题
        $sql = <<<END
SELECT
  app_id, channel, device, COUNT(uuid) new_device
FROM
  `account_uuid`
WHERE create_time BETWEEN '{$this->date} 00:00:00' AND '{$this->date} 23:59:59'
GROUP BY
  app_id,channel,device
END;
        $query = $this->db('logs')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $new_device = $query->fetchAll();


        // 计算 - 新增账号
        $sql = <<<END
SELECT
    app_id, channel, device, COUNT(DISTINCT user_id) new_account
FROM
    `account_login_{$this->month}`
WHERE
    type=1 AND (create_time BETWEEN '{$this->date} 00:00:00' AND '{$this->date} 23:59:59')
GROUP BY
    app_id, channel, device
END;
        $query = $this->db('logs')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $new_account = $query->fetchAll();

        // 合并
        $result = $this->dataMerge($new_account, $new_device);
        return $this->dataMerge($result_active, $result);
    }


    /**
     * 获取活跃设备(去重)
     * @param string $date
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function _getActiveDevice($date = '')
    {
        $month = date('Ym', strtotime($date));
        $sql = <<<END
SELECT
    uuid, app_id, channel, device
FROM
    account_login_{$month}
WHERE
    create_time BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59'
GROUP BY
    uuid, app_id, channel, device
END;
        $query = $this->db('logs')->query($sql);
        if (!$query) {
            return [];
        }
        $query->setFetchMode(PDO::FETCH_ASSOC);
        return $query->fetchAll();
    }


    /**
     * 获取新增设备
     * @param string $from
     * @param string $to
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function _getNewDevice($from = '', $to = '')
    {
        if (!$to) {
            $to = $from;
        }
        $sql = <<<END
SELECT uuid, app_id, channel, device
FROM `account_uuid`
WHERE create_time>='{$from} 00:00:00' AND create_time<='{$to} 23:59:59'
END;
        $query = $this->db('logs')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        return $query->fetchAll();
    }

    /**
     * 获取新增账号
     * @param string $from
     * @param string $to
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function _getNewUser($date = '')
    {
        $month = date('Ym', strtotime($date));
        $sql = <<<END
SELECT
    user_id, app_id, channel, device
FROM
    account_login_{$month}
WHERE
    type=1 AND (create_time BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59')
END;
        $query = $this->db('logs')->query($sql);
        if (!$query) {
            return [];
        }
        $query->setFetchMode(PDO::FETCH_ASSOC);
        return $query->fetchAll();
    }

    /**
     * 获取活跃账号(去重)
     * @param string $date
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function _getActiveUser($date = '')
    {
        $month = date('Ym', strtotime($date));
        $sql = <<<END
SELECT
    user_id, app_id, channel, device
FROM
    account_login_{$month}
WHERE
    create_time BETWEEN '{$date} 00:00:00' AND '{$date} 23:59:59'
GROUP BY
    user_id
END;
        $query = $this->db('logs')->query($sql);
        if (!$query) {
            return [];
        }
        $query->setFetchMode(PDO::FETCH_ASSOC);
        return $query->fetchAll();
    }

    /**
     * 数据合并
     * @param array $arr1
     * @param array $arr2
     * @return array
     */
    private function dataMerge($arr1 = [], $arr2 = [])
    {
        if (empty($arr1)) {
            return $arr2;
        }
        if (empty($arr2)) {
            return $arr1;
        }

        // 字典
        $dict = [];
        foreach ($arr2 as $value) {
            $k = $value['app_id'] . ':' . $value['channel'] . ':' . $value['device'];
            $dict[$k] = $value;
        }

        // 循环合并
        $used_key = [];
        foreach ($arr1 as $key => $value) {
            $k = $value['app_id'] . ':' . $value['channel'] . ':' . $value['device'];
            if (isset($dict[$k])) {
                $used_key[] = $k;
                $arr1[$key] = array_merge($value, $dict[$k]);
            }
        }

        // 合并未使用的字典
        $left = array_diff_key($dict, array_flip($used_key));
        // 补全键名
        if ($left) {
            $keyListDict = array_keys(reset($dict));
            $keyListAll = array_keys(reset($arr1));
            $keyLost = array_diff($keyListAll, $keyListDict);
            $lost = [];
            foreach ($keyLost as $k => $v) {
                $lost[$v] = 0;
            }
            foreach ($left as $k => $v) {
                $left[$k] = array_merge($v, $lost);
            }
        }

        return array_merge($arr1, $left);
    }


    /**
     * 写入文件
     * @param array $data
     * @param string $fileName
     * @param bool|false $headerField
     * @param string $fileType
     * @return bool
     */
    private function exportCSV($data = array(), $fileName = '', $headerField = false, $fileType = 'csv')
    {
        if (!$data) {
            return false;
        }

        // 排序
        $data = array_map(function ($v) {
            ksort($v);
            return $v;
        }, $data);

        // 文件名
        $dir = '/data/source/';
        if (!is_dir($dir)) {
            mkdir($dir);
        }
        $filePath = $dir . str_replace('-', '', $this->date);
        $filePath .= '_' . $fileName . '.csv';

        // 导出类型
        $split = ",";
        if ($fileType == 'xls') {
            $split = "\t";
        }

        // 表头
        if ($headerField) {
            $first = reset($data);
            $output = implode($split, array_keys($first));
            $output .= "\r\n";
        }
        else {
            $output = '';
        }

        // 分割写入
        $skip = 1000;
        $max = count($data);
        $fp = fopen($filePath, "w+"); // a+
        foreach ($data as $key => $value) {
            $output .= implode($split, array_values($value));
            $output .= "\r\n";
            if ((($key != 0) && ($key % $skip == 0)) || ($max == $key + 1)) {
                fwrite($fp, $output);
                $output = '';
            }
        }
        fclose($fp);
    }


    /**
     * redis连接
     * @return Redis
     */
    private function redis()
    {
        if (empty($this->_redis)) {
            $redis = new \Redis();
            $redis->connect($this->cfg['redis']['host'], $this->cfg['redis']['port']);
            $redis->auth($this->cfg['redis']['pass']);
            $redis->select($this->cfg['redis']['db']);
            $this->_redis = $redis;
        }
        return $this->_redis;
    }


    /**
     * redis删除数据
     * @param $key
     */
    private function redis_del($key)
    {
        $keys = $this->redis()->keys($key);
        if ($keys) {
            foreach ($keys as $k) {
                $this->redis()->del($k);
            }
        }
    }


    /**
     * @param string $handle
     * @return PDO
     */
    private function db($handle = '')
    {
        $params = $this->cfg['db_' . $handle];
        $dsn = 'mysql:host=' . $params['host'] . ';port=' . $params['port'] . ';dbname=' . $params['dbname'];
        $db = new PDO($dsn, $params['user'], $params['pass']);
        $db->query('set names ' . $params['charset']);
        return $db;
    }


    private function readCfg()
    {
        $this->cfg = Yaml::parse(file_get_contents(__DIR__ . "/stats.yml"));
    }


    /**
     * 日志
     * @param string $msg
     */
    private function logger($msg = '')
    {
        print date('Y-m-d H:i:s O ') . $msg . "\r\n";
    }


    private function takeUp()
    {
        $this->_RUN_TIME_END = time();
        $this->logger('---------------');
        $this->logger('占用内存: ' . round(memory_get_usage() / 1024 / 1024, 2) . 'M');
        $this->logger('执行时间: ' . round(($this->_RUN_TIME_END - $this->_RUN_TIME_START) / 60, 2) . '分钟');
        $this->logger('-----------------------------------------');
    }


    /**
     * 设置参数
     */
    private function getOptions()
    {
        $options = getopt('c:m:d:ih', ['date:', 'method:']);

        if (isset($options['h'])) {
            $help = <<<END
-------------------------------------------
使用:
php stats.php -d 20170601
-------------------------------------------
-h          帮助
-i          显示配置信息
-c          指定货币 默认USD, 可选CNY
-d          指定日期 20170517
-m          指定方法
-------------------------------------------\r\n
END;
            print_r($help);
            exit;
        }
        // set currency
        if (isset($options['c'])) {
            if ($options['c'] == 'CNY') {
                $this->cField = 'amount';
            }
            else {
                $this->cField = 'amount_usd';
            }
        }
        // set date
        if (isset($options['d'])) {
            $this->date = date('Y-m-d', strtotime($options['d']));
            $this->month = date('Ym', strtotime($options['d']));
        }
        else {
            $this->date = date('Y-m-d', strtotime('-1 days'));
            $this->month = date('Ym', strtotime('-1 days'));
        }
        // set method
        if (isset($options['m'])) {
            $this->$options['m']();
            exit();
        }
    }

}

$audit = new Stats();
$audit->run();