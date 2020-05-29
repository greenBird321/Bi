<?php
/**
 * Created by PhpStorm.
 * Desc: APP切换处理
 * User: jzco
 * Date: 2017/6/9
 * Time: 16:21
 */

namespace MyApp\Controllers;

use Phalcon\Http\Request;                   // 表单参数获取组件 refer: https://docs.phalconphp.com/en/latest/api/Phalcon_Http_Request.html
use Phalcon\Filter;                         // 表单验证过滤组件 refer: https://docs.phalconphp.com/en/latest/reference/filter.html

use Phalcon\Mvc\Dispatcher;


class AppsController extends ControllerBase
{
    private $_filter;

    public function initialize()
    {
        parent::initialize();

        $this->_filter = new Filter();
    }


    /**
     * des: App 切换 TODO :: 简化传参
     */
    public function exchangeAction()
    {
        $cate = $this->request->get('cate', 'string');
        $appId = $this->request->get('appid', 'int');
        $apps = $this->session->get('apps');

        if (in_array($appId, $_SESSION['resources']['allow_game'])) {
            $this->session->set('default_app', $apps[$cate]["data"][$appId]);
            $this->response->redirect('');
        }
        else {
        }
    }

}