/**
 * des: 获取日期(不包含时间获取)
 * @param day [type|int] 0.当天 -1.前一天 +1.后一天
 * @param split [type|string] 日期的分隔符
 * @returns {*}
 */
function getDate( day, split )
{
    var _split = split ? split : '-';
    var date = new Date();
    date.setDate( date.getDate() + day );//获取AddDayCount天后的日期
    var y = date.getFullYear();
    var m = date.getMonth() + 1;//获取当前月份的日期
    var d = date.getDate();

    // 分隔符的范围验证
    if( split != '/' && split != '-' )
    {
        _split = '-';
    }

    return y + _split + m + _split + d;
}

/**
 * des: 获取当前日期在当前年第几周
 * @param time
 * @returns {number}
 */
function getWeek( time )
{
    var totalDays = 0;
    var now = new Date( time );
    var years = now.getYear();

    if(years < 1000)
    {
        years += 1900;
    }

    var days = new Array(12);
    days[0] = 31;
    days[2] = 31;
    days[3] = 30;
    days[4] = 31;
    days[5] = 30;
    days[6] = 31;
    days[7] = 31;
    days[8] = 30;
    days[9] = 31;
    days[10] = 30;
    days[11] = 31;

    //判断是否为闰年，针对2月的天数进行计算
    if (Math.round(now.getYear() / 4) == now.getYear() / 4) {
        days[1] = 29
    } else {
        days[1] = 28
    }

    if (now.getMonth() == 0) {
        totalDays = totalDays + now.getDate();
    } else {
        var curMonth = now.getMonth();
        for (var count = 1; count <= curMonth; count++) {
            totalDays = totalDays + days[count - 1];
        }
        totalDays = totalDays + now.getDate();
    }

    //得到第几周
    var week = Math.round(totalDays / 7);
    return week;
}

/**
 * des: 载入过度遮罩效果
 * @param: txt [type|string] 载入提示
 */
function loading( txt )
{
    // var loader = $(".fakeloader"),
    //     status = loader.attr( "data-status" );
    // var text = !_.isEmpty(txt) ? txt : '处理中, 请稍后...';
    //
    // // 无需重复实例化, 可以服用特效
    // if( status == 'init' )
    // {
    //     // 载入特效
    //     loader.fakeLoader({
    //         cancelHide: true,
    //         bgColor: "#e74c3c",
    //         zIndex: 9999,
    //         extCss: {
    //             "background-color": "#000000",
    //             "opacity": "0.8",
    //             "-moz-opacity": "0.8",
    //             "filter": "alpha(opacity=80)"
    //         },
    //         spinner: "spinner7",
    //         loadingTxt: text
    //     });
    //
    //     loader.attr({"data-status":"used"});                // 修改遮罩层的状态
    // }
    // else
    // {
    //     loader.fadeIn();
    // }
    return true;
}

/**
 * des: 插件懒惰载入或重载
 * @param addr [type|string] 地址
 */
function pluginReload( addr )
{
    // 寻址判断
    var dom_obj = ( typeof addr != 'undefined' ) ? $( addr ).find( "[ui-jq]" ) : $( "[ui-jq]" );

    dom_obj.each( function()
    {
        var self = $( this );
        var options = eval( '[' + self.attr('ui-options') + ']' );

        if( $.isPlainObject( options[0] ) )
        {
            options[0] = $.extend( {}, options[0] );
        }

        // 加载资源文件列表
        var if_existed = document.getElementsByTagName( 'script' );
        var if_loaded = false;

        // 是否已经加载判断
        for( var iCount in if_existed )
        {
            if( typeof if_existed[iCount]['src'] != 'undefined' && if_existed[iCount]['src'].indexOf( self.attr('ui-jq') ) >= 0 )
            {
                if_loaded = true;
                break;
            }
        }

        if( !if_loaded )
        {
            uiLoad.load( jp_config[self.attr('ui-jq')] ).then( function()
            {
                plugInit( self, options );
            } );
        }
        else
        {
            plugInit( self, options );
        }
    } );
}

/**
 * des: 插件初始化方法, 不可复用
 */
function plugInit( obj, params )
{
    switch( obj.attr( 'ui-jq' ) )
    {
        case 'dataTable':
            var extend = ( obj.attr('ui-extend') != undefined ) ? JSON.parse( obj.attr('ui-extend') ) : 'undefined';
            var data_len = params[0]['columns'].length;
            var check = $(extend[0]['bind']+'_wrapper').length;

            if( extend != 'undefined' && check < 1 )
            {
                var table = $( extend[0]['bind'] ).DataTable( params[0] );
                var hides = ( typeof extend[0]['default'] != 'undefined' ) ? extend[0]['default'] : '';

                if( hides )
                {
                    // 默认事件
                    for( var iCount in hides )
                    {
                        var column = table.column( hides[iCount] );
                        column.visible( !column.visible() );
                    }
                }

                // 触发事件
                if( typeof extend[0]['click'] != 'undefined' )
                {
                    $( extend[0]['click'] ).on( 'click', function( e )
                    {
                        e.preventDefault();
                        var _that = $( this );
                        var _origin = JSON.parse( _that.attr( 'data-column' ) );
                        var _values = _origin[0]['value'],
                            _name = _origin[0]['name'];
                        var _column_index = _values[_name];
                        var column = '';

                        // 先隐藏全部数据列
                        for( var iCount = 0; iCount < data_len; iCount++ )
                        {
                            column = table.column( iCount );
                            column.visible( false );
                        }

                        // 显示选中的数据列
                        for( var iCount in _column_index )
                        {
                            column = table.column( _column_index[iCount] );
                            column.visible( true );
                        }
                    } );
                }
                //$( extend[0]['bind'] ).DataTable().destroy();
            }
            break;
        case 'duallistbox':
            obj.bootstrapDualListbox( params[0] );
            break;
        case 'amcharts':
            amChartsRander( obj.attr('id'), params[0], obj.attr('ui-config'), obj );
            break;
        default:
            obj[obj.attr('ui-jq')].apply( obj, params );
            // console.log(obj.attr('ui-jq'));
    }
}

/**
 * des: amcharts 图生成
 * @param addr
 * @param data [type|string] 参数集
 * @param index [type|string] 键名
 * @param obj [type|object] 节点对象
 */
function amChartsRander( addr, data, index, obj )
{

    //var dup = data.concat();                                                                // 建立数据副本
    var dup = data; //原写法报错
    // 屏蔽条件不满足情况
    if( typeof addr != 'undefined' && dup.length > 0 && typeof(amChartConfig)!="undefined" )
    {
        var chart = null;               // 变量初始化
        if(typeof(amChartConfig[index])=="undefined"){
            return;
        }

        // 图像类型选择与初始化过程
        switch( amChartConfig[index]["type"] )
        {
            case 'serial':
                if( typeof amChartConfig[index] == 'undefined' || typeof chartGraphsTmp[index] == 'undefined' || typeof chartTmpDetail[index] == 'undefined' )
                {
                    console.log( '请在 amChartConfig、chartGraphsTmp、chartTmpDetail 变量中配置参数' );
                }
                else
                {
                    amChartConfig[index]["dataProvider"] = dup;
                    var graphs_conf = chartGraphsTmp[index],
                        graphs_data = [];
                    var detail = chartTmpDetail[index];

                    // 通过对数据的处理自动生成 graphs 属性
                    for( var iCount in detail )
                    {
                        var tmp = $.extend( true, {}, graphs_conf );                                // 创建模板副本

                        tmp["id"] = detail[iCount]["id"];
                        tmp["valueField"] = detail[iCount]["field"];
                        tmp["title"] = detail[iCount]["title"];

                        graphs_data.push( tmp );
                    }

                    amChartConfig[index]["graphs"] = graphs_data;
                    chart = AmCharts.makeChart( addr, amChartConfig[index] );                   // 生成图

                    zoomChart();

                    function zoomChart()
                    {
                        chart.zoomToIndexes( chart.dataProvider.length - 40, chart.dataProvider.length - 1 );
                    }
                }
                break;
            case 'pie':
                var skip = obj.attr( "ui-skip" );

                // 是否生成跳转辅助 <a> 标签
                if( typeof skip != 'undefined' )
                {
                    for( var key in dup )
                    {
                        var href = skip+'='+dup[key]["id"];
                        var a_tag = $( "<a data-pjax id='"+dup[key]["id"]+"' href='"+href+"' style='display: none;'>"+dup[key]["category"]+"</a>" );

                        $( '#'+addr).parent().append( a_tag );
                    }
                }

                amChartConfig[index]["dataProvider"] = dup;
                chart = AmCharts.makeChart( addr, amChartConfig[index] );                   // 生成图
                break;
        }
    }
}