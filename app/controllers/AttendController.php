<?php

/**
 * 参与和留存
 */
namespace MyApp\Controllers;


use Phalcon\Mvc\Dispatcher;
use MyApp\Models\Attend;
use MyApp\Models\ReportCommon;

class AttendController extends ControllerBase
{

    private $_attendModel;
    private $_reportCommon;
    private $_days;
    private $_keep_days;
    public static $img_days = [1, 2, 6];
    public $image_1days = [];  //运营定义的次留，对应程序的1日
    public $image_2days = [];  //运营定义的3日留存，对应程序的2日
    public $image_6days = [];  //运营定义的7日留存，对应程序的6日

    public function initialize()
    {
        parent::initialize();
        $this->_attendModel = new Attend();
        $this->_reportCommon = new ReportCommon();
        $this->_days = ["1", "2", "3", "4", "5", "6", "7", "14", "30", "60"];
        $this->_keep_days = ["1", "2", "3", "4", "5", "6", "13", "29", "59"];

    }

    // 新增
    public function newAction()
    {
        $conditions = $this->mergeQueryCond();
        //获取开始时间 需要用此时间检查数据是否完整 并回传给前端
        $start_time = strtotime($conditions['where']['date']['start']);
        $end_time = strtotime($conditions['where']['date']['end']);

        //新增渠道设备 原始数据
        $new_origin = $this->reportData($conditions, "report_day");

        //新增设备  原始数据
        $new_device = $this->deviceData($conditions, "report_day");

        //图表数据 原始数据
        $new_image = $this->imageData($conditions, "report_day");

        //检测时间是否完整
        for ($i = $end_time; $i >= $start_time; $i -= 86400) {
            if (empty($new_image[date('Y-m-d', $i)])) {
                $new_image[date('Y-m-d', $i)] = '';
            }
        }
        ksort($new_image);

        $report = [];
        $device = [];
        // 判断数组维度, 3维数组说明数据未选中某一渠道, 需要将所有渠道、设备数据进行累加
        if (depthOfArray($new_origin) == 3 && depthOfArray($new_device) == 3) {
            $report = $this->depthCrop($new_origin);
            $device = $this->deviceDepthCrop($new_device);
        }

        //比较数据
        $account = $this->compare($report, 'device');

        //组装新增渠道设备(饼状图)
        $new_account = $this->installPieData($account);

        //组装新增设备数据(饼状图)
        $new_device = $this->installPieData($device);

        //组装线形图
        $image = $this->imageDepthCrop($new_image);

        $this->view->new_account = json_encode($new_account);
        $this->view->new_device = json_encode($new_device);
        $this->view->image_data = json_encode($image);

        $this->view->start_time = date('Y-m-d', $start_time);
        $this->view->end_time = date('Y-m-d', $end_time);

        $this->view->pick("attend/new");
    }

    // 活跃
    public function activeAction()
    {
        $conditions = $this->mergeQueryCond();
        $start_time = strtotime($conditions['where']['date']['start']);
        $end_time = strtotime($conditions['where']['date']['end']);

        //组装sql 按照channel查出活跃
        $conditions['joint'] = "GROUP BY `channel`, `device`, `date` ORDER BY `date`";
        $conditions['field'] = "`date`, `channel`, `device`, SUM(`active_account`) as active_account, SUM(`active_device`) as active_device";

        //渠道饼状图所用数据源
        $active_channel = array_column_index($this->_reportCommon->getDatas(
            $conditions,
            "report_day"
        ), 'channel');

        //活跃设备饼状图所用数据源
        $active_os = array_column_index($this->_reportCommon->getDatas(
            $conditions,
            "report_day"
        ), 'device');

        //活跃图表所用数据源
        $active_image = array_column_index($this->_reportCommon->getDatas(
            $conditions,
            "report_day"
        ), 'date');

        //判断数据是否完整
        for ($i = $end_time; $i >= $start_time; $i -= 86400) {
            if (empty($active_image[date('Y-m-d', $i)])) {
                $active_image[date('Y-m-d', $i)] = '';
            }
        }
        ksort($active_image);

        $channel = [];
        $os = [];
        // 判断数组维度, 3维数组说明数据未选中某一渠道, 需要将所有渠道、设备数据进行累加
        if (depthOfArray($active_channel) == 3 && depthOfArray($active_os) == 3) {
            $channel = $this->activeDepthCrop($active_channel, 'channel');
            $os = $this->activeDepthCrop($active_os, 'os');
        }

        //比较数据
        $channel_finnal = $this->compare($channel, 'active_device');

        //组装新增渠道设备(饼状图)
        $channel_data = $this->installPieData($channel_finnal);

        //组装新增设备数据(饼状图)
        $os_data = $this->installPieData($os);

        //组装线形图
        $image = $this->imageActiveDepthCrop($active_image);

        $this->view->channel_data = json_encode($channel_data);
        $this->view->os_data = json_encode($os_data);
        $this->view->image_data = json_encode($image);

        $this->view->start_time = date('Y-m-d', $start_time);
        $this->view->end_time = date('Y-m-d', $end_time);
        $this->view->pick("attend/active");
    }

    // 留存
//    public function stayAction()
//    {
//        //图表需要展现的留存天数
//        $image_days = [ 1, 3, 7, 30];
//
//        //sql = select `date`, SUM(count_device)  FROM `retention` WHERE `date` BETWEEN '2017-06-21' AND '2017-06-26'  AND
//        //app_id=1001000 GROUP BY `date`, `channel`, `days` ORDER BY `days` DESC` //根据date倒序取数据
//        $conditions = $this->mergeQueryCond();
//        $start_time = strtotime($conditions['where']['date']['start']);        //获取开始时间 并转化成时间戳
//        $end_time = strtotime($conditions['where']['date']['end']);            //获取结束时间 并转化为时间戳
//
//        $conditions['where']["days"] = array(
//            "symbol" => '=',
//            "value"  => 0
//        );
//
//        $conditions['joint'] = "GROUP BY `date`, `channel`, `days` ORDER BY `days`";
//        $conditions['field'] = "`channel`,`date`, `days`,sum(count_device) as DeCount";
//
//        //获取基本数据,数据以统计时间为键归类
//        $stay_origin = array_column_index($this->_attendModel->getDatas($conditions), 'date');
//
//        if (!empty($stay_origin)) {             //处理没有数据时候 不会报错
//            $stay_final = [];
//
//            //默认是所有渠道的设备数按留存天数累加
//            foreach ($stay_origin as $k => $detail) {
//                foreach ($detail as $k => $v) {
//                    if (empty($tmp[$v['date'] . '_' . $v['days']])) {
//                        $tmp[$v['date'] . '_' . $v['days']] = $v;
//                        $tmp[$v['date'] . '_' . $v['days']]['stay_Device'] = $v['DeCount'];
//                    }
//                    else {              //设备数累加按留存天数
//                        if ($tmp[$v['date'] . '_' . $v['days']]['days'] == $v['days']) {
//                            $tmp[$v['date'] . '_' . $v['days']]['stay_Device'] = $tmp[$v['date'] . '_' . $v['days']]['stay_Device'] + $v['DeCount'];
//                            $tmp[$v['date'] . '_' . $v['days']]['channel'] = 'all';     //以免误导
//                            unset($tmp[$v['date'] . '_' . $v['days']]['DeCount']);      //以免误导 删除跳板数组中的原设备留存
//                        }
//                    }
//                }
//                $stay_final = array_merge_smart($stay_final, $tmp);
//            }
//            //以 统计时间为键,整理每日的所有设备数
//            $stay_device = array_column_index($stay_final, 'date');
//
//            ksort($stay_device);
//
//            //计算留存率
//            foreach ($stay_device as $key => $detail) {
//                $total_device = $detail[0]['stay_Device'];
//                foreach ($detail as $k => $v) {
//                    if ($total_device != 0) {               //如果分母(total_device) 不为0时才进行计算 否则报错
//                        $stay_device[$key][$k]['stay_ratio'] = number_format($v['stay_Device'] / $total_device, 2, '.',
//                            '');      //四舍五入 并且保留两位小数
//                    }else{                                  //如果分母为0 则留存率为0
//                        $stay_device[$key][$k]['stay_ratio'] = 0;
//                    }
//                }
//            }
//
//            //拼装图表用数据
//            //[ ['2016-05-12',7],['2016-05-11',6.5],['2016-05-10',12.5] ]
//            foreach ($stay_device as $key => $detail) {
//                foreach ($detail as $k => $v) {
//                    $image_data[$key][] = [
//                        'date'       => $v['date'],
//                        'days'       => $v['days'],
//                        'device'     => $v['stay_Device'],
//                        'stay_ratio' => $v['stay_ratio']
//                    ];
//                }
//            }
//
//            //判断日期是否完整
//            for ($i = $end_time; $i >= $start_time; $i -= 86400) {
//                if (empty($image_data[date('Y-m-d', $i)])) {
//                    $image_data[date('Y-m-d', $i)] = '';
//                }
//            }
//
//            //由于图表只展示 1,3,7,30 留存 (目前数据没有30日留存)
//            foreach ($image_data as $k => $detail) {
//                if (is_array($detail)) {
//                    foreach ($detail as $key => $v) {
//                        if (in_array($image_data[$k][$key]['days'], $image_days)) {     //排除没有1,3,7日留存的日期
//                            $image_final[$k][] = $v;
//                        }else{
//                            $image_final[$k][] = "";
//                        }
//                    }
//                }
//                else {                              //无此日数据
//                    $image_final[$k] = $detail;
//                }
//            }
//
//            ksort($image_final);
//
//            $image_1days = [];
//            $image_3days = [];
//            $image_7days = [];
//
//            foreach ($image_final as $k => $detail) {
//                if (is_array($detail)) {
//                    foreach ($detail as $key => $v) {
//                        if (is_array($v)) {         //此日有数据 但是没有1,3,7日留存数据
//                            if ($v["days"] == '1') {//按照留存天数拆分数组 差30天的
//                                $data = [
//                                    $v['date'],
//                                    $v['stay_ratio']
//                                ];
//                                array_push($image_1days, $data);
//                            }
//                            else {
//                                $no_data = [
//                                    $k,
//                                    0
//                                ];
//                                array_push($image_1days, $no_data);
//                            }
//
//                            if ($v['days'] == '3') {
//                                $data = [
//                                    $v['date'],
//                                    $v['stay_ratio']
//                                ];
//                                array_push($image_3days, $data);
//                            }
//                            else {
//                                $no_data = [
//                                    $k,
//                                    0
//                                ];
//                                array_push($image_3days, $no_data);
//                            }
//
//                            if ($v['days'] == '7') {
//                                $data = [
//                                    $v['date'],
//                                    $v['stay_ratio']
//                                ];
//                                array_push($image_7days, $data);
//                            }
//                            else {
//                                $no_data = [
//                                    $k,
//                                    0
//                                ];
//                                array_push($image_7days, $no_data);
//                            }
//                        }else {                                  //向图表数据源压入没有数据的天数
//                            $no_data = [
//                                $k,
//                                0
//                            ];
//                            array_push($image_1days, $no_data);
//                            array_push($image_3days, $no_data);
//                            array_push($image_7days, $no_data);
//                        }
//                    }
//                }
//                else {                                  //向图表数据源压入没有数据的天数
//                    $no_data = [
//                        $k,
//                        0
//                    ];
//                    array_push($image_1days, $no_data);
//                    array_push($image_3days, $no_data);
//                    array_push($image_7days, $no_data);
//                }
//            }
//
//            //组装dataBases数据
//            //[ '2017-03-28', '645', '0', '0', '665', '645', '0', '0', '0', '0', '0.00', '0.00%', '0.00%', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00' ],[ '2017-03-27', '645', '0', '0', '665', '645', '0', '0', '0', '0', '0.00', '0.00%', '0.00%', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00' ]
//            $table_data = [];
//            foreach ($image_data as $k => $detail) {
//                if (is_array($detail)) {
//                    foreach ($detail as $key => $v) {
//                        $table_data[$k][$key]['date'] = $v['date'];
//                        $table_data[$k][$key]['device'] = $v['device'];
//                        $table_data[$k][$key]['days'] = $v['days'];
//                        $table_data[$k][$key]['stay_ratio'] = $v['stay_ratio'] * 100 . '%';
//                    }
//                }
//                else {                                              //代表此天没有数据
//                    $table_data[$k] = [];
//                }
//            }
//
//           foreach ($table_data as $key => $value){
//               if (!empty($value)) {
//                   $table_finnal[$key][] = $key;
//                   $table_finnal[$key][] = $value[0]['device'];
//                   foreach ($value as $k => $v) {
//                       $table_finnal[$key][] = $v['stay_ratio'];
//                   }
//               }
//           }
//            //dd($table_finnal, $table_data);
//            foreach ($table_finnal as $key => $item) {
//                if (count($item) < count($this->_days)) {
//                    $count = count($this->_days) + 1;
//                    for ($i = 1; $i <= $count; $i++) {
//                        if (empty($item[$i])) {
//                            $item[$i] = '-';
//                        }
//                    }
//                }
//                ksort($item);
//                $tab_data[$key] = $item;
//                //删除每天的0日留存
//                unset($tab_data[$key]['2']);
//            }
//            //检查表格数据是否完整
//            for($i = $end_time; $i >= $start_time; $i -= 86400 ){
//                if (empty($tab_data[date('Y-m-d', $i)])){
//                    $tab_data[date('Y-m-d', $i)] = [
//                        date('Y-m-d', $i),
//                        '-',
//                        '',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-',
//                        '-'
//                    ];
//                }
//            }
//
//            ksort($tab_data);
//        }
//        else {
//            $image_1days = [];
//            $image_3days = [];
//            $image_7days = [];
//            $tab_data = [];
//            //如果没有数据 表格数据为 [date, '-', '-' .....];
//            for ($date = $end_time; $date >= $start_time; $date -= 86400) {
//                $tab_data[] = [
//                    date('Y-m-d', $date),
//                    '-',
//                    '',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-',
//                    '-'
//                ];
//            }
//        }
//
//        //dd(json_encode($image_1days), json_encode($image_3days), json_encode($image_7days));
//        //留存图 所需数据
//        $this->view->image_1days = json_encode($image_1days);
//        $this->view->image_3days = json_encode($image_3days);
//        $this->view->image_7days = json_encode($image_7days);
//        $this->view->tab_data = json_encode(array_values($tab_data));
//
//        $this->view->start_time = date('Y-m-d', $start_time);
//        $this->view->end_time = date('Y-m-d', $end_time);
//
//        $this->view->pick("attend/stay");
//
//    }

    // 留存
    public function stayAction()
    {

        $conditions = $this->mergeQueryCond();
        $conditions['joint'] = "GROUP BY `date`, `channel`, `days` ORDER BY `days`";
        $conditions['field'] = "`channel`,`date`, `days`,sum(count_device) as DeCount";

        //获取基本数据,数据以统计时间为键归类
        $stay_origin = array_column_index($this->_attendModel->getDatas($conditions), 'date');

        $stay_date = $this->processData($stay_origin);
        $tab = array_values($stay_date);

        //处理日期
        $start_time = substr($conditions['where']['date']['start'], 0, 10);        //获取开始时间 并转化成时间戳
        $end_time = substr($conditions['where']['date']['end'], 0, 10);            //获取结束时间 并转化为时间戳
        $dateArr = $this->getDateArrAction($start_time, $end_time);
        $tab_data = [];
        foreach ($tab as $k => $d) {
            $tab_data[$k] = $d;
            array_unshift($tab_data[$k], $dateArr[$k]);
        }

        $this->view->setVars(
            [
                'tab_data' => json_encode($tab_data),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'image_1days' => json_encode($this->image_1days),
                'image_3days' => json_encode($this->image_2days),
                'image_7days' => json_encode($this->image_6days),
            ]
        );

        $this->view->pick("attend/stay");

    }

    //账号留存
    public function stayUserAction()
    {
        $conditions = $this->mergeQueryCond();
        $conditions['joint'] = "GROUP BY `date`, `channel`, `days` ORDER BY `date` DESC,`days`";
        $conditions['field'] = "`channel`,`date`, `days`,sum(count_device) as DeCount";

        //获取基本数据,数据以统计时间为键归类
        $stay_origin = array_column_index($this->_attendModel->getDatas($conditions, 'retention_user'), 'date');
        $stay_date = $this->processData($stay_origin);
        $tab = array_values($stay_date);

        //处理日期
        $start_time = substr($conditions['where']['date']['start'], 0, 10);        //获取开始时间 并转化成时间戳
        $end_time = substr($conditions['where']['date']['end'], 0, 10);            //获取结束时间 并转化为时间戳
        $dateArr = $this->getDateArrAction($start_time, $end_time);
        $tab_data = [];
        foreach ($tab as $k => $d) {
            $tab_data[$k] = $d;
            array_unshift($tab_data[$k], $dateArr[$k]);
        }

        $this->view->setVars(
            [
                'tab_data' => json_encode($tab_data),
                'start_time' => $start_time,
                'end_time' => $end_time,
                'image_1days' => json_encode($this->image_1days),
                'image_3days' => json_encode($this->image_2days),
                'image_7days' => json_encode($this->image_6days),
            ]
        );

        $this->view->pick("attend/stayuser");
    }


    /**
     * 处理数据
     * @param $stay_origin
     */
    public function processData($stay_origin)
    {
        if (!empty($stay_origin)) {             //处理没有数据时候 不会报错

            foreach ($stay_origin as $key => $origin) {
                $arr = [];
                foreach ($origin as $k => $v) {
                    if (!empty($arr[$v['days']])) {
                        $arr[$v['days']] += $v['DeCount'];
                    } else {
                        $arr[$v['days']] = $v['DeCount'];
                    }
                }

                $stay_date[$key] = $arr;
            }

            ksort($stay_date);

            foreach ($stay_date as $key => $value) {

                $max = max($this->_keep_days);
                for ($i = 1; $i <= $max; $i++) {

                    if (!in_array($i, $this->_keep_days)) {
                        continue;
                    }
                    if (isset($value[$i])) {
                        $stay_date[$key][$i] = $value[0] == 0 ? '0.00%' : round($value[$i] / $value[0], 4) * 100 . '%';
                    } else {
                        $stay_date[$key][$i] = '-';
                    }

                    //1日，3日，7日数据用于绘制曲线图，需单独处理
                    if (in_array($i, self::$img_days)) {
                        $item = [];
                        $item[] = $key;
                        if ($stay_date[$key][0] == 0) {
                            $item[] = 0;
                        } else {
                            $item[] = $stay_date[$key][$i] == '-' ? 0 : round($value[$i] / $stay_date[$key][0], 2);
                        }
                        $a = "image_{$i}days";
                        array_push($this->$a, $item);
                    }
                }
                ksort($stay_date[$key]);
            }

            return $stay_date;
        }
        return [];
    }


    protected function mergeQueryCond()
    {
        //默认条件
        $before_1day = date('Y-m-d', strtotime('-1 day'));
        $before_7day = date('Y-m-d', strtotime('-7 day'));

        $base = array(
            "date" => array(
                "type" => "range",
                "start" => $before_7day,
                "end" => $before_1day,
            )
        );

        //如果用户提交查询条件 (有POST 传值)
        if ($this->request->isPost()) {
            $req = $this->request->get();
            $date = dateCompare($req["start_time"], $req["end_time"]);

            if (!empty($date)) {
                $base['date']['start'] = $date[0];
                $base['date']['end'] = date('Y-m-d', strtotime($date[1])) . ' 23:59:59';
            }

            //设备系统条件
            if (!empty($req['choice_device'])) {
                $base['device'] = [
                    'type' => 'in',
                    'value' => $req['choice_device']
                ];
            }

            //渠道条件
            if (!empty($req['choice_channel'])) {
                $base['channel'] = [
                    'type' => 'in',
                    'value' => $req['choice_channel'],
                ];
            }
        }

        //确认app_id
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
        if (!empty($default_app['game_id'])) {
            $base["app_id"] = array(
                "symbol" => '=',
                "value" => $default_app['game_id']
            );
        }
        return ['where' => $base];
    }

    /**
     * des: 日志数据综合处理 仅处理report
     * @param $cond [type|array] 基础查询条件
     * @param $table [type|string] 表名
     * @return array
     */
    protected function reportData($cond, $table)
    {
        // 查询条件组装
        $report_cond = $cond;

        $report_cond['joint'] = "GROUP BY  `channel` ORDER BY `date` ";
        $report_cond['field'] = "`date`, `channel`, IFNULL(SUM(new_account), 0) as account, IFNULL(SUM(new_device), 0) as device";

        $result = array_column_index($this->_reportCommon->getDatas(
            $report_cond,
            $table
        ), 'channel');                                                         // 每个渠道用户日志原始数据

        return $result;
    }

    /**
     * des: 日志数据综合处理 仅处理设备
     * @param $cond [type|array] 基础查询条件
     * @param $table [type|string] 表名
     * @return array
     */
    protected function deviceData($cond, $table)
    {
        //查询条件组装
        $device_cond = $cond;

        $device_cond['joint'] = "GROUP BY device ORDER BY `device` DESC";
        $device_cond['field'] = "`device` as os,SUM(new_account) as account, SUM(new_device) as device";

        $result = array_column_index($this->_reportCommon->getDatas(
            $device_cond,
            $table
        ), 'os');

        return $result;
    }

    /**
     * des: 日志数据综合处理 仅处理图表数据
     * @param $cond [type|array] 基础查询条件
     * @param $table [type|string] 表名
     * @return array
     */
    protected function imageData($cond, $table)
    {
        // 查询条件组装
        $report_cond = $cond;

        $report_cond['joint'] = "GROUP BY  `channel`, `date`";
        $report_cond['field'] = "`date`, `channel`, SUM(new_account) as account, SUM(new_device) as device";

        $result = array_column_index($this->_reportCommon->getDatas(
            $report_cond,
            $table
        ), 'date');                                                         // 每个渠道用户日志原始数据

        return $result;
    }

    /**
     * des: 降低数组的维度
     * @param: $origin [type|string] 原数据
     * @return array
     */
    private function depthCrop($origin)
    {
        if (!empty($origin)) {
            foreach ($origin as $key => $detail) {
                foreach ($detail as $k => $v) {
                    if (empty($tmp[$k])) {
                        $temp[$key]['channel'] = $v['channel'];
                        $temp[$key]['account'] = $v['account'];
                        $temp[$key]['device'] = $v['device'];
                    } else {
                        $temp[$key]['account'] = $temp[$key]['account'] + $v['account'];
                        $temp[$key]['device'] = $temp[$key]['device'] + $v['device'];
                    }
                }
            }
        }
        return $temp;
    }

    //降低数组维度, 并且只统计新增设备
    private function deviceDepthCrop($origin)
    {
        if (!empty($origin)) {
            foreach ($origin as $key => $detail) {
                foreach ($detail as $k => $v) {
                    if (empty($tmp[$k])) {
                        $temp[$key] = $v['device'];
                    } else {
                        $temp[$key] = $temp[$key] + $v['device'];
                    }
                }
            }
        }

        return $temp;
    }

    //新增统计图表需要数据
    private function imageDepthCrop($origin)
    {
        $result = [];
        foreach ($origin as $key => $detail) {
            $temp = [];
            if (!empty($detail)) {
                foreach ($detail as $k => $v) {
                    if (empty($temp[$key])) {
                        $temp[$key]['category'] = $v['date'];
                        $temp[$key]['new_account'] = $v['account'];
                        $temp[$key]['new_device'] = $v['device'];
                    } else {
                        $temp[$key]['new_account'] = $v['account'] + $temp[$key]['new_account'];
                        $temp[$key]['new_device'] = $v['device'] + $temp[$key]['new_device'];
                    }
                }
                $result[] = $temp[$key];
            } else {
                $result[]['category'] = $key;
            }
        }
        //检测数据完整性
        foreach ($result as $r => $t) {
            if (count($t) < 3) {
                $result[$r]['new_account'] = 0;
                $result[$r]['new_device'] = 0;
            }
        }

        foreach ($result as $ca => $item) {
            if (strpos($item['category'], '-')) {
                $result[$ca]['category'] = str_replace('-', '', $item['category']);
            }
        }

        return $result;
    }

    //组装 饼状图可用的数据
    protected function installPieData($origin)
    {
        if (!empty($origin)) {
            foreach ($origin as $k => $v) {
                $new_account[] = [
                    "category" => empty($k) ? 'default' : $k,
                    "column-1" => $v
                ];
            }
        }
        return empty($new_account) ? [] : $new_account;
    }

    //比较渠道设备 或者渠道新增
    protected function compare($origin, $keys)
    {
        $result = [];
        if (!empty($origin)) {
            foreach ($origin as $k => $detail) {
                foreach ($detail as $key => $value) {
                    $result[$key][$k] = $value;
                }
            }

            //按照account 排序 并且只要靠前的前五个, 剩余的为其他
            arsort($result[$keys]);
            $i = 0;
            $temp = 0;
            foreach ($result[$keys] as $k => $v) {
                if ($i++ > 4) {
                    $temp += intval($v);
                    unset($result[$keys][$k]);
                }
            }
            $result[$keys]['others'] = $temp;
        }
        return empty($result[$keys]) ? [] : $result[$keys];
    }

    protected function activeDepthCrop($origin, $type)
    {
        if (!empty($origin) && !empty($type)) {
            $result = [];
            foreach ($origin as $k => $v) {
                $temp = [];
                foreach ($v as $key => $value) {
                    if (empty($temp[$k])) {
                        $temp[$k]['date'] = $value['date'];
                        $temp[$k]['channel'] = $value['channel'];
                        $temp[$k]['device'] = $value['device'];
                        $temp[$k]['active_account'] = $value['active_account'];
                        $temp[$k]['active_device'] = $value['active_device'];
                    } else {
                        $temp[$k]['active_account'] = $temp[$k]['active_account'] + $value['active_account'];
                        $temp[$k]['active_device'] = $temp[$k]['active_device'] + $value['active_device'];
                    }
                }
                $result[$k] = $temp[$k];
            }

            if ($type == 'channel') {
                foreach ($result as $channel => $channel_device) {
                    $finnal_channel[$channel] = [
                        'channel' => $channel,
                        'active_device' => $channel_device['active_device']
                    ];
                }
                return $finnal_channel;
            }

            if ($type = 'os') {
                foreach ($result as $os => $device_os) {
                    $finnal_os[$os] = $device_os['active_device'];
                }
                return $finnal_os;
            }
        }
    }

    //统计活跃图数据
    private function imageActiveDepthCrop($origin)
    {
        $result = [];
        foreach ($origin as $key => $detail) {
            $temp = [];
            if (!empty($detail)) {
                foreach ($detail as $k => $v) {
                    if (empty($temp[$key])) {
                        $temp[$key]['category'] = $v['date'];
                        $temp[$key]['active_account'] = $v['active_account'];
                        $temp[$key]['active_device'] = $v['active_device'];
                    } else {
                        $temp[$key]['active_account'] = $v['active_account'] + $temp[$key]['active_account'];
                        $temp[$key]['active_device'] = $v['active_device'] + $temp[$key]['active_device'];
                    }
                }
                $result[] = $temp[$key];
            } else {
                $result[]['category'] = $key;
            }
        }

        //检测数据完整性
        foreach ($result as $r => $t) {
            if (count($t) < 3) {
                $result[$r]['active_account'] = 0;
                $result[$r]['active_device'] = 0;
            }
        }

        foreach ($result as $ca => $item) {
            if (strpos($item['category'], '-')) {
                $result[$ca]['category'] = str_replace('-', '', $item['category']);
            }
        }

        return $result;
    }

    /**
     * 生成日期数组
     * @param $start_time
     * @param $end_time
     * @return array
     */
    public function getDateArrAction($start_time, $end_time)
    {
        if (!$start_time || !$end_time) return [];
        $start_timestamp = strtotime($start_time);
        $end_timestamp = strtotime($end_time);
        $dateArr = [];
        $days = ($end_timestamp - $start_timestamp) / 86400;
        for ($i = 0; $i <= $days; $i++) {
            $dateArr[] = date('Y-m-d', $start_timestamp + $i * 86400);
        }
        return $dateArr;
    }
}