<?php

/**
 * 设置
 */

namespace MyApp\Controllers;

use MyApp\Models\Subscribe;
use MyApp\Models\Top\Game;
use Phalcon\Mvc\Dispatcher;
use MyApp\Models\Utils;

class SettingController extends ControllerBase
{

    private $gameModel;
    private $subscribeModel;
    private $words = [
        'new_account' => '新增用户',
        'new_pay'     => '新增付费',
        'stay_user'   => '用户留存',
        'ARPU'        => 'ARPU',
        'ARPPU'       => 'ARPPU',
        'rate'        => '付费率',
    ];

    public function initialize()
    {
        $this->gameModel      = new Game();
        $this->subscribeModel = new Subscribe();
    }

    public function subscribeAction()
    {
        $user_id   = $this->session->get('user_id');
        $app_id    = $this->session->get('default_app')['game_id'];
        $subscribe = $this->subscribeModel->getOne($user_id, $app_id);
        if ($subscribe) {
            $subscribe['start_time']  = date('Y-m-d', $subscribe['start_time']);
            $subscribe['end_time']    = date('Y-m-d', $subscribe['end_time']);
            $subscribe['create_time'] = date('Y-m-d', $subscribe['create_time']);
            $subscribe['subscribe']   = json_decode($subscribe['subscribe']);
            $words                    = $this->getWords($subscribe['subscribe']);

            $subscribe['subscribe']     = $words;
            $subscribe['new_subscribe'] = array_flip($words);
            $this->view->subscribe      = $subscribe;
        }
        $this->view->pick("setting/subscribe");
    }

    public function addAction()
    {
        if($_POST){
            $parameter = $this->request->get();
            $parameter['app_id']    = $this->session->get('default_app')['game_id'];
            $parameter['user_id']   = $this->session->get('user_id');
            $parameter['subscribe'] = json_encode($parameter['select']);
            $parameter['user_name'] = $this->session->get('username');
            $result                 = $this->subscribeModel->add($parameter);

        }
        $this->subscribeAction();
    }

    public function warningAction()
    {
    }

    public function synchroAction()
    {
        if ($this->request->get('is_form', ['string', 'trim'])) {

            $secret_key = $this->config->setting->secret_key;
            $time       = time();

            $base_url = $this->config->sso->api_url . '?time=' . $time . '&key=' . md5($secret_key . $time);

            $gameList = json_decode(file_get_contents($base_url), true);

            if($gameList['code'] == 1){
                Utils::tips('error', '同步失败', '/');
            }

            $this->gameModel->saveData($gameList);

            Utils::tips('success', '同步成功', '/');
            exit;

        }
    }

    //数据转换
    public function getWords($par)
    {
        $data = [];
        foreach ($par as $key => $value) {
            if (key_exists($value, $this->words)) {
                $data[] = $this->words[$value];
            }
        }
        return $data;
    }
}