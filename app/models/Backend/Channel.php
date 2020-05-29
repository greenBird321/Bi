<?php
namespace MyApp\Models\Backend;


use MyApp\Models\Base;
use Phalcon\Mvc\Model;
use Phalcon\DI;
use Phalcon\Db;
use Phalcon\Security\Random;

class Channel extends Base
{
    private $_link;
    private $_table;

    public function onConstruct()
    {
        $this->_link = DI::getDefault()->get('dbData');
        $this->_table = 'channel';
    }

    public function backDatasNew($default_channel)
    {
        if (empty($default_channel)) {
            $this -> _link  = DI::getDefault() -> get( "dbTop" );
            # sql 语句智能拼装
            $sql = "SELECT id,channel as name,channel_name as ch FROM channels";
            $query = $this -> _link -> query( $sql );
            $query -> setFetchMode( Db::FETCH_ASSOC );
            $result = $query -> fetchAll();
        } else {
            foreach ($default_channel as $key => $value) {
                $result[$key]['id'] = $value['id'];
                $result[$key]['name'] = $value['channel'];
                $result[$key]['ch'] = $value['channel_name'];
            }
        }
        return $result;
    }

    public function backDatas()
    {
        $result = [
            array("id" => 1, "name" => "baidu", "ch" => "百度"),
            array("id" => 2, "name" => "qihu360", "ch" => "奇虎360"),
            array("id" => 3, "name" => "vivo", "ch" => "VIVO"),
            array("id" => 4, "name" => "downjoy", "ch" => "当乐"),
            array("id" => 5, "name" => "uc", "ch" => "UC"),
            array("id" => 6, "name" => "lenovo", "ch" => "联想"),
            array("id" => 7, "name" => "meizu", "ch" => "魅族"),
            array("id" => 8, "name" => "amigo", "ch" => "金立"),
            array("id" => 9, "name" => "oppo", "ch" => "OPPO"),
            array("id" => 10, "name" => "huawei", "ch" => "华为"),
            array("id" => 11, "name" => "mi", "ch" => "小米"),
            array("id" => 12, "name" => "feiliu", "ch" => "飞流"),
            array("id" => 13, "name" => "yqq", "ch" => "应用宝(qq)"),
            array("id" => 14, "name" => "yweixin" , "ch" => "应用宝(微信)"),
            array("id" => 15, "name" => "baiwen", "ch" => "百文"),
            array("id" => 16, "name" => "anzhi", "ch" => "安智"),
            array("id" => 17, "name" => "coolpad", "ch" => "酷派"),
            array("id" => 18, "name" => "yiwan", "ch" => "益玩"),
            array("id" => 19, "name" => "iqy", "ch" => "爱奇艺"),
            array("id" => 19, "name" => "kuaikan", "ch" => "快看"),
            array("id" => 19, "name" => "leyou", "ch" => "乐游"),
            array("id" => 19, "name" => "yueyou", "ch" => "悦游"),
            array("id" => 19, "name" => "jile", "ch" => "吉乐"),
            array("id" => 19, "name" => "sdk4399", "ch" => "4399"),
            array("id" => 19, "name" => "samsung", "ch" => "三星"),
            array("id" => 19, "name" => "sdk179", "ch" => "179"),
            array("id" => 19, "name" => "meitu", "ch" => "美图")
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