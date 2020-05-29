<?php

/**
 * 统计报告
 */
namespace MyApp\Controllers;


use Phalcon\Mvc\Dispatcher;
use MyApp\Models\ReportCommon;
use MyApp\Models\Trade\Trade;
use MyApp\Models\PaymentCommon;

class ReportController extends ControllerBase
{
    public $default_app;
    private $_ReportCommon;
    private $_PaymentCommon;
    private $tradeModel;
    private $_keys_ch = [
        "new_account" => "新增用户",
        "active_account" => "活跃用户",
        "amount" => "付费总额",
        "login_times" => "登录次数",
        "rate" => "付费率",
        "arpu" => "arpu",
        "arppu" => "arppu"
    ];
    public $timeType = [
        'day' => 'date',
        'week' => 'week',
        'month' => 'ym'
    ];
    private $_channelCode = [
        19 => '木蚂蚁',
        52 => '果盘(XX助手)',
        63 => '新浪游戏',
        90 => 'TT语音',
        409 => '葫芦侠',
        610 => '广凡游戏',
        633 => '闲兔',
        634 => '乐嗨游戏(B)',
        664 => '曼巴游戏',
        737 => 'btgame_海外',
        779 => '炫玩网络',
        900 => '一牛',
        926 => '游戏FAN（新）',
        1030 => '九妖游戏',
        1019 => '游钛游戏',
        1053 => '贪玩365',
        1168 => '聚乐荣城',
    ];

    protected $amchart;
    protected $table;


    public function initialize()
    {
        parent::initialize();

        $this->_ReportCommon = new ReportCommon();
        $this->_PaymentCommon = new PaymentCommon();
        $this->default_app = $this->session->get('default_app')['game_id'];
    }


    public function realtimeAction()
    {
        if ($this->request->isPost()) {
            $param = $this->request->get();
            $date = $param['start_time'];
        }
        if (empty($date)) {
            $date = date('Y-m-d', time());
        }
        //配置抓取参数
        $game_id = $this->session->get('default_app')['game_id'];
        $url1 = 'http://' . '1031019' . '.' . $this->config->rpc->url . 'stats/realtime?date=' . $date . "&channel=true";
        $url = 'http://' . '1031019' . '.' . $this->config->rpc->url . 'stats/realtime?date=' . $date;
        $data1 = json_decode(file_get_contents($url1), true);
        $data = json_decode(file_get_contents($url), true);
        $report_new = $report_act = $img = [];
        if ($data['code'] == 0 && $data1['code'] == 0) {
            $result = $data['data'];
            $result_channel = $data1['data'];
            $report_overview = $result['new'];
            $report_overview2 = $result['act'];

            //处理新增数据
            $report_new['new'] = $result['new'];
            foreach ($report_new as $key => $item) {
                if (empty($key)) {
                    continue;
                }
                $tab = [];
                $tab[] = $key;
                foreach ($item as $detail) {
                    $tmp = explode(' ', $detail['time']);
                    $k = (int)$tmp[1];
                    $tab[$k + 1] = $detail['new_account'];
                    ksort($tab);
                }
                $report_tab[] = $tab;
            }
//            处理新增数据的渠道问提
            $report_tab5 = [];
            $result_new_channel['new'] = $result_channel['new'];
            $new_channel = array_column_index($result_new_channel['new'], 'channel');
            foreach ($new_channel as $key => $item) {
                if (empty($key)) {
                    continue;
                }
                $tab = [];
                $tab[] = $key;
                foreach ($item as $detail) {
                    $tmp = explode(' ', $detail['time']);
                    $k = (int)$tmp[1];
                    $tab[$k + 1] = $detail['new_account'];
                    ksort($tab);
                }
                $report_tab5[] = $tab;
            }
            $report_final = [];
            foreach ($report_tab5 as $key => $item) {
                if (count($item) < 26) {
                    for ($i = 1; $i < 26; $i++) {
                        if (empty($item[$i])) {
                            $item[$i] = '-';
                        }
                    }
                }
                ksort($item);
                $report_final[$key] = $item;
            }
            $new_channel_report = $report_final;
            //处理huoyue数据
            $report_act['act'] = $result['act'];
            foreach ($report_act as $key => $item) {
                if (empty($key)) {
                    continue;
                }
                $tab = [];
                $tab[] = $key;

                foreach ($item as $detail) {

                    $tmp = explode(' ', $detail['time']);
                    $k = (int)$tmp[1];
                    $tab[$k + 1] = $detail['act_account'];
                    ksort($tab);
                }
                $report_tab[] = $tab;
            }
            $report_tab2 = [];
            $result_act_channel['new'] = $result_channel['act'];
            $act_channel = array_column_index($result_act_channel['new'], 'channel');
            foreach ($act_channel as $key => $item) {
                if (empty($key)) {
                    continue;
                }
                $tab = [];
                $tab[] = $key;
                foreach ($item as $detail) {
                    $tmp = explode(' ', $detail['time']);
                    $k = (int)$tmp[1];
                    $tab[$k + 1] = $detail['act_account'];
                    ksort($tab);
                }
                $report_tab2[] = $tab;
            }
            foreach ($report_tab2 as $key => $item) {
                if (count($item) < 26) {
                    for ($i = 1; $i < 26; $i++) {
                        if (empty($item[$i])) {
                            $item[$i] = '-';
                        }
                    }
                }
                ksort($item);
                $report_final[$key] = $item;
            }
            $act_channel_report = $report_final;
            //处理活跃设备
            foreach ($report_act as $key => $item) {
                if (empty($key)) {
                    continue;
                }
                $tab = [];
                $tab[] = $key;
                foreach ($item as $detail) {
                    $tmp = explode(' ', $detail['time']);
                    $k = (int)$tmp[1];
                    $tab[$k + 1] = $detail['act_uuid'];
                    ksort($tab);
                }
                $report_tab[] = $tab;
            }
            //处理活跃ip
            foreach ($report_act as $key => $item) {
                if (empty($key)) {
                    continue;
                }
                $tab = [];
                $tab[] = $key;
                foreach ($item as $detail) {
                    $tmp = explode(' ', $detail['time']);
                    $k = (int)$tmp[1];
                    $tab[$k + 1] = $detail['act_ip'];
                    ksort($tab);
                }
                $report_tab[] = $tab;
            }
            foreach ($report_tab as $key => $item) {
                if (count($item) < 26) {
                    for ($i = 1; $i < 26; $i++) {
                        if (empty($item[$i])) {
                            $item[$i] = '-';
                        }
                    }
                }
                ksort($item);
                $report_final[$key] = $item;
            }

            //新增图表数据处理
            foreach ($report_overview as $key => $item) {
                $tmp = explode(' ', $item['time']);
                $overview['country'] = (int)$tmp[1];
                $overview['visits'] = $item['new_account'];
                $img[$overview['country']] = $overview;
            }

            if (count($img) < 24) {
                for ($i = 0; $i < 24; $i++) {
                    if (empty($img[$i])) {
                        $img[$i]['country'] = $i;
                        $img[$i]['visits'] = 0;
                    }
                }
            }
            ksort($img);
            //新增图表数据处理
            $img2 = [];
            foreach ($report_overview2 as $key => $item) {
                $tmp = explode(' ', $item['time']);
                $overview2['country'] = (int)$tmp[1];
                $overview2['visits2'] = $item['act_account'];
                $img2[$overview2['country']] = $overview2;
            }

            if (count($img2) < 24) {
                for ($i = 0; $i < 24; $i++) {
                    if (empty($img2[$i])) {
                        $img2[$i]['country'] = $i;
                        $img2[$i]['visits2'] = 0;
                    }
                }
            }
            ksort($img2);

        }
        $new_img = array(array());
        foreach ($img as $key => $val) {
            foreach ($img2 as $k => $v) {
                if ($key == $k) {
                    $new_img[$key]['country'] = $key;
//                    $val['visits2'] = $v['visits2'];
                    $new_img[$key]['visits'] = $val['visits'];
                    $new_img[$key]['visits2'] = $v['visits2'];
                }
            }
        }
        $data = array(array());
        foreach ($report_final as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $data[$key2][$key] = $val2;
            }

        }
        unset($data[0]);
        $arr = [];

        foreach ($data as $key => $value) {
            $arr[$key - 1] = array_merge(array($key - 1), $value);
        }
        unset($arr[24]);
        //处理数据的时间显示问题；
        $new_arr = array();
        foreach ($arr as $key => $value) {
            if ($value[0] < 10) {
                $value[0] = '0' . $value[0];
            }
            $new_arr[] = array($date . " $value[0]", $value[1], $value[2], $value[3], $value[4]);
        }

        //处理充值数据
        $this->tradeModel = new Trade();
        $data = $this->tradeModel->getData($date, $game_id);
        $new_array = [];
        foreach ($new_arr as $k => $val) {
            $new_array[] = $val;
            foreach ($data as $key => $value) {
                if ($val[0] == $value['time']) {
                    if (!empty($value['amount'])) {
                        $new_array[$k][] = $value['amount'];
                    } else {
                        $new_array[$k][] = '-';
                    }
                    if (!empty($value['uuid'])) {
                        $new_array[$k][] = $value['uuid'];
                    } else {
                        $new_array[$k][] = '-';
                    }

                }
            }
        }
        foreach ($new_array as $key => &$val) {
            if (count($val) == 5) {
                $val[] = '-';
                $val[] = '-';
            }
        }
        //处理今天当前时间以后数据不显示
        if ($date == date("Y-m-d", time())) {
            $hour = intval(date("H", time()));
            for ($i = $hour + 1; $i <= 23; $i++) {
                unset($new_array[$i]);
            }

        }

        // 处理渠道充值
        $pay = $data1['data']['pay'];
        //dump($pay);
        $channel_pay_hour = [];
        $channel_name = [];
        $tmp = [];
        foreach ($pay as $key => $channel_pay) {
            $tmp[0] = isset($this->_channelCode[intval($channel_pay['channel'])]) ? $this->_channelCode[intval($channel_pay['channel'])] : $channel_pay['channel'];
            $tmp[intval(substr($channel_pay['time'], -2, 2) + 1)] = $channel_pay['total_amount'];
            if (!array_key_exists($tmp[0], $channel_name)) {
                    $channel_pay_hour[] = $tmp;
            } else {
                foreach ($channel_pay_hour as $key => $value) {
                    if ($value[0] == $tmp[0]) {
                        $diff = array_diff_assoc($tmp, $value);
                        if (empty($diff)) {
                            continue;
                        } else {
                            $channel_pay_hour[$key] = $value + $diff;
                        }
                    }
                }
            }
            $channel_name[$tmp[0]] = 1;     // 只是为了判断是否有此渠道数据 无实际用处
            $tmp = [];
        }
        //格式整理
        foreach ($channel_pay_hour as $key => $value) {
            for($i = 0; $i <= 25; $i++) {
                if (!isset($channel_pay_hour[$key][$i])) {
                    $channel_pay_hour[$key][$i] = '-';
                }
            }
            ksort($channel_pay_hour[$key]);
        }
        
//        处理总计
        $sum_account = 0;
        $sum_amount = 0;
        foreach ($new_array as $key => $value) {
            if (isset($value[1]) && $value[1] != '-') {
                $sum_account += $value[1];
            }
            if (isset($value[1]) && $value[5] != '-') {
                $sum_amount += $value[5];
            }
        }
        $ar = ['总计', $sum_account, '-', '-', '-', number_format($sum_amount, 2), '-'];
        array_unshift($new_array, $ar);
//        dd($new_array);
        $this->view->date = $date;
        $this->view->report_act = json_encode($act_channel_report); //活跃渠道
        $this->view->report_new = json_encode($new_channel_report); //新增渠道
        $this->view->report_tab = json_encode($new_array);         // 表格数据
        $this->view->report_img = json_encode($new_img);         // 图表数据
        $this->view->report_pay = json_encode($channel_pay_hour);   // 各个渠道的充值数据
        $this->view->pick('report/realtime');
    }


    /**
     * 日概况
     */
    public function dayAction()
    {
        $base = $this->mergeQueryCond();
        $start_time = strtotime($base['where']['date']['start']);
        $end_time = strtotime($base['where']['date']['end']);
        $processed = $this->getData($base);
        //数据补全
        $processed = $this->checkData($processed, $start_time, $end_time);
        $this->assignData($processed, $base);
        $this->view->pick('report/day');
    }


    /**
     * 周概况
     */
    public function weekAction()
    {
        $type = 'week';
        $base = $this->mergeQueryCond($type);
        $start_time = $base['where']['week_start']['start'];
        $end_time = $base['where']['week_start']['end'];
        $processed = $this->getData($base, $type);
        //数据补全
        $processed = $this->checkData($processed, $start_time, $end_time, 'week');
        $this->assignData($processed, $base, $type);
        $this->view->pick('report/week');
    }


    /**
     * 月概况
     */
    public function monthAction()
    {
        $type = 'month';
        $base = $this->mergeQueryCond($type);                           // 查询条件获取
        $start_time = $base['where']['ym']['start'];
        $end_time = $base['where']['ym']['end'];
        $processed = $this->getData($base, $type);
        //数据不全
        $processed = $this->checkData($processed, $start_time, $end_time, 'month');
        $this->assignData($processed, $base, $type);
        $this->view->pick('report/month');
    }

    /**
     * @param $base
     * @param string $type
     * 共用的获取数据方法
     */
    public function getData($base, $type = 'day')
    {
        $reports_origin = $this->reportData($base, "report_{$type}", $type);
        $payment_origin = $this->paymentData($base, "payment_{$type}", $type);
        return $this->dataCombineProcess($reports_origin, $payment_origin);     // 综合数据处理
    }


    /**
     * 分配模板数据
     * @param $processed
     * @param string $type
     */
    public function assignData($processed, $base, $type = 'day')
    {
        $report_type = "{$type}_report";
        $time_fields = [
            'day' => 'date',
            'week' => 'week_start',
            'month' => 'ym'
        ];
        $this->view->setVars(
            [
                "$report_type" => json_encode(array_values($processed['amchart'])),
                'report_tmp' => json_encode(array_values($processed['tmp'])),
                'tab_data' => json_encode(array_values($processed['table'])),
                'start_time' => $base['where'][$time_fields[$type]]['start'],
                'end_time' => $base['where'][$time_fields[$type]]['end']
            ]
        );
    }


    /**
     * des: 日志数据综合处理
     * @param $cond [type|array] 基础查询条件
     * @param $table [type|string] 表名
     * @return array
     */
    protected function reportData($cond, $table, $type = 'day')
    {
        // 查询条件组装
        $report_cond = $cond;
        $timeType = $timeTypeSpecial = $this->timeType[$type];
        //周数据特殊处理
        if ($type == 'week') {
            $report_cond['order'] = "week_start";
            $timeTypeSpecial = "CONCAT (`year`, '/', `week`) week";
        }
        $report_cond["field"] = "
            $timeTypeSpecial,
            `channel`,
            `device`,
            sum(`new_account`) as `new_account`,
            sum(`active_account`) as `active_account`,
            sum(`new_device`) as `new_device`,
            sum(`active_device`) as `active_device`,
            sum(`login_times`) as `login_times`";                           // 查询字段配置
        $report_cond["joint"] = "GROUP BY `$timeType`,`channel`,`device`";

        $result = array_column_index($this->_ReportCommon->getDatas(
            $report_cond,
            $table
        ), $timeType);                                                         // 每日用户日志原始数据

        return $result;
    }

    /**
     * des: 交易数据综合处理
     * @param $cond [type|array] 基础查询条件
     * @param $table [type|string] 表名
     * @return array
     */
    protected function paymentData($cond, $table, $type = 'day')
    {
        // 查询条件组装
        $payment_cond = $cond;
        // 根据时间类型（天，周，月）修改查询字段
        $timeType = $timeTypeSpecial = $this->timeType[$type];
        if ($type == 'week') {
            $timeTypeSpecial = "CONCAT (`year`, '/', `week`) week";
        }
        $payment_cond["field"] = "
            $timeTypeSpecial,
            `channel`,
            `device`,
            sum(`new_account`) as `new_account`,
            sum(`new_device`) as `new_device`,
            sum(`count_account`) as `count_account`,
            sum(`count_device`) as `count_device`,
            sum(`times`) as `times`,
            sum(`amount`) as `amount`";                                     // 查询字段配置
        $payment_cond["joint"] = "GROUP BY `$timeType`,`channel`,`device`";
        $result = array_column_index($this->_PaymentCommon->getDatas(
            $payment_cond,
            $table
        ), $timeType);                                                         // 每日/周/月付费日志原始数据

        return $result;
    }

    /**
     * des: 输出的综合数据处理
     * @param $report [type|array] 处理日志数据
     * @param $payment [type|array] 交易日志数据
     * @return array [type|array] [
     * "amchart"   => 'amchart 图所需数据',
     * "tmp"       => 'amchart 图模板数据',
     * "table"     => 'datatables 数据'
     * ]
     */
    protected function dataCombineProcess($report, $payment)
    {
        $base_data = [];                            // 组装后的数据
        $table_data = [];                           // 返回给前端 datatables 插件数据

        // 判断数组维度, 3维数组说明数据未选中某一渠道, 需要将所有渠道、设备数据进行累加
        if (depthOfArray($report) == 3 || depthOfArray($payment) == 3) {
            $report = $this->depthCrop($report);
            $payment = $this->depthCrop($payment);
        }

        foreach ($report as $key => $value) {
            if (isset($payment[$key])) {
                $tmp = [];
                $tmp["date"] = $key;
                $tmp["new_account"] = $value["new_account"];
                $tmp["active_account"] = $value["active_account"];
                $tmp["amount"] = $payment[$key]["amount"];
                $tmp["login_times"] = $value["login_times"];
                $tmp["rate"] = round($payment[$key]["count_account"] / $value["active_account"], 2);
                $tmp["arpu"] = round($payment[$key]["amount"] / $value["active_account"], 2);
                $tmp["arppu"] = round($payment[$key]["amount"] / $payment[$key]["count_account"], 2);
            } else {
                $tmp = [];
                $tmp["date"] = $key;
                $tmp["new_account"] = $value["new_account"];
                $tmp["active_account"] = $value["active_account"];
                $tmp["amount"] = 0;
                $tmp["login_times"] = $value["login_times"];
                $tmp["rate"] = 0;
                $tmp["arpu"] = 0;
                $tmp["arppu"] = 0;
            }

            array_push($base_data, $tmp);
            $table_data[$key] = array_values($tmp);
        }

        $app_id = $this->session->get('default_app')["game_id"];                    // 获取 app_id

        $final_report = [];                                                         // amchart 图所需数据
        $chart_tmp = [];                                                            // amchart 图模板数据
        $key_grps = [];                                                             // 临时变量

        // 图像数据和图像模板组装
        foreach ($base_data as $key => $value) {
            if (empty($key_grps)) {
                $key_grps = array_keys($value);

                foreach ($key_grps as $k_g => $v_g) {
                    if ($k_g == 'date') {
                        continue;
                    }
                    $chart_tmp[$k_g]["id"] = $v_g . '_' . $app_id;
                    $chart_tmp[$k_g]["field"] = $v_g;
                    $chart_tmp[$k_g]["title"] = $this->_keys_ch[$v_g];
                }
            }

            $tmp = $value;
            $tmp["category"] = $value["date"];
            unset($tmp["date"]);
            $final_report[$value['date']] = $tmp;
        }

        return array("amchart" => $final_report, "tmp" => $chart_tmp, "table" => $table_data);
    }

    /**
     * des: 组装查询条件
     * @return array
     */
    protected function mergeQueryCond($type = 'day')
    {

        // 用户配置查询条件
        if ($this->request->isPost()) {
            $reqs = $this->request->get();

            switch ($type) {
                case 'day':
                    $date = dateCompare($reqs["start_time"], $reqs["end_time"]);
                    if (!empty($date)) {
                        $base['date']['start'] = $date[0];
                        $base['date']['end'] = date('Y-m-d', strtotime($date[1])) . ' 23:59:59';
                        $base['date']['type'] = 'range';
                    }
                    break;
                case 'week':
                    if ($reqs['start_time']) {
                        $start_time = $this->getWeekInfoByDate($reqs['start_time'])['start'];
                    }
                    if ($reqs['end_time']) {
                        $end_time = $this->getWeekInfoByDate($reqs['end_time'])['start'];
                    }
                    $base["week_start"] = [
                        'start' => $start_time,
                        'end' => $end_time,
                        'type' => 'range'
                    ];
                    break;
                case 'month':
                    $base["ym"] = [
                        'start' => $reqs['start_time'],
                        'end' => $reqs['end_time'],
                        'type' => 'range'
                    ];
                    break;
                default:
            }

            // 设备条件
            if (!empty($reqs["choice_device"])) {
                $base["device"] = array(
                    "type" => "in",
                    "value" => $reqs["choice_device"],
                );
            }

            // 渠道条件
            if (!empty($reqs["choice_channel"])) {
                $base["channel"] = array(
                    "type" => "in",
                    "value" => $reqs["choice_channel"],
                );
            }

        } //页面初始加载，没有筛选条件, 赋予默认时间
        else {
            switch ($type) {
                //日报默认时间14天前~1天前
                case 'day':
                    $base = array(
                        "date" => array(
                            "type" => "range",
                            "start" => date('Y-m-d', strtotime('-14 day')),
                            "end" => date('Y-m-d', strtotime('-1 day'))
                        )
                    );
                    break;
                //周报默认时间7周前～1周前
                case 'week':
                    $base = array(
                        "week_start" => array(
                            "type" => "range",
                            "start" => $this->getWeekInfoByDate(date('Y-m-d', strtotime('-7 week')))['start'],
                            "end" => $this->getWeekInfoByDate(date('Y-m-d', strtotime('-1 week')))['start'],
                        )
                    );
                    break;
                //月报默认时间6月前~1月前
                case 'month':
                    $base = array(
                        "ym" => array(
                            "type" => "range",
                            "start" => date('Y-m', strtotime('-6 month')),
                            "end" => date('Y-m', strtotime('-1 month'))
                        )
                    );
                    break;
                default:
            }
        }

        $base["app_id"] = array(
            "symbol" => '=',
            "value" => $this->default_app
        );

        return array("where" => $base);
    }


    /**
     * 根据给出的日期计算出该日期所在周的开始时间，结束时间
     */
    private function getWeekInfoByDate($date)
    {
        $week = date('W', strtotime($date));
        $year = date('Y', strtotime($date));
        $dayNumber = $week * 7;
        $weekDayNumber = date("N", mktime(0, 0, 0, 1, $dayNumber, $year)); //当前周的第几天
        $startNumber = $dayNumber - $weekDayNumber;
        //开始日期
        $week_start = date("Y-m-d", mktime(0, 0, 0, 1, $startNumber + 1, $year));
        //结束日期
        $week_end = date("Y-m-d", mktime(0, 0, 0, 1, $startNumber + 7, $year));
        return ['start' => $week_start, 'end' => $week_end];
    }


    /**
     * des: 降低数组的维度, 并合并数据(特殊处理,不能复用)
     * @param: $origin [type|string] 原数据
     * @return array
     */
    private function depthCrop($origin)
    {
        $result = [];

        if (!empty($origin)) {
            foreach ($origin as $key_1 => $level_1) {
                $base = [];

                foreach ($level_1 as $key_2 => $level_2) {
                    if (empty($base)) {
                        $base = array_fill_keys(array_keys($level_2), '');           // 获取最低维度数组的键,并自动填充初始值
                    }

                    // 将维度内的数据根据键对应累加
                    foreach ($level_2 as $key_3 => $level_3) {
                        if (!in_array($key_3, ['date', 'channel', 'device'])) {
                            $base[$key_3] = $base[$key_3] + $level_3;
                        }
                    }
                }

                $result[$key_1] = $base;
            }
        }

        return $result;
    }


    protected function checkData($origin, $start, $end, $type = 'day') {
        if (!empty($origin)) {
            $this->amchart = $origin['amchart'];
            $this->table = $origin['table'];
            switch ($type) {
                case 'day':
                    for ($i = $end; $i >= $start; $i -= 86400) {
                        $day = date('Y-m-d', $i);
                        $this->fillData($day);
                    };
                    break;
                case 'week':
                    //根据选择的开始时间，计算出对应那一周的起始时间（周一）和结束时间（周日）。
                    $startDetail = $this->getWeekInfoByDate($start);
                    $start = $startDetail['start'];
                    //根据选择的结束时间，计算出对应那一周的其实时间（周一）和结束时间（周日）。
                    $endDetail = $this->getWeekInfoByDate($end);
                    $end = $endDetail['start'];
                    for ($i = strtotime($start); $i <= strtotime($end); $i += 86400 * 7) {
                        //补全图表所用数据
                        $week = date('Y', $i) . '/' . (int)date('W', $i);
                        $this->fillData($week);
                    };
                    break;
                case 'month':
                    for ($i = $start; $i <= $end; $i = date('Y-m', strtotime("$i +1month"))) {
                        /*//补全图表所用数据
                        if (empty($amchart[$i])) {
                            $amchart[$i] = [
                                'new_account' => 0,
                                'active_account' => 0,
                                'amount' => 0,
                                'login_times' => 0,
                                'rate' => 0,
                                'arpu' => 0,
                                'arppu' => 0,
                                'category' => $i,
                            ];
                        }

                        //补全table所用数据
                        if (empty($table[$i])) {
                            $table[$i] = [
                                $i,
                                '-',
                                '-',
                                '-',
                                '-',
                                '-',
                                '-',
                                '-',
                            ];
                        } else {
                            $table[$i][5] = $table[$i][5] * 100 . '%';
                        }*/
                        $this->fillData($i);
                    };
                    break;
                default:
            }

            ksort($this->amchart, SORT_FLAG_CASE | SORT_NATURAL);
            krsort($this->table, SORT_FLAG_CASE | SORT_NATURAL);
            $origin['amchart'] = $this->amchart;
            $origin['table'] = $this->table;
            return $origin;
        }
    }


    /**
     * 补全数据公共部分
     * @param $var
     */
    protected function fillData($var) {
        //补全图表所用数据
        if (empty($this->amchart[$var])) {
            $this->amchart[$var] = [
                'new_account' => 0,
                'active_account' => 0,
                'amount' => 0,
                'login_times' => 0,
                'rate' => 0,
                'arpu' => 0,
                'arppu' => 0,
                'category' => $var,
            ];
        }

        //补全table所用数据
        if (empty($this->table[$var])) {
            $this->table[$var] = [
                $var,
                '-',
                '-',
                '-',
                '-',
                '-',
                '-',
                '-',
            ];
        } else {
            $this->table[$var][5] = $this->table[$var][5] * 100 . '%';
        }
    }



}