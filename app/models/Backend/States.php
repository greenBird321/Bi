<?php
namespace MyApp\Models\Backend;


use MyApp\Models\Base;
use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;
use Phalcon\Security\Random;

class States extends Base
{
    private $_link;
    private $_table;

    public function onConstruct()
    {
        $this->_link = DI::getDefault()->get('dbData');
        $this->_table = 'states';
    }

    public function backDatas()
    {
        $result = [
            array("id" => 1, "name" => "US", "ch" => "美国"),
            array("id" => 2, "name" => "VIE", "ch" => "越南"),
            array("id" => 3, "name" => "TR", "ch" => "土耳其"),
            array("id" => 4, "name" => "EN", "ch" => "英国"),
            array("id" => 5, "name" => "TH", "ch" => "泰国"),
            array("id" => 6, "name" => "PHI", "ch" => "菲律宾"),
            array("id" => 7, "name" => "RU", "ch" => "俄罗斯"),
            array("id" => 8, "name" => "CA", "ch" => "加拿大"),
            array("id" => 9, "name" => "BR", "ch" => "巴西"),
            array("id" => 10, "name" => "JP", "ch" => "日本")
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