<?php
namespace MyApp\Models\Trade;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;
use Phalcon\Security\Random;

class Trade extends Model
{

    private $_link;


    public function onConstruct()
    {
        $this->_link = DI::getDefault()->get('dbTrade');
    }
    //获取实时数据的充值数据
    public function getData($date, $app_id)
    {
        $sql = "SELECT SUBSTRING(complete_time,1,13) `time`, sum(amount) amount, COUNT(DISTINCT uuid) uuid FROM `transactions` WHERE app_id = '{$app_id}' AND complete_time >='{$date} 00:00:00' AND complete_time <='{$date} 23:59:59' GROUP BY `time`";
        $query = DI::getDefault()->get('dbTrade')->query($sql);
        $query->setFetchMode(Db::FETCH_ASSOC);
        $data = $query->fetchAll();
        return $data;
    }
}