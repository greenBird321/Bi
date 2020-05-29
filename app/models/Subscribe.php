<?php

namespace MyApp\Models;


use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;

class Subscribe extends Model
{
    private $utilsModel;

    public function initialize()
    {
        $this->setConnectionService('dbData');
        $this->setSource("Subscribe");
        $this->utilsModel = new Utils();
    }

    public function add($data)
    {
        $user = $this->getOne($data['user_id'], $data['app_id']);
        if (!$user) {
            $sql    = "INSERT INTO `subscribe`(`user_id`,`app_id`,`user_name`,`subscribe`, `start_time`, `end_time`, `create_time`) VALUES (?, ?,?,?,?,?,?)";
            $result = DI::getDefault()->get('dbData')->execute($sql, array(
                $data['user_id'],
                $data['app_id'],
                $data['user_name'],
                $data['subscribe'],
                strtotime($data['start_time']),
                strtotime($data['end_time']),
                time()

            ));
            return $result;
        }
        $sql   = "UPDATE subscribe SET   subscribe = '" . $data['subscribe'] . "' ,start_time  = '" . strtotime($data['start_time']) . "',end_time  = '" . strtotime($data['end_time']) . "',create_time  = '" . time() . "' WHERE user_id = " . $data['user_id'] . ' AND app_id = ' . $data['app_id'];
        $query = DI::getDefault()->get('dbData')->query($sql);
        return true;
    }

    public function getOne($userid, $appid)
    {
        $sql   = "SELECT user_id,app_id,subscribe,start_time, end_time,create_time FROM subscribe WHERE user_id=:user_id AND app_id=:app_id";
        $bind  = array('user_id' => $userid, 'app_id' => $appid);
        $query = DI::getDefault()->get('dbData')->query($sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetch();
        return $data;
    }


}