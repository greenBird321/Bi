<?php
namespace MyApp\Models\Backend;


use MyApp\Models\Base;
use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;
use Phalcon\Security\Random;

class GameServer extends Base
{
    private $_link;
    private $_table;

    public function onConstruct()
    {
        $this->_link = DI::getDefault()->get('dbData');
        $this->_table = 'game_server';
    }

    public function backDatas()
    {
        $result = [
            '1031019' => [
                '1' => ['id' => 1, 'name' => '1(ssss2-S1)'],
                '2' => ['id' => 2, 'name' => '2(ssss2-S2)'],
                '3' => ['id' => 3, 'name' => '3(ssss2-S3)'],
                '4' => ['id' => 4, 'name' => '4(ssss2-S4)'],
            ],
            '1021013' => [
                '1' => ['id' => 1, 'name' => '1(ssss-S1)'],
                '2' => ['id' => 2, 'name' => '2(ssss-S2)'],
                '3' => ['id' => 3, 'name' => '3(ssss-S3)'],
                '4' => ['id' => 4, 'name' => '4(ssss-S4)'],

            ],
            '1041020' => [
                '1' => ['id' => 1, 'name' => '1(ssss-S1)'],
            ],
            // 新增lmyz-dev区服
            '1051021' => [
                '1' => ['id' => 1, 'name' => 'Lmyz-S1-Dev'],
                '2' => ['id' => 2, 'name' => 'Lmyz-S2-Dev'],
            ],
            // 新增lmyz-open区服
            '1051020' => [
                '1' => ['id' => 1, 'name' => 'Lmyz-S1-open'],
                '2' => ['id' => 1, 'name' => 'Lmyz-S2-open'],
            ],
        ];

        return $result;
    }

    /**
     * des: 通用数据查询方法
     * @param $conditions [type|string] 查询条件集
     * @param $table [type|string] 表名
     * @param $single [type|boolean] 获取数据数量(单条、全部)
     * @return mixed
     */
    public function getDatas($conditions = [], $table = '', $single = false)
    {
        $sql_tmp = [];                                                                                                  // sql 语句拼装用的容器
        $bind = [];                                                                                                     // 参数绑定变量
        $tab_name = '';

        $field = !empty($conditions["field"]) ? $conditions["field"] : '*';
        $where = !empty($conditions["where"]) ? $this->parseWhere($conditions["where"]) : [];
        $order = !empty($conditions["order"]) ? 'ORDER BY ' . $conditions["order"] : '';
        $limit = !empty($conditions["limit"]) ? $conditions["limit"] : '';

        // 库和表名配置
        if (is_array($table)) {
            $db = !empty($table["db"]) ? $table["db"] : '';
            $tab = !empty($table["table"]) ? $table["table"] : '';

            // 可以实现切库
            if (!empty($db) && !empty($tab)) {
                $this->_link = DI::getDefault()->get($db);

                $tab_name = "`{$tab}`";
            }
        }
        else {
            $tab_name = "`{$table}`";
        }

        # sql 语句智能拼装
        $sql_tmp[] = "SELECT {$field} FROM {$tab_name}";
        $sql_tmp[] = !empty($where['template']) ? ' WHERE ' . $where['template'] : '';
        $sql_tmp[] = $order;
        $sql_tmp[] = $limit;

        $sql_tmp = array_filter($sql_tmp);                // 过滤空数组
        $final_sql = implode(' ', $sql_tmp);

        // 直接被拼接的查询子句
        if (!empty($conditions["joint"])) {
            $final_sql = $final_sql . " {$conditions["joint"]}";
        }

        if (!empty($where['bind'])) {
            $bind = $where['bind'];
        }

        $query = $this->_link->query($final_sql, $bind);
        $query->setFetchMode(Db::FETCH_ASSOC);

        // 返回值判断
        if ($single) {
            $data = $query->fetch();
        }
        else {
            $data = $query->fetchAll();
        }

        return $data;
    }
}