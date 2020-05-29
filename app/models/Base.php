<?php
namespace MyApp\Models;
use Phalcon\Mvc\Model;

use Phalcon\DI;
use Phalcon\Db;
use Phalcon\Security\Random;

class Base extends Model
{
    /**
     * des: 将数据拆分成具备绑定参数的 sql 语句和数据数组
     * format:
     *   "params" => array(
     *       "字段名"   => array(
     *           'symbol'    => '符号',
     *           'value'     => '值'
     *       )
     *   )
     *
     * @param $data [type|array] 参数集
     * @return array( "template" => string, "bind" => array )
     */
    protected function parseWhere( $data )
    {
        $grps = array(
            "template"  => [],          // 绑定模板
            "binds"     => [],          // 绑定的参数
            "special"   => []           // 特殊组合
        );
        $result = [];

        if( !empty( $data ) )
        {
            foreach( $data as $key => $detail )
            {
                $type = !empty( $detail["type"] ) ? $detail["type"] : '';

                // 处理分发过程
                switch( $type )
                {
                    // 多用于拼接时间范围查询条件
                    case "range":
                        if ($detail['start']) {
                            $tmp = "`$key` >= '{$detail["start"]}'";
                            array_push( $grps["special"], $tmp );
                        }
                        if ($detail['end']) {
                            $tmp = "`$key` <= '{$detail["end"]}'";
                            array_push( $grps["special"], $tmp );
                        }
                        break;
                    case "in":
                        if( is_array( $detail["value"] ) )
                        {
                            $tmp = "`{$key}` in ('".implode( '\',\'', $detail["value"] )."')";
                            array_push( $grps["special"], $tmp );
                        }
                        break;
                    default:
                        $tmp = "`$key` {$detail["symbol"]} :{$key}";

                        // 非空判断
                        if( !empty( $tmp ) )
                        {
                            array_push( $grps["template"], $tmp );

                            // 避免空数组合并结果为空数组
                            if( empty( $grps["binds"] ) )
                            {
                                $grps["binds"][':'.$key] = $detail["value"];
                            }
                            else
                            {
                                $grps["binds"] = array_merge( $grps["binds"], array( ':'.$key => $detail["value"] ) );
                            }
                        }
                }
            }

            // 组合特殊条件拼接
            if( !empty( $grps["special"] ) )
            {
                array_push( $grps["template"], implode( ' AND ', $grps["special"] ) );
            }

            $template = implode( ' AND ', $grps["template"] );

            $result = ["template" => $template, "bind" => $grps["binds"]];
        }

        return $result;
    }
}