<?php

/**
 * 游戏分析
 */

namespace MyApp\Controllers;


use MyApp\Models\Backend\GameServer;
use MyApp\Models\Utils;
use Phalcon\Mvc\Dispatcher;

class GameController extends ControllerBase
{
    public $gameServer;
    public $server_list;

    public function initialize()
    {
        parent::initialize();
        $this->gameServer = new GameServer();
    }

    // 游戏内新增用户
    public function user_newAction()
    {
        $conditions        = $this->mergeQueryCond();
        $start_time        = strtotime($conditions['where']['date']['start']);
        $end_time          = strtotime($conditions['where']['date']['end']);
        $this->server_list = $this->getServerID();

        $this->view->start_time  = date('Y-m-d', $start_time);
        $this->view->end_time    = date('Y-m-d', $end_time);
        $this->view->server_list = $this->server_list;
        $this->view->pick("game/user_new");
    }

    //游戏内活跃用户
    public function user_activeAction()
    {
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
            $req  = $this->request->get();
            $date = dateCompare($req["start_time"], $req["end_time"]);

            if (!empty($date)) {
                $base['date']['start'] = $date[0];
                $base['date']['end']   = date('Y-m-d', strtotime($date[1])) . ' 23:59:59';
            }

            // 游戏区服条件
            if (!empty($req['server_id'])) {
                $base['zone'] = [
                    'symbol' => '=',
                    'value' => $req['server_id']
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