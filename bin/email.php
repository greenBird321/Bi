<?php


use Symfony\Component\Yaml\Yaml;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include __DIR__ . '/../vendor/autoload.php';
$config = Yaml::parse(file_get_contents(__DIR__ . "/../app/config/app.yml"));
$timezone = $config['setting']['timezone'];
ini_set("date.timezone", $timezone);
ini_set('memory_limit', -1);
ini_set('max_execution_time', '0');

class Email
{
    private $_RUN_TIME_START;
    private $_RUN_TIME_END;
    private $words = [
        'new_account' => '新增用户',
        'new_pay'     => '新增付费',
        'stay_user'   => '用户留存',
        'ARPU'        => 'ARPU',
        'ARPPU'       => 'ARPPU',
        'rate'        => '付费率',
    ];
    //数组显示对应订阅标题
    private $app = [
        1021012 => '三生三世-测试',
        1021013 => '三生三世-Android',
        1021014 => '三生三世-正式Ios',
        1021015 => '三生三世-简体-百文 ',
        1021016 => '三生三世-繁体',
    ];

    public function __construct()
    {
        $this->_RUN_TIME_START = time();
    }

    public function run()
    {
        $this->readCfg();
        $this->logger('START');
        // 基本
        $this->logger('send email');
        $new_data = [];
        $user     = $this->getUser();
        //获取对应需要发送的邮件
        foreach ($user as $key => $value) {
            foreach ($value as $k => $v) {
                $new_data[$v['app_id']] = $this->getData($v['app_id']);
            }
        }
        //获取邮件循环发送数据
        $emailData = $this->getEmailData($user, $new_data);
        foreach ($emailData as $key => $value) {
            $this->sendEmail($key, $value);
        }
        $this->takeUp();
    }

    //获取邮件数据
    public function getEmailData($users, $new_data)
    {
        $result = [];
        foreach ($users as $key => $value) {
            foreach ($value as $k => $v) {
                $words = $this->getWords(json_decode($v['subscribe']), $new_data, $v['app_id']);
                foreach ($words as $kw => $vw) {
                    $vw['start_time']  = date('Y-m-d', $v['start_time']);
                    $vw['end_time']    = date('Y-m-d', $v['end_time']);
                    $result[$key][$kw] = $vw;
                }
            }
        }
        return $result;
    }

    /**
     * @param string $handle
     * @return PDO
     */
    private function db($handle = '')
    {
        $params = $this->cfg['db_' . $handle];
        $dsn    = 'mysql:host=' . $params['host'] . ';port=' . $params['port'] . ';dbname=' . $params['dbname'];
        $db     = new PDO($dsn, $params['user'], $params['pass']);
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

    //获取订阅用户
    private function getUser()
    {
        $sql   = "SELECT * FROM subscribe  WHERE `start_time` <=  " . time() . " AND `end_time` >=" . time();
        $query = $this->db('bi')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result = $query->fetchAll();
        //合并相同用户的多条记录
        $new_res = [];
        foreach ($result as $key => $value) {
            $new_res[$value['user_name']][] = $result[$key];
        }
        return $new_res;
    }

    private function takeUp()
    {
        $this->_RUN_TIME_END = time();
        $this->logger('---------------');
        $this->logger('占用内存: ' . round(memory_get_usage() / 1024 / 1024, 2) . 'M');
        $this->logger('执行时间: ' . round(($this->_RUN_TIME_END - $this->_RUN_TIME_START) / 60, 2) . '分钟');
        $this->logger('-----------------------------------------');
    }

    //发送邮件
    public function sendEmail($email, $data)
    {
        $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
        try {
            //Server settings
            $mail->SMTPDebug = 1;                                 // Enable verbose debug output
            $mail->isSMTP();                                      // Set mailer to use SMTP
            $mail->Host       = 'smtp.aliyun.com';                      // Specify main and backup SMTP servers
            $mail->SMTPAuth   = true;                               // Enable SMTP authentication
            $mail->Username   = 'huguifeng@aliyun.com';                 // SMTP username
            $mail->Password   = 'gamehetu123654';
            $mail->CharSet    = 'UTF-8';                             // SMTP
            $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted

            //Recipients
            $mail->setFrom('huguifeng@aliyun.com', 'aliyun');
            $mail->addAddress($email);     // Add a recipient
            $mail->addReplyTo('huguifeng@aliyun.com', 'aliyun');
            $mail->isHTML(true);                                  // Set email format to HTML
            $mail->Subject = '我的订阅';
            $str           = '';
            foreach ($data as $key => $value) {
                $str .= ('<P style="MARGIN-LEFT: 13px;">' . '<div style="color:red;font-size:30px;margin:0px auto;">' . $key . '</div><table style="margin:0px auto;">');
                foreach ($value as $k => $v) {
                    $str .= ('<tr><td style="color:green;">' . $k . '</td><td>:</td>' . '<td>' . $v . '</td></tr>');
                }
                $str .= '</table></p>';
            }
            //转换为中文便于阅读
            $str        = str_replace("start_time", "订阅开始时间", $str);
            $str        = str_replace("end_time", "订阅结束时间", $str);
            $mail->Body = str_replace('replace', $str, file_get_contents('replace.html'));
            $mail->send();
            $this->logger('Message has been sent');
        } catch (Exception $e) {
            $this->logger('Message could not be sent');
            $this->logger('Mailer Error: ' . $mail->ErrorInfo);
        }
    }

    //处理数据
    public function getWords($subscribe, $result, $appid)
    {

        $data = [];
        foreach ($result as $key => $value) {
            if ($appid == $key) {
                foreach ($subscribe as $k => $v) {
                    if (key_exists($v, $value)) {
                        $data[$key][$v] = $value[$v];
                    }
                }
            }
        }
        //将英文转为对应数组的汉字
        $new_data = [];
        foreach ($data as $key => $value) {
            if (key_exists($key, $this->app)) {
                $new_data[$this->app[$key]] = $data[$key];
            }
        }
        $fianl_data = [];
        foreach ($new_data as $key => $val) {
            foreach ($val as $k => $v) {
                if (key_exists($k, $this->words)) {
                    $fianl_data[$key][$this->words[$k]] = $new_data[$key][$k];

                }
            }
        }
        return $fianl_data;
    }

    //获取源数据
    public function getData($appid)
    {
        $date = date('Y-m-d', strtotime('-1 day'));
        //获取report_day数据
        $sql = "SELECT 
                `date`,
                sum(`new_account`) AS `new_account`,
                sum(`active_account`) AS `active_account`,
                sum(`new_device`) AS `new_device`,
                sum(`active_device`) AS `active_device`, 
                sum(`login_times`) AS `login_times`
              FROM 
                `report_day`
              WHERE
                `app_id` = $appid 
              AND `date` = " . "'$date'" . " 
              GROUP BY 
                `date`";

        $query = $this->db('bi')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $result = $query->fetch();
        //获取payment_day数据
        $sql   = "SELECT
                `date`,
                sum(`new_account`) AS `new_account`,
                sum(`new_device`) AS `new_device`,
                sum(`count_account`) AS `count_account`,
                sum(`count_device`) AS `count_device`,
                sum(`times`) AS `times`,
                sum(`amount`) AS `amount`
            FROM
                `payment_day`
            WHERE
                `app_id` = $appid
            AND `date` = " . "'$date'" . "
            GROUP BY
                `date`";
        $query = $this->db('bi')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $pay_result = $query->fetch();
        $date_2     = date('Y-m-d', strtotime('-2 day'));
        //获取次日留存数据
        $sql   = "SELECT
            `date`,
            `days`,
            sum(count_device) AS DeCount
        FROM
            `retention_user`
        WHERE
            `app_id` = $appid
        
        AND `date` = " . "'$date_2'" . "
        GROUP BY
            `date`,
            `days`
        ORDER BY
            `date` DESC,
            `days`";
        $query = $this->db('bi')->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $retention_result = $query->fetchAll();

        //组装数据

        $result['new_account'] = empty($result['new_account']) ? 0 : $result['new_account'];
        $result['new_pay']     = empty($pay_result['amount']) ? 0 : $pay_result['amount'];
        if (empty($pay_result['amount']) || empty($result['active_account'])) {
            $result['ARPU'] = 0;
        } else {
            $result['ARPU'] = round($pay_result['amount'] / $result['active_account'], 2);
        }
        if (empty($pay_result['amount']) || empty($pay_result['count_account'])) {
            $result['ARPPU'] = 0;
        } else {
            $result['ARPPU'] = round($pay_result['amount'] / $pay_result['count_account'], 2);
        }
        if (empty($pay_result['count_account']) || empty($result['active_account'])) {
            $result['rate'] = 0;
        } else {
            $result['rate'] = (round($pay_result['count_account'] / $result['active_account'], 2) * 100) . '%';
        }
        if (empty($retention_result[1]['DeCount']) || empty($retention_result[0]['DeCount'])) {
            $result['stay_user'] = '0.00%';
        } else {
            $result['stay_user'] = (round($retention_result[1]['DeCount'] / $retention_result[0]['DeCount'], 4) * 100) . '%';
        }
        return $result;
    }


}

$audit = new Email();
$audit->run();