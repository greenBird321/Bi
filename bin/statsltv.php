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
    private $retention_days = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 21, 30, 60];
    private $endDate = '2017-11-01';


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
        $this->logger('analysis ltv');

        if ($this->date == date('Y-m-d', strtotime('-1 days'))) {
            for ($i = strtotime($this->endDate); $i <= strtotime($this->date); $i += 86400) {
                $this->month = date("Y-m", $i);
                $report = $this->statsLTV(date("Y-m-d", $i));
            }
        }
        else {
            $report = $this->statsLTV($this->date);
        }
//        $this->saveToBI($report, 'ltv');

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


        if ($type == 'ltv') {
            $table = 'ltv';
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

            $k = array_keys($value);
            $v = array_values($value);

            $k = "`" . implode("`,`", $k) . "`";
            $v = "'" . implode("','", $v) . "'";
            $sql .= "INSERT INTO `{$table}` ($k) VALUES ($v);";
        }

        $this->db('bi')->exec($sql);
    }


    /**
     * 用户价值LTV
     * 新增设备价值
     */
    public function statsLTV($date)
    {
        foreach ($this->retention_days as $day) {

            $stats_day = date('Y-m-d', strtotime("{$date} + {$day} day"));
            if ($stats_day >= date('Y-m-d', time())) {
                continue;
            }

            $ltv = $this->getLtv($date, $day);

            if (!empty($ltv)) {
                continue;
            }

            $newUserList = $this->_getNewUser($date);
            $userStr = '';
            foreach ($newUserList as $key => $item) {
                $userStr .= $item['user_id'] . ',';

            }
            $userStr = rtrim($userStr, ",");
            $amountList = $this->_getAmount($userStr, $date, $stats_day);

            foreach ($amountList as $key => $item) {
                $amountList[$key]['date'] = $date;
                $amountList[$key]['days'] = $day;

            }

            $this->saveToBI($amountList, 'ltv');
//
//            $ltv[] = $newdeviceList;
        }

//        return $ltv;
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
     * 获取订单总额
     * @param string $from
     * @param string $to
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function _getAmount($data, $from = '', $to = '')
    {
        if (!$to) {
            $to = $from;
        }
        $sql = <<<END
SELECT app_id,device,channel,sum(amount) amount FROM `transactions` where 
user_id in( {$data})AND status = 'complete'
AND create_time>='{$from} 00:00:00' AND create_time<='{$to} 23:59:59' 
GROUP by app_id, channel, device
END;
        $query = $this->db('trade')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result = $query->fetchAll();
        return $result;

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

    private function getLtv($date, $day)
    {
        $sql = "SELECT * FROM ltv WHERE date = '{$date}' AND days = $day";
        $query = $this->db('bi')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result = $query->fetch();
        return $result;
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