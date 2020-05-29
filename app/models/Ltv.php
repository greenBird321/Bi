<?php
namespace MyApp\Models;
use MyApp\Models\Base;

use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;
use Phalcon\Security\Random;

class Ltv extends Base
{
    private $_link;
    private $_table;

    public function onConstruct()
    {
        $this->_link = DI::getDefault()->get('dbData');
        $this->_table = 'ltv';
    }

    /**
     * des: 通用数据查询方法
     * @param $conditions [type|string] 查询条件集
     * @param $table [type|string] 表名
     * @param $single [type|boolean] 获取数据数量(单条、全部)
     * @return mixed
     */
    public function getDatas( $conditions=[], $table='', $single=false )
    {
        $sql_tmp = [];                                                                                                  // sql 语句拼装用的容器
        $bind = [];                                                                                                     // 参数绑定变量
        $tab_name = '';

        $field = !empty( $conditions["field"] ) ? $conditions["field"] : '*';
        $where = !empty( $conditions["where"] ) ? $this -> parseWhere( $conditions["where"] ) : [];
        $order = !empty( $conditions["order"] ) ? 'ORDER BY '.$conditions["order"] : '';
        $limit = !empty( $conditions["limit"] ) ? $conditions["limit"] : '';

        // 库和表名配置
        if( is_array( $table ) )
        {
            $db = !empty($table["db"])?$table["db"]:'';
            $tab = !empty($table["table"])?$table["table"]:'';

            // 可以实现切库
            if(!empty($db) && !empty($tab))
            {
                $this -> _link  = DI::getDefault() -> get( $db );

                $tab_name = "`{$tab}`";
            }
        }
        else
        {
            $table = !empty( $table ) ? $table : $this->_table;
            $tab_name = "`{$table}`";
        }

        # sql 语句智能拼装
        $sql_tmp[] = "SELECT {$field} FROM {$tab_name}";
        $sql_tmp[] = !empty( $where['template'] ) ? ' WHERE ' . $where['template'] : '';
        $sql_tmp[] = $order;
        $sql_tmp[] = $limit;

        $sql_tmp = array_filter( $sql_tmp );                // 过滤空数组
        $final_sql = implode( ' ', $sql_tmp );

        // 直接被拼接的查询子句
        if( !empty( $conditions["joint"] ) )
        {
            $final_sql = $final_sql . " {$conditions["joint"]}";
        }

        if( !empty( $where['bind'] ) )
        {
            $bind = $where['bind'];
        }

        $query = $this -> _link -> query( $final_sql, $bind );
        $query -> setFetchMode( Db::FETCH_ASSOC );

        // 返回值判断
        if( $single )
        {
            $data = $query -> fetch();
        }
        else
        {
            $data = $query -> fetchAll();
        }

        return $data;
    }

    public function newGetDatas( $conditions=[], $table='', $single=false )
    {
        $where = '`app_id` = '.$conditions['app_id']. ' AND `date` >= "'. $conditions['start'] .'"  AND `date` <= "'. $conditions['end'] .'" ';
        $sql = "SELECT 
                  r.`date`,
                  new_account,
                  amount,
                  days 
                FROM
                  (SELECT 
                    app_id,
                    channel,
                    device,
                    SUM(new_account) new_account,
                    `date` 
                  FROM
                    report_day 
                  WHERE $where
                  GROUP BY DATE) r 
                  JOIN 
                    (SELECT 
                      app_id,
                      channel,
                      device,
                      SUM(amount) amount,
                      `date`,
                      days 
                    FROM
                      ltv 
                    WHERE $where
                    GROUP BY DATE,
                      days) l 
                    ON r.app_id = l.app_id 
                    AND r.date = l.date 
                GROUP BY r.channel,
                  l.days,
                  l.date 
                ORDER BY l.date,
                  l.days ASC ";

        $query = $this -> _link -> query( $sql );
        $query -> setFetchMode( Db::FETCH_ASSOC );

        // 返回值判断
        if( $single )
        {
            $data = $query -> fetch();
        }
        else
        {
            $data = $query -> fetchAll();
        }

        return $data;
    }
}