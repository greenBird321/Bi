<?php

/**
 * 游戏分析
 */

namespace MyApp\Controllers;


use MyApp\Models\Backend\GameServer;
use MyApp\Models\Game;
use MyApp\Models\Utils;
use Phalcon\Mvc\Dispatcher;

class GameController extends ControllerBase
{
    public $gameServer;
    public $server_list;
    public $gameModel;

    private $_channel = [
        'vivo',
        '360',
        'baidu',
        'meizu'
    ];
    private $_infiltration_days = [0, 1, 2, 3, 4, 5, 6];
    private static $img_days = [1, 2, 6];

    public function initialize()
    {
        parent::initialize();
        $this->gameServer = new GameServer();
        $this->gameModel = new Game();
    }

    // 游戏内新增用户
    public function user_newAction()
    {
        $conditions = $this->mergeQueryCond(time() - 86400, time());
        $start_time = strtotime($conditions['where']['date']['start']);
        $end_time   = strtotime($conditions['where']['date']['end']);
        $app_id     = $conditions['where']['app_id']['value'];
        $rpc_url    = $this->config->rpc["{$app_id}_url"];
        $server_id = isset($conditions['server_id']['zone']['value']) ? $conditions['server_id']['zone']['value'] : 1;
        $url = $rpc_url."game/userCreateTimes?zone={$server_id}&start={$start_time}&end={$end_time}";
        // 获取新增用户数据
        $response = json_decode(file_get_contents($url), true);

        $table_data = [];
        $image_data = [];
        if ($response['code'] == 0) {
            $tmp = $response['data'];
            foreach ($tmp as $k => $v) {
                if ($v == '-') {
                    $tmp[$k] = 0;
                }
            }
            // 组装图表数据
            foreach ($tmp as $key => $value) {
                $image_data[] = [
                    date('H',$key),
                    $value
                ];
            }
            // 组装表格数据
            foreach ($response['data'] as $i => $j) {
                $table_data[] = [
                    date('Y-m-d H:i:s', $i),
                    $j
                ];
            }
        }

        $this->server_list = $this->getServerID();
        $this->view->start_time  = date('Y-m-d', $start_time);
        $this->view->end_time    = date('Y-m-d', $end_time);
        $this->view->server_list = $this->server_list;
        $this->view->table_data = json_encode($table_data, true);
        $this->view->image_data = json_encode($image_data, true);
        $this->view->pick("game/user_new");
    }

    //游戏内活跃用户
    public function user_activeAction()
    {
    }

    //用户游戏关卡统计
    public function user_gameLevelAction()
    {
        // 获取默认参数
        $conditions = $this->mergeQueryCond(time() - 3600, time());
        // 获取服务器列表
        $this->server_list = $this->getServerID();
        // 时间
        $time_start = $conditions['where']['date']['start'];
        $time_end   = $conditions['where']['date']['end'];
        // 请求时间
        $start = strtotime($time_start);
        $end   = strtotime($time_end);
        // 默认一服
        $server_id = isset($conditions['server_id']['zone']['value']) ? $conditions['server_id']['zone']['value'] : 1;
        // 获取app_id
        $app_id  = $conditions['where']['app_id']['value'];
        $rpc_url = $this->config->rpc["{$app_id}_url"];
        // 发送数据给rpc
        $url = $rpc_url."stats/userPassLevel?start={$start}&end={$end}&server_id={$server_id}";
        $response = json_decode(file_get_contents($url), true);
        $tableData = [];
        $imageData = [];
        if ($response['code'] == 0) {
            // 组装表格数据 // 组装图表数据
            foreach ($response['data'] as $key => $value) {
                $tableData[] = array_values($value);
                $userMix = intval(rtrim($value['userMix'])) / 100;
                $imageData[] = [
                    $value['levelId'],
                    $userMix,
                ];
            }
        }

        $this->view->start_time  = $time_start;
        $this->view->end_time    = $time_end;
        $this->view->server_list = $this->server_list;
        $this->view->tab_data    = json_encode($tableData);
        $this->view->image_data  = json_encode($imageData);
        $this->view->pick("game/user_gameLevel");
    }

    //充值排行
    public function topAction()
    {
        // 默认参数
        $default_start = '';
        $default_end   = '';
        $conditions    = $this->mergeQueryCond($default_start, $default_end);
        $app_id        = $conditions['where']['app_id']['value'];
        $start_time    = empty($conditions['where']['date']['start']) ? '' : strtotime($conditions['where']['date']['start']);
        $end_time      = empty($conditions['where']['date']['end']) ? '' : strtotime($conditions['where']['date']['end']);
        $server_id     = isset($conditions['server_id']['zone']['value']) ? $conditions['server_id']['zone']['value'] : 1;
        $channel       = isset($conditions['channel']['ch']['value']) ? $conditions['channel']['ch']['value']:'';
        $rpc_url       = $this->config->rpc["{$app_id}_url"];
        $url = $rpc_url."game/top?zone=$server_id&channel=$channel&start=$start_time&end=$end_time";
        $response = json_decode(file_get_contents($url), true);
        $table_data = [];
        if ($response['code'] == 0) {
            foreach ($response['data'] as $key => $value) {
                $table_data[] = array_values($value);
            }
        }

        $this->view->start_time  = '';
        $this->view->end_time    = '';
        $this->view->server_list = $this->getServerID();
        $this->view->tab_data    = json_encode($table_data);
        $this->view->channel     = $this->_channel;
        $this->view->pick("game/user_top");
    }

    //首充分布
    public function distributionAction()
    {
        // 默认参数
        $default_start = '';
        $default_end   = '';
        $conditions    = $this->mergeQueryCond($default_start, $default_end);
        $app_id        = $conditions['where']['app_id']['value'];
        $start_time    = empty($conditions['where']['date']['start']) ? '' : strtotime($conditions['where']['date']['start']);
        $end_time      = empty($conditions['where']['date']['end']) ? '' : strtotime($conditions['where']['date']['end']);
        $server_id     = isset($conditions['server_id']['zone']['value']) ? $conditions['server_id']['zone']['value'] : 1;
        $rpc_url       = $this->config->rpc["{$app_id}_url"];
        $url = $rpc_url."game/distribution?zone=$server_id&start=$start_time&end=$end_time";

        $response = json_decode(file_get_contents($url), true);

        $tab_data = [];
        if ($response['code'] == 0) {
            $tab_data = $response['data'];
            foreach ($tab_data as $key => $value) {
                $tab_data[$key] = array_values($value);
            }
        }

        $this->view->start_time  = $default_start;
        $this->view->end_time    = $default_end;
        $this->view->server_list = $this->getServerID();
        $this->view->tab_data    = json_encode($tab_data, true);
        $this->view->pick('game/user_distribution');
    }

    // 充值分布
    public function rechargeDistributionAction()
    {
        //默认参数
        $default_start = '';
        $default_end   = '';
        $conditions    = $this->mergeQueryCond($default_start, $default_end);
        $app_id        = $conditions['where']['app_id']['value'];
        $start_time    = empty($conditions['where']['date']['start']) ? '' : strtotime($conditions['where']['date']['start']);
        $end_time      = empty($conditions['where']['date']['end']) ? '' : strtotime($conditions['where']['date']['end']);
        $server_id     = isset($conditions['server_id']['zone']['value']) ? $conditions['server_id']['zone']['value'] : 1;
        $rpc_url       = $this->config->rpc["{$app_id}_url"];
        $url           = $rpc_url . "game/rechargeDistribution?zone=$server_id&info_start=$start_time&info_end=$end_time";

        $response      = json_decode(file_get_contents($url), true);

        $tab_data = [];
        if ($response['code'] == 0) {
            $data = $response['data'];
            $tab_data[] = [
                $data['server'],
                $data['playerCount'],
                $data['rechargePlayerCount'],
                $data['rechargeAmount'],
                $data['rechargeMix'],
                $data['playerAverage']
            ];

        }

        $this->view->start_time  = $default_start;
        $this->view->end_time    = $default_end;
        $this->view->server_list = $this->getServerID();
        $this->view->tab_data    = json_encode($tab_data, true);
        $this->view->pick('game/user_rechargeDistribution');
    }

    public function realtimeOnlineAction()
    {
        // 默认参数
        $default_start = time() - 60;
        $default_end   = time();
        $conditions    = $this->mergeQueryCond($default_start, $default_end);
        $app_id        = $conditions['where']['app_id']['value'];
        $server_id     = isset($conditions['server_id']['zone']['value']) ? $conditions['server_id']['zone']['value'] : 1;
        $rpc_url       = $this->config->rpc["{$app_id}_url"];
        $url           = $rpc_url . "game/realtimeOnline?zone=$server_id&start=$default_start&end=$default_end";
        $response      = json_decode(file_get_contents($url), true);
        $tab_data = [];
        if ($response['code'] == 0) {
            $tab_data[] = array_values($response['data']);
        }

        $this->view->server_list = $this->getServerID();
        $this->view->tab_data = json_encode($tab_data, true);
        $this->view->pick('game/user_online');
    }

    public function historyOnlineAction()
    {
        $conditions = $this->mergeQueryCond(strtotime(date('Y-m-d', time())), time() - 3600);
        $app_id     = $conditions['where']['app_id']['value'];
        $start_time = empty($conditions['where']['date']['start']) ? '' : strtotime($conditions['where']['date']['start']);
        $end_time   = empty($conditions['where']['date']['end']) ? '' : strtotime($conditions['where']['date']['end']);
        $server_id  = isset($conditions['server_id']['zone']['value']) ? $conditions['server_id']['zone']['value'] : 1;
        $rpc_url    = $this->config->rpc["{$app_id}_url"];
        $url        = $rpc_url . "game/historyOnline?zone=$server_id&start=$start_time&end=$end_time";
        $response   = json_decode(file_get_contents($url), true);
        $tab_data = [];
        if ($response['code'] == 0) {
            $tab_data = $response['data'];
            foreach ($tab_data as $key => $value) {
                $tab_data[$key] = array_values($value);
            }
        }

        $this->view->start_time  = date('Y-m-d H:i:s', $start_time);
        $this->view->end_time    = date('Y-m-d H:i:s', $end_time);
        $this->view->tab_data    = json_encode($tab_data, true);
        $this->view->server_list = $this->getServerID();
        $this->view->pick('game/user_history');
    }

    // 付费渗透
    public function paymentInfiltrationAction()
    {
        $condidtions             = $this->mergeQueryCond(strtotime(date('Y-m-d', strtotime('-7 day'))), strtotime(date('Y-m-d', strtotime('-1 day'))));
        $parameter['start_time'] = $condidtions['where']['date']['start'];
        $parameter['end_time']   = $condidtions['where']['date']['end'];
        $parameter['app_id']     = $condidtions['where']['app_id']['value'];
        $parameter['server_id']  = isset($conditions['server_id']['zone']['value']) ? $conditions['server_id']['zone']['value'] : 1;
        $data                    = $this->gameModel->getPaymentInfiltration($parameter);
        $sortData                = array_column_index($data, 'date');
        foreach ($this->_infiltration_days as $key => $value) {
            $stats_time = date('Y-m-d', strtotime("- {$value} day", strtotime($parameter['end_time'])));
            // 外层数据补全
            if (!isset($sortData[$stats_time])) {
                $sortData[$stats_time] = [
                    'a_date' => $stats_time,
                    'a_server' => $parameter['server_id'],
                    'a_new_player' => 0,
                ];
            }

            // 数据补全
            foreach ($this->_infiltration_days as $day) {
                // 提取数据
                if ( isset($sortData[$stats_time][$day]) && is_array($sortData[$stats_time][$day])) {
                    $sortData[$stats_time]['a_date'] = $sortData[$stats_time][$day]['date'];
                    $sortData[$stats_time]['a_server'] = $sortData[$stats_time][$day]['server_id'];
                    $sortData[$stats_time]['a_new_player'] = $sortData[$stats_time][$day]['new_user_count'];
                    // 必须错位，否则不能按照预定的格式输出
                    $sortData[$stats_time][$day + count($this->_infiltration_days)] = $sortData[$stats_time][$day]['new_user_payment'];
                    unset($sortData[$stats_time][$day]);
                } else if (!isset($sortData[$stats_time][$day])) {
                    $sortData[$stats_time][$day + count($this->_infiltration_days)] = '-';
                }
            }

        }
        // 数组排序: 按照key的降序进行排序
        krsort($sortData);

        // data数据
        foreach ($sortData as $value) {
            $table_data[] = array_values($value);
        }

        $this->view->setVars([
            'start_time' => $parameter['start_time'],
            'end_time' => $parameter['end_time'],
            'server_list' => $this->getServerID(),
            'tab_data' => json_encode($table_data, true)
        ]);

        $this->view->pick('game/user_paymentInfiltration');
    }

    public function userLostAction()
    {
        $condidtions             = $this->mergeQueryCond(strtotime(date('Y-m-d', strtotime('-7 day'))), strtotime(date('Y-m-d', strtotime('-1 day'))));
        $parameter['start_time'] = $condidtions['where']['date']['start'];
        $parameter['end_time']   = $condidtions['where']['date']['end'];
        $parameter['app_id']     = $condidtions['where']['app_id']['value'];
        $data                    = $this->gameModel->getUserLost($parameter);
        $sortData                = array_column_index($data, 'date');
        foreach ($this->_infiltration_days as $key => $value) {
            $stats_time = date('Y-m-d', strtotime("- {$value} day", strtotime($parameter['end_time'])));

            // 外层数据补全
            if (!isset($sortData[$stats_time])) {
                $sortData[$stats_time] = [
                    'a_date' => $stats_time,
                    'a_new_player' => 0,
                ];
            }

            // 数据补全
            foreach ($this->_infiltration_days as $day) {
                // 提取数据
                if ( isset($sortData[$stats_time][$day]) && is_array($sortData[$stats_time][$day])) {
                    $sortData[$stats_time]['a_date'] = $sortData[$stats_time][$day]['date'];
                    $sortData[$stats_time]['a_new_player'] = $sortData[$stats_time][$day]['new_user_count'];
                    // 必须错位，否则不能按照预定的格式输出
                    $sortData[$stats_time][$day + count($this->_infiltration_days)] = $sortData[$stats_time][$day]['lost_user_count'];
                    unset($sortData[$stats_time][$day]);
                } else if (!isset($sortData[$stats_time][$day])) {
                    $sortData[$stats_time][$day + count($this->_infiltration_days)] = '-';
                }
            }
        }
        // 数组排序: 按照key的降序进行排序
        krsort($sortData);

        // data数据
        foreach ($sortData as $value) {
            $table_data[] = array_values($value);
        }

        $this->view->setVars([
            'start_time' => $parameter['start_time'],
            'end_time' => $parameter['end_time'],
            'tab_data' => json_encode($table_data, true)
        ]);

        $this->view->pick('game/user_lost');
    }

    public function lostStatsAction()
    {
        $condidtions = $this->mergeQueryCond(strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d 23:59:59', time())));
        $start = $condidtions['where']['date']['start'];
        $end   = $condidtions['where']['date']['end'];
        $app_id   = $condidtions['where']['app_id']['value'];
        // 默认1服
        $server_id = isset($condidtions['where']['zone']['value']) ? $condidtions['where']['zone']['value'] : 1;
        $channel   = isset($condidtions['where']['channel']['value']) ? $condidtions['where']['channel']['value']:'';
        $lost_day  = isset($condidtions['where']['lost_day']['value']) ? $condidtions['where']['lost_day']['value'] : 1;
        $rpc_url   = $this->config->rpc["{$app_id}_url"];
        $url       = $rpc_url . "Stats/statsLost?zone=$server_id&start=$start&end=$end&channel=$channel&lostDay=$lost_day";
        $response  = json_decode(file_get_contents($url), true);
        $tab_data = [];
        if ($response['code'] == 0) {
            foreach ($response['data'] as $key => $value) {
                foreach ($value as $k => $v) {
                    $tab_data[] = [
                        $v['AreaLastStage'],
                        $v['total_user'],
                        $v['activity_user'],
                        $v['lostCount'],
                        $v['lostRate'],
                        $v['chargeCount'],
                        $v['money']
                    ];
                }
            }
        }
        
        $this->view->server_list = $this->getServerID();
        $this->view->tab_data = json_encode($tab_data, true);
        $this->view->lost_day = [
            1 => '1天',
            2 => '2天',
            3 => '3天',
            4 => '4天',
            5 => '5天',
            6 => '6天',
            7 => '7天',
            8 => '8天',
            9 => '9天',
            10 => '10天',
        ];
        $this->view->lostCondition = [
            1 => '按照关卡条件筛选'
        ];
        $this->view->server_list = $this->getServerID();
        $this->view->channel = $this->_channel;
        $this->view->start = date('Y-m-d 00:00:00', time());
        $this->view->end = date('Y-m-d 23:59:59', time());
        $this->view->pick('game/user_lostStats');
    }

    // 实时数据查询
    public function statsTimeQueryAction() 
    {
        // 默认当天的数据
        $condidtions = $this->mergeQueryCond(strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d H:i:s', time())));
        $start = $condidtions['where']['date']['start'];
        $end   = $condidtions['where']['date']['end'];
        $app_id   = $condidtions['where']['app_id']['value'];
        // 默认1服
        $server_id = isset($condidtions['where']['choice_server']['value']) ? $condidtions['where']['choice_server']['value'] : 1;
        if (is_array($server_id)) {
            $server_id = implode(',', $server_id);
        }
        $channel   = isset($condidtions['where']['channel']['value']) ? $condidtions['where']['channel']['value']:'';
        $rpc_url   = $this->config->rpc["{$app_id}_url"];
        $url       = $rpc_url . "Stats/statsTimeQuery?zone=$server_id&start=$start&end=$end&channel=$channel";
        $response = json_decode(file_get_contents($url), true);

        $tab_data = [];
        if ($response['code'] == 0) {
            foreach ($response['data'] as $key => $value) {
                foreach ($value as $k => $v) {
                    $tab_data[] = [
                        $key,
                        $v['serverId'],
                        $v['add_user_count'],
                        $v['act_user_count'],
                        $v['money'],
                        $v['chargeRate'],
                        $v['chargeRate'],
                        $v['arpu'],
                        $v['arppu']
                    ];
                }
            } 
        }

        $this->view->server_list = $this->getServerID();
        $this->view->channel = $this->_channel;
        $this->view->tab_data = json_encode($tab_data, true);
        $this->view->start = date('Y-m-d 00:00:00', time());
        $this->view->end = date('Y-m-d H:i:s', time());
        $this->view->pick('game/user_timequery');
    }

    // (实时查询)对比数据
    public function statsContrastAction() 
    {
        // 默认当天的数据
        $condidtions = $this->mergeQueryCond(strtotime(date('Y-m-d 00:00:00', time())), strtotime(date('Y-m-d H:i:s', time())));
        $start = $condidtions['where']['date']['start'];
        $end   = $condidtions['where']['date']['end'];
        $app_id   = $condidtions['where']['app_id']['value'];
        // 默认1服
        $server_id = isset($condidtions['where']['choice_server']['value']) ? $condidtions['where']['choice_server']['value'] : 1;
        if (is_array($server_id)) {
            $server_id = implode(',', $server_id);
        }
        $channel   = isset($condidtions['where']['channel']['value']) ? $condidtions['where']['channel']['value']:'';
        $rpc_url   = $this->config->rpc["{$app_id}_url"];
        $url       = $rpc_url . "Stats/statsContrast?zone=$server_id&start=$start&end=$end&channel=$channel";
        $response = json_decode(file_get_contents($url), true);

        $tab_data = [];
        if ($response['code'] == 0) {
            $temp = [];
            //数据按照日期归类
            foreach ($response['data'] as $key => $value) {
                foreach ($value as $k => $v) {
                    $temp[$k][] = [
                        $k,
                        $key,
                        $v['addUserCount'],
                        $v['addRoleMoney'],
                        $v['addRoleChargeCount'],
                        $v['addRoleRote'],
                        $v['addRolearpu'],
                        $v['addRolearppu'],
                        $v['oldUserCount'],
                        $v['oldRoleMoney'],
                        $v['oldRoleChargeCount'],
                        $v['oldRoleRote'],
                        $v['oldRolearpu'],
                        $v['oldRolearppu']
                    ];
                }
            }

            // 按照前端格式进行整理
            foreach ($temp as $key => $value) {
                foreach ($value as $k => $v) {
                    $tab_data[] = $v;
                }
            }
        }
        $this->view->server_list = $this->getServerID();
        $this->view->channel = $this->_channel;
        $this->view->tab_data = json_encode($tab_data, true);
        $this->view->start = date('Y-m-d 00:00:00', time());
        $this->view->end = date('Y-m-d H:i:s', time());
        $this->view->pick('game/user_statsContrast');
    }

    protected function mergeQueryCond($start_time, $end_time)
    {
        //默认
        if (!empty($start_time) && !empty($end_time)) {
            $before_day = date('Y-m-d H:i:s', $start_time);
            $after_day = date('Y-m-d H:i:s', $end_time);
        } else {
            $before_day = $start_time;
            $after_day = $end_time;
        }

        $base = array(
            "date" => array(
                "type" => "range",
                "start" => $before_day,
                "end" => $after_day,
            )
        );

        //如果用户提交查询条件 (有POST 传值)
        if ($this->request->isPost()) {
            $req  = $this->request->get();;
            if (!empty($req['start_time']) && !empty($req['end_time'])) {
                $date = dateCompare($req["start_time"], $req["end_time"]);
                if (!empty($date)) {
                    $base['date']['start'] = $date[0];
                    $base['date']['end']   = $date[1];
                }
            }
            // 游戏区服条件
            if (!empty($req['server_id'])) {
                $base['zone'] = [
                    'symbol' => '=',
                    'value' => $req['server_id']
                ];
            }

            // 渠道条件
            if (!empty($req["choice_channel"])) {
                $base["channel"] = array(
                    "type" => "in",
                    "value" => $req["choice_channel"],
                );
            }

            // 流失天数
            if (!empty($req['lost_day'])) {
                $base["lost_day"] = [
                    'symbol' => '=',
                    'value' => $req['lost_day']
                ];
            }

            // 流失条件
            if (!empty($req['lost_condition'])) {
                $base["lost_condition"] = [
                    'symbol' => '=',
                    'value' => $req['lost_condition']
                ];
            }

            // 多服务器选择
            if (!empty($req['choice_server'])) {
                $base['choice_server'] = [
                    'symbol' => 'range',
                    'value' => $req['choice_server']
                ];
            }
        }

        //确认app_id
        $default_app = $this->session->get('default_app');
        if (!empty($default_app['game_id'])) {
            $base["app_id"] = array(
                "symbol" => '=',
                "value" => $default_app['game_id']
            );
        }

        return ['where' => $base];
    }

    /**
     * 获取appID对应的ServerId
     * @return mixed
     */
    public function getServerID()
    {
        $allGameServer = $this->gameServer->backDatas();
        $app_id        = $this->session->get('default_app');
        $server_list   = $allGameServer[$app_id['game_id']];
        return $server_list;
    }
}