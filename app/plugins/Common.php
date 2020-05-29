<?php
/**
 * Package  Common.php
 * Author:  joe@xxtime.com
 * Date:    2015-07-20
 * Time:    上午12:43
 * Link:    http://www.xxtime.com
 */

if (!function_exists('debug')) {
    function debug()
    {
        echo "<meta charset='UTF-8'><pre style='padding:20px; background: #000000; color: #FFFFFF;'>\r\n";
        if (func_num_args()) {
            foreach (func_get_args() as $k => $v) {
                echo "------- Debug $k -------<br/>\r\n";
                print_r($v);
                echo "<br/>\r\n";
            }
        }
        echo '</pre>';
        exit;
    }
}

if (!function_exists('writeLog')) {
    function writeLog($log = '', $file = 'logs.txt')
    {
        global $config;
        $log_file = APP_DIR . '/logs/' . $file;
        $handle = fopen($log_file, "a+b");
        $text = date('Y-m-d H:i:s') . ' ' . $log . "\r\n";
        fwrite($handle, $text);
        fclose($handle);
    }
}

if (!function_exists('dateCompare')) {
    /**
     * des: 比较两天大小, 最终返回数组, 天数大的靠前 array( '大', '小' )
     * @param $day1 [type|string]
     * @param $day2 [type|string]
     * @return array
     */
    function dateCompare($day1, $day2)
    {
        // 没有正确数据传入
        if (empty($day1) && empty($day2)) {
            $result = [];
        } else {
            if (empty($day1) || empty($day2)) {
                if (empty($day1) && !empty($day2)) {
                    $time_1 = strtotime(date('Y-m-d ', strtotime($day2)) . ' 00:00:00');
                    $time_2 = strtotime(date('Y-m-d ', strtotime($day2)) . ' 23:59:59');
                }

                if (!empty($day1) && empty($day2)) {
                    $time_1 = strtotime(date('Y-m-d ', strtotime($day1)) . ' 00:00:00');
                    $time_2 = strtotime(date('Y-m-d ', strtotime($day1)) . ' 23:59:59');
                }
            } else {
                $time_1 = strtotime($day1);
                $time_2 = strtotime($day2);
            }

            if ($time_1 < $time_2) {
                $tmp = $time_2;
                $time_2 = $time_1;
                $time_1 = $tmp;
            }

            $result = array(date('Y-m-d H:i:s', $time_2), date('Y-m-d H:i:s', $time_1));
        }

        return $result;
    }
}

if (!function_exists('array_merge_smart')) {
    /**
     * des: 数组合并, 避免 array_merge 合并空数组会有错误
     * @param $target [type|array] 待组合的数组
     * @param $extend [type|array] 待组合的数组
     * @return array
     */
    function array_merge_smart($target, $extend)
    {
        $result = [];

        if (empty($target) && empty($extend)) {
            $result = [];
        } else {
            if (!empty($target) && empty($extend)) {
                $result = $target;
            } else {
                if (empty($target) && !empty($extend)) {
                    $result = $extend;
                } else {
                    $result = array_merge($target, $extend);
                }
            }
        }

        return $result;
    }
}

if (!function_exists('array_column_index')) {
    /**
     * des: 将二维数组的某个列作为键, 会将相同键的数据合并成一个新数组
     * @param $arr [type|array] 数据(二维数组)
     * @param $index [type|string] 作为键的某列
     * @return array
     */
    function array_column_index($arr, $index = '')
    {
        $new = array();
        $used = array();

        // 必须确定列名是存在的
        if (!empty($arr) && !empty($index)) {
            foreach ($arr as $key => $value) {
                // 非数组则不执行
                if (!is_array($value)) {
                    $new = $arr;
                    break;
                }

                // 检查该键是否已经使用, 使用过则需要合并相同键内的数据
                if (!empty($used[$value[$index]])) {
                    $used[$value[$index]][] = $value;
                    $new[$value[$index]] = $used[$value[$index]];
                } else {
                    $used[$value[$index]][] = $value;       // 标记是否使用
                    $new[$value[$index]][] = $value;
                }
            }
        } else {
            $new = $arr;
        }

        return $new;
    }
}

if (!function_exists('depthOfArray')) {
    /**
     * 返回数组的维度
     * @param  $array [type|array] 待判断维度的数组
     * @return array
     */
    function depthOfArray( $array )
    {
        if( !is_array( $array ) )
        {
            return 0;
        }
        else
        {
            $level = 0;
            foreach( $array as $detail )
            {
                $tmp = depthOfArray( $detail );
                if( $tmp > $level )
                {
                    $level = $tmp;
                }
            }
            return $level + 1;
        }
    }
}