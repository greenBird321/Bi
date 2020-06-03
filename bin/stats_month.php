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
    private $month;
    private $month_format;
    private $cfg;
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
        $this->logger('analysis report by month');
        $report = $this->statsReport();
        $this->saveToBI($report, 'report');

        // 付费
        $this->logger('analysis payment by month');
        $payment = $this->statsPayment();
        $this->saveToBI($payment, 'payment');

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
            $table = 'report_month';
            // 删除旧数据
            $sqlDelete = "DELETE FROM $table WHERE `ym`='{$this->month_format}'";
            $this->db('bi')->exec($sqlDelete);
        }

        if ($type == 'payment') {
            $table = 'payment_month';
            // 删除旧数据
            $sqlDelete = "DELETE FROM $table WHERE `ym`='{$this->month_format}'";
            $this->db('bi')->exec($sqlDelete);
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

            $value['ym'] = $this->month_format;

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
    AND LEFT(complete_time, 7) = '{$this->month_format}'
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
WHERE LEFT(t.min_time, 7) = '{$this->month_format}'
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
WHERE LEFT(t.min_time, 7) >= '{$this->month_format}'
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
        /*$sql = <<<END
INSERT IGNORE INTO account_uuid (uuid, app_id, user_id, channel, device, create_time)
SELECT uuid, app_id, user_id, channel, device, create_time FROM `account_login_{$this->month}`
WHERE uuid<>'' AND create_time BETWEEN '{$this->date} 00:00:00' AND '{$this->date} 23:59:59'
GROUP BY uuid, app_id
END;
        $this->db('logs')->exec($sql);*/


        // 计算 - 活跃账号，活跃设备，账号登录次数
        $sql = <<<END
SELECT
    app_id,channel,device,
    COUNT(1) login_times,
    COUNT(DISTINCT user_id) active_account,
    COUNT(DISTINCT uuid) active_device
FROM
    account_login_{$this->month}
GROUP BY
    app_id,channel,device
END;
        $query = $this->db('logs')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result_active = $query->fetchAll();


        // 计算 - 新增设备 TODO :: create_time 无索引导致的效率问题
        //TODO :: 此处sql截取create_time可优化（保持create_time不变，将月头和月尾时间算出来）
        $sql = <<<END
SELECT
  app_id, channel, device, COUNT(uuid) new_device
FROM
  `account_uuid`
WHERE LEFT(create_time, 7) = '{$this->month_format}'
GROUP BY
  app_id,channel,device
END;
        $query = $this->db('logs')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $new_device = $query->fetchAll();


        // 计算 - 新增账号
        //TODO :: 此处sql截取create_time可优化（保持create_time不变，将月头和月尾时间算出来）
        $sql = <<<END
SELECT
    app_id, channel, device, COUNT(DISTINCT user_id) new_account
FROM
    `account_login_{$this->month}`
WHERE
    type=1 AND LEFT(create_time, 7) = '{$this->month_format}'
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
        $options = getopt('c:m:t:ih', ['date:', 'method:']);

        if (isset($options['h'])) {
            $help = <<<END
-------------------------------------------
使用:
php stats.php -t 201710
-------------------------------------------
-h          帮助
-i          显示配置信息
-c          指定货币 默认USD, 可选CNY
-t          指定月份 201710
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
            } else {
                $this->cField = 'amount_usd';
            }
        }
        // set month
        if (isset($options['t'])) {
            $this->month = $options['t'];
	    $this->month_format = substr($this->month, 0, 4) . '-' . substr($this->month, -2);
        } else {
            $this->month = date('Ym', strtotime('-1 month'));
            $this->month_format = date('Y-m', strtotime('-1 month'));
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
