<?php

/**
 * 数据分析
 */
namespace MyApp\Controllers;

use Phalcon\Http\Request;                   // 表单参数获取组件 refer: https://docs.phalconphp.com/en/latest/api/Phalcon_Http_Request.html
use Phalcon\Filter;                         // 表单验证过滤组件 refer: https://docs.phalconphp.com/en/latest/reference/filter.html

use Phalcon\Mvc\Dispatcher;
use MyApp\Models\Ltv;
use MyApp\Models\ReportDay;
use MyApp\Models\PaymentDay;
use MyApp\Models\Area;
use MyApp\Models\Backend\States;

class AnalysisController extends ControllerBase
{
    private $_request;                      // 私有变量
    private $_filter;
    private $_days;

    private $_Ltv;                          // ltv 模型
    private $_ReportDay;
    private $_PaymentDay;
    private $_Area;
    private $_States;

    public function initialize()
    {
        parent::initialize();

        $this->_Ltv = new Ltv();
        $this->_ReportDay = new ReportDay();
        $this->_PaymentDay = new PaymentDay();
        $this->_Area = new Area();
        $this->_States = new States();

        $this->_request = new Request();
        $this->_filter = new Filter();

        $this->_days = [
            "0",
            "1",
            "2",
            "3",
            "4",
            "5",
            "6",
            "7",
            "8",
            "9",
            "10",
            "11",
            "12",
            "13",
            "14",
            "21",
            "30",
            "60"
        ];

        $this->_channelCode = [
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
    }

    public function ltvAction()
    {
        $conditions = $this->mergeQueryCond();                                          // 查询条件获取

        $start_time = strtotime($conditions['where']['date']['start']);        //获取开始时间 并转化成时间戳
        $end_time = strtotime($conditions['where']['date']['end']);            //获取结束时间 并转化为时间戳
        $app_id = $conditions['where']['app_id']['value'];

        $where['start'] = $conditions['where']['date']['start'];
        $where['end'] = $conditions['where']['date']['end'];
        $where['app_id'] = $app_id;

        $result = $this->_Ltv->newGetDatas($where);

        // 获取基本数据(数据以days、date为键归类)
        $ltv_days = array_column_index($result, 'days');
        $ltv_date = array_column_index($result, 'date');
        ksort($ltv_days);
        ksort($ltv_date);

        $reduced = [];

        foreach ($ltv_date as $key => $item) {
            $ltvdata = [];
            foreach ($item as $k => $v) {
                if (!in_array($key, $ltvdata)) {
                    $ltvdata[] = $key;
                }

                if ($v['new_account'] == 0) {
                    $ltvdata[$v['days'] + 2] = '-';
                }
                elseif ($v['amount'] == 0) {
                    $ltvdata[$v['days'] + 2] = 0;
                }
                else {
                    switch ($v['days']) {
                        case 21:
                            $ltvdata[17] = round($v['amount'] / $v['new_account'], 3);
                            break;
                        case 30:
                            $ltvdata[18] = round($v['amount'] / $v['new_account'], 3);
                            break;
                        case 60:
                            $ltvdata[19] = round($v['amount'] / $v['new_account'], 3);
                            break;
                        default:
                            $ltvdata[$v['days'] + 2] = round($v['amount'] / $v['new_account'], 3);
                    }
                }
            }
            $ltvdata[1] = $v['new_account'];
            ksort($ltvdata);
            $reduced[$key] = $ltvdata;
        }

        $ltv_origin = $ltv_tmp = [];
        foreach ($reduced as $key => $item) {
            if (count($item) < count($this->_days)) {
                $count = count($this->_days) + 2;
                for ($i = 0; $i < $count; $i++) {
                    if (empty($item[$i])) {
                        $item[$i] = '-';
                    }
                }
            }
            ksort($item);
            $ltv_origin[$key] = $item;

            $tmp = array(
                "id"    => $app_id . '_' . $key,
                "field" => $key . '_' . $app_id,
                "title" => $key
            );

            $ltv_tmp[] = $tmp;
        }

        for ($i = $start_time; $i <= $end_time; $i += 86400) {
            $date = date('Y-m-d', $i);
            if (empty($ltv_origin[$date])) {


                $ltv_origin[$date][] = $date;
                $count = count($this->_days) + 1;
                for ($j = 0; $j < $count; $j++) {
                    $ltv_origin[$date][] = '-';
                }
            }

        }
        ksort($ltv_origin);
        $ltv_final = array_values($ltv_origin);

        $ltv = [];
        foreach ($this->_days as $key => $item) {
            $tmp1 = [];
            $tmp1["category"] = "+ {$item} 日";

            if (!empty($ltv_days[$item])) {
                foreach ($ltv_days[$item] as $k => $v) {
                    if ($v['new_account'] == 0 || $v['amount'] == 0) {
                        $tmp1[$v["date"] . '_' . $app_id] = 0;
                    }
                    else {
                        $tmp1[$v["date"] . '_' . $app_id] = round($v['amount'] / $v['new_account'], 2);
                    }

                }
            }
            $ltv[$key] = $tmp1;
        }

        $this->view->ltv_tab = json_encode($ltv_final);       // 表格数据
        $this->view->ltv = json_encode($ltv);                 // 生成数据图的数据
        $this->view->ltv_tmp = json_encode($ltv_tmp);         // 数据图模板数据

        $this->view->start_time = date('Y-m-d', $start_time);
        $this->view->end_time = date('Y-m-d', $end_time);

        $this->view->pick('analysis/ltv');
    }

    public function payAction()
    {
        $base = $this->mergeQueryCond();                                            // 查询条件获取

        $start_time = strtotime($base['where']['date']['start']);        //获取开始时间 并转化成时间戳
        $end_time = strtotime($base['where']['date']['end']);            //获取结束时间 并转化为时间戳

        $payment_cond = $base;

        // 查询条件组装
        $payment_cond["field"] = '`date`,channel,device,
            sum(`new_account`) as `new_account`,
            sum(`count_account`) as `count_account`,
            sum(`times`) as `times`,
            sum(`amount`) as `amount`';
        $payment_cond["joint"] = 'GROUP BY `date`,`channel`,`device`';

        $result = $this->_PaymentDay->getDatas($payment_cond);

        // 基础数据
        $payment = array_column_index($result, 'date');

        $paychannel = array_column_index($result, 'channel');
        $paydevice = array_column_index($result, 'device');

        $channeltotle = $this->payPieData($paychannel);
        $deviceltotle = $this->payPieData($paydevice);


        // 查询条件组装
        $report_cond = $base;
        $report_cond["field"] = "`date`,sum(`active_account`) as `active_account`";
        $report_cond["joint"] = 'GROUP BY `date`,`channel`,`device`';

        // 活跃数据
        $report = array_column_index($this->_ReportDay->getDatas(
            $report_cond
        ), 'date');

        // 判断数组维度, 3维数组说明数据未选中某一渠道, 需要将所有渠道、设备数据进行累加
//        if (depthOfArray($report) == 3 && depthOfArray($payment) == 3) {
        $report = $this->depthCrop($report);
        $payment = $this->depthCrop($payment);
//        }


        //判断日期是否完整
        for ($i = $end_time; $i >= $start_time; $i -= 86400) {
            if (empty($payment[date('Y-m-d', $i)])) {
                $payment[date('Y-m-d', $i)] = [];
            }
        }
        ksort($payment);

        // 将活跃用户数据拼接到 $origin 数组中
        $payTab = [];
        foreach ($payment as $key => $value) {
            if (!empty($value)) {
                $payment[$key]["date"] = $key;
                if (isset($report[$key])) {
                    $payment[$key]["rate"] = round($payment[$key]["count_account"] / $report[$key]["active_account"],
                            2) * 100 . '%';   // 付费率
                    $payment[$key]["arpu"] = round($payment[$key]["amount"] / $report[$key]["active_account"],
                        2);          // ARPU
                    $payment[$key]["arppu"] = round($payment[$key]["amount"] / $payment[$key]["count_account"],
                        2);         // ARPU
                }
                else {
                    $payment[$key]["rate"] = '-';   // 付费率
                    $payment[$key]["arpu"] = '-';          // ARPU
                    $payment[$key]["arppu"] = '-';         // ARPU
                }

                unset($payment[$key]['channel']);
                unset($payment[$key]['device']);

                $item['category'] = $key;
                $item['new_account'] = empty($payment[$key]["new_account"]) ? 0 : $payment[$key]["new_account"];
                $item['count_account'] = empty($payment[$key]["count_account"]) ? 0 : $payment[$key]["count_account"];
                $item['times'] = empty($payment[$key]["times"]) ? 0 : $payment[$key]["times"];
                $item['amount'] = empty($payment[$key]["amount"]) ? 0 : $payment[$key]["amount"];
            }
            else {
                $payment[$key]["date"] = $key;
                $payment[$key]["new_account"] = '-';
                $payment[$key]["count_account"] = '-';
                $payment[$key]["times"] = '-';
                $payment[$key]["amount"] = '-';
                $payment[$key]["rate"] = '-';
                $payment[$key]["arpu"] = '-';
                $payment[$key]["arppu"] = '-';

                $item['category'] = $key;
                $item['new_account'] = 0;
                $item['count_account'] = 0;
                $item['times'] = 0;
                $item['amount'] = 0;
            }
            $payTab[] = $item;
        }


        // 将数据转换成前端认可的格式
        $tab_data = [];
        foreach ($payment as $key => $value) {
            array_push($tab_data, array_values($value));
        }

        $this->view->tab_data = json_encode($tab_data);
        $this->view->channeltotle = json_encode($channeltotle);
        $this->view->deviceltotle = json_encode($deviceltotle);
        $this->view->payTab = json_encode($payTab);

        $this->view->start_time = date('Y-m-d', $start_time);
        $this->view->end_time = date('Y-m-d', $end_time);

        $this->view->pick('analysis/pay');
    }

    private function payPieData($data)
    {
        if (empty($data)) {
            return array();
        }
        foreach ($data as $key => $item) {
            $totle = 0;
            foreach ($item as $value) {
                $totle += $value['amount'];
            }
            $key = array_key_exists($key, $this->_channelCode) ? $this->_channelCode[$key]:$key;
            $paytotle[$key] = $totle;
        }

        arsort($paytotle);

        if (count($paytotle) > 5) {
            $keyArr = array_keys($paytotle);
            $paytotle['other'] = 0;
            for ($i = 5; $i < count($keyArr); $i++) {
                $paytotle['other'] = $paytotle['other'] + $paytotle[$keyArr[$i]];
                unset($paytotle[$keyArr[$i]]);
            }
        }

        foreach ($paytotle as $key => $item) {
            $result[] = array(
                'category' => empty($key) ? 'default' : $key,
                'column-1' => $item
            );
        }

        return $result;
    }


    public function zoneAction()
    {
        $base = $this->mergeQueryCond();                                            // 查询条件获取

        $start_time = strtotime($base['where']['date']['start']);        //获取开始时间 并转化成时间戳
        $end_time = strtotime($base['where']['date']['end']);            //获取结束时间 并转化为时间戳

        // 查询条件组装
        $area_cond = $base;
        $area_cond["field"] = '`area`,
        sum(`new_device`) as `new_device`,
        sum(`pay_device`) as `pay_device`,
        sum(`pay_count`) as `pay_count`,
        sum(`pay_amount`) as `pay_amount`';
        $area_cond["joint"] = 'GROUP BY `area`';

        $zones_info = $this->_Area->getDatas($area_cond);
        $new_device = $this->zonePieData($zones_info, 'new_device');
        $pay_device = $this->zonePieData($zones_info, 'pay_device');
        $pay_count = $this->zonePieData($zones_info, 'pay_count');
        $pay_amount = $this->zonePieData($zones_info, 'pay_amount');                                      // 存储饼图所需数据
        $zones_tab = $this->zonePieData($zones_info, 'new_device',
            true);                                             // 存储表格所需的数据

        $this->view->new_device = json_encode($new_device);
        $this->view->pay_device = json_encode($pay_device);
        $this->view->pay_count = json_encode($pay_count);
        $this->view->pay_amount = json_encode($pay_amount);
        $this->view->zones = json_encode($zones_tab);

        $this->view->start_time = date('Y-m-d', $start_time);
        $this->view->end_time = date('Y-m-d', $end_time);

        $states = $this->_States->backDatas();
        $this->view->states = $states;
        $this->view->pick('analysis/zone');
    }

    private function zonePieData($data, $name, $tab = false)
    {
        if (empty($data)) {
            return array();
        }

        $zone_ch = array(
            "US"  => "美国",
            "VIE" => "越南",
            "TR"  => "土耳其",
            "EN"  => "英国",
            "TH"  => "泰国",
            "PHI" => "菲律宾",
            "RU"  => "俄罗斯",
            "CA"  => "加拿大",
            "BR"  => "巴西",
            "JP"  => "日本"
        );

        foreach ($data as $key => $item) {
            $totle = 0;
            $totle += $item[$name];
            $paytotle[$key] = $totle;

            $area = $item['area'];
            $item['area'] = isset($zone_ch[$area]) ? $zone_ch[$area] : $area;

            $zones_tab[] = array_values($item);
        }

        if ($tab) {
            return $zones_tab;
        }

        arsort($paytotle);

        if (count($paytotle) > 9) {
            $keyArr = array_keys($paytotle);
            $paytotle['other'] = 0;
            for ($i = 9; $i < count($keyArr); $i++) {
                $paytotle['other'] = $paytotle['other'] + $paytotle[$keyArr[$i]];
                unset($paytotle[$keyArr[$i]]);
            }
        }

        foreach ($paytotle as $key => $item) {
            if ($key !== 'other') {
                $area = $data[$key]['area'];
                $value[] = array(
                    "id"       => $area,
                    "category" => isset($zone_ch[$area]) ? $zone_ch[$area] : $area,
                    "value"    => $item
                );

            }
            else {
                $value[] = array(
                    "id"       => 'other',
                    "category" => '其他',
                    "value"    => $item
                );
            }
        }

        return $value;
    }

    /**
     * des: 渠道详情
     */
    public function channelDetailAction()
    {
        $base = $this->mergeQueryCond();                                            // 查询条件获取
        $reqs = $this->_request->get();

        $start_time = strtotime($base['where']['date']['start']);        //获取开始时间 并转化成时间戳
        $end_time = strtotime($base['where']['date']['end']);            //获取结束时间 并转化为时间戳

        if (!empty($reqs["area"])) {
            $base['where']["area"] = array(
                "symbol" => '=',
                "value"  => $reqs["area"],
            );
        }

        // 查询条件组装
        $area_cond = $base;
        $area_cond["field"] = 'date,
        sum(`new_device`) as `new_device`,
        sum(`pay_device`) as `pay_device`,
        sum(`pay_count`) as `pay_count`,
        sum(`pay_amount`) as `pay_amount`';
        $area_cond["joint"] = 'GROUP BY `date`';

        $result = $this->_Area->getDatas($area_cond);

        $zones = array_column_index($result, 'date');

        //判断日期是否完整
        for ($i = $end_time; $i >= $start_time; $i -= 86400) {
            if (empty($zones[date('Y-m-d', $i)])) {
                $zones[date('Y-m-d', $i)] = '';
            }
        }
        ksort($zones);

        $zonesTab = [];
        foreach ($zones as $key => $value) {
            if (!empty($value)) {
                $item['category'] = $key;
                $item['new_device'] = $value[0]["new_device"];
                $item['pay_device'] = $value[0]["pay_device"];
                $item['pay_count'] = $value[0]["pay_count"];
                $item['pay_amount'] = $value[0]["pay_amount"];
            }
            else {
                $zones[$key][] = array(
                    "date"       => $key,
                    "new_device" => '-',
                    "pay_device" => '-',
                    "pay_count"  => '-',
                    "pay_amount" => '-',
                );

                $item['category'] = $key;
                $item['new_device'] = 0;
                $item['pay_device'] = 0;
                $item['pay_count'] = 0;
                $item['pay_amount'] = 0;
            }

            $zonesTab[] = $item;
        }

        $tab_data = [];
        foreach ($zones as $key => $value) {
            array_push($tab_data, array_values($value[0]));
        }

        $this->view->tab_data = json_encode($tab_data);
        $this->view->zonesTab = json_encode($zonesTab);

        $this->view->pick('analysis/channelDetail');
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

    /**
     * des: 组装查询条件
     * @return array
     */
    protected function mergeQueryCond()
    {
        ini_set("date.timezone", 'PRC');

        $router = $this->di['router'];                              // 获取路由信息
        // $router->getControllerName(), $router->getActionName()

        // 获取时间戳
        $today_stamp = time();

//        if($router->getActionName() == 'ltv'){
//            $before_7day = date('Y-m-d', strtotime('-8 day', $today_stamp)) . ' 23:59:59';           // 以当天向前推算7天的时间(时间初始值)
//            $before_14day = date('Y-m-d', strtotime('-15 day', $today_stamp)) . ' 00:00:00';         // 前14天(时间初始值)
//        }else{
        $before_7day = date('Y-m-d', strtotime('-1 day', $today_stamp)) . ' 23:59:59';           // 以当天向前推算7天的时间(时间初始值)
        $before_14day = date('Y-m-d', strtotime('-8 day', $today_stamp)) . ' 00:00:00';         // 前14天(时间初始值)
//        }

        // 默认时间的查询条件(无 POST 传值启用)
        $base = array(
            "date" => array(
                "type"  => "range",
                "start" => $before_14day,
                "end"   => $before_7day
            )
        );

        // 用户配置查询条件
        if ($this->_request->isPost()) {
            $reqs = $this->_request->get();

            $date = dateCompare($reqs["start_time"], $reqs["end_time"]);

            if (!empty($date)) {
                $base["date"]["start"] = $date[0];
                $base["date"]["end"] = date('Y-m-d', strtotime($date[1])) . ' 23:59:59';
            }

            // 根据方法类型只输出需要的查询条件
            switch ($router->getActionName()) {
                case "zone":
                    // 地区
                    if (!empty($reqs["choice_states"])) {
                        $base["area"] = array(
                            "type"  => "in",                            // 使用 in 方法必须使用 <input type="checkbox" /> 传入的数组
                            "value" => $reqs["choice_states"],
                        );
                    }
                    break;
                case "pay":
                case "ltv":
                    // 设备条件
                    if (!empty($reqs["choice_device"])) {
                        $base["device"] = array(
                            "type"  => "in",                            // 使用 in 方法必须使用 <input type="checkbox" /> 传入的数组
                            "value" => $reqs["choice_device"],
                        );
                    }

                    // 渠道条件
                    if (!empty($reqs["choice_channel"])) {
                        $base["channel"] = array(
                            "type"  => "in",
                            "value" => $reqs["choice_channel"],
                        );
                    }
                    break;
                default:
            }
        }

        // 默认 app 筛选
        $default_app = $this->session->get('default_app');
        $default_channeltemp = $this->session->get('resources')['allow_channel'];
        if($default_channeltemp)
        {
            foreach ($default_channeltemp as $v)
            {
                $default_channel[] = $v['channel'];
            }
            $base['channel'] = [
                'type' => 'in',
                'value' => $default_channel,
            ];
        }

        if (!empty($default_app["game_id"])) {
            $base["app_id"] = array(
                "symbol" => '=',
                "value"  => $default_app["game_id"]
            );
        }

        return array("where" => $base);
    }
}