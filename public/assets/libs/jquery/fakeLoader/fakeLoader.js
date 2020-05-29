/*--------------------------------------------------------------------
 * JAVASCRIPT "FakeLoader.js"
 * Version:    1.1.0 - 2014
 * Author:     João Pereira
 * Website:    http://www.joaopereira.pt
 * Extend By:  freejzco@sina.com
 *
 * Licensed MIT
 -----------------------------------------------------------------------*/

( function( $ )
{
    $.fn.fakeLoader = function( options )
    {
        //Defaults
        var settings = $.extend( {
            cancelHide: false,
            timeToHide:1200,                    // Default Time to hide fakeLoader
            pos:'fixed',                        // Default Position
            top:'0px',                          // Default Top value
            left:'0px',                         // Default Left value
            width:'100%',                       // Default width
            height:'100%',                      // Default Height
            zIndex: '999',                      // Default zIndex
            bgColor: '#2ecc71',                 // Default background color
            spinner:'spinner7',                 // Default Spinner
            imagePath:'',                       // Default Path custom image
            loadingTxt: '',
            extCss: {}                          // 自定义扩展 CSS (类型必须是 json 对象)
        }, options );

        //Customized Spinners
        var spinner01 = '<div class="fl spinner1"><div class="double-bounce1"></div><div class="double-bounce2"></div></div>';
        var spinner02 = '<div class="fl spinner2"><div class="spinner-container container1"><div class="circle1"></div><div class="circle2"></div><div class="circle3"></div><div class="circle4"></div></div><div class="spinner-container container2"><div class="circle1"></div><div class="circle2"></div><div class="circle3"></div><div class="circle4"></div></div><div class="spinner-container container3"><div class="circle1"></div><div class="circle2"></div><div class="circle3"></div><div class="circle4"></div></div></div>';
        var spinner03 = '<div class="fl spinner3"><div class="dot1"></div><div class="dot2"></div></div>';
        var spinner04 = '<div class="fl spinner4"></div>';
        var spinner05 = '<div class="fl spinner5"><div class="cube1"></div><div class="cube2"></div></div>';
        var spinner06 = '<div class="fl spinner6"><div class="rect1"></div><div class="rect2"></div><div class="rect3"></div><div class="rect4"></div><div class="rect5"></div></div>';
        var spinner07 = '<div class="fl spinner7"><div class="circ1"></div><div class="circ2"></div><div class="circ3"></div><div class="circ4"></div></div>';

        //The target
        var el = $( this );

        var spinner_data = {};

        //Init styles
        var initStyles = {
            'position':settings.pos,
            'width':settings.width,
            'height':settings.height,
            'top':settings.top,
            'left':settings.left
        };

        //Apply styles
        el.css( initStyles );

        //Each 
        el.each( function()
        {
            var a = settings.spinner;

            switch( a )
            {
                case 'spinner1':
                    el.html( spinner01 );
                    break;
                case 'spinner2':
                    el.html( spinner02 );
                    break;
                case 'spinner3':
                    el.html( spinner03 );
                    break;
                case 'spinner4':
                    el.html( spinner04 );
                    break;
                case 'spinner5':
                    el.html( spinner05 );
                    break;
                case 'spinner6':
                    el.html( spinner06 );
                    break;
                case 'spinner7':
                    el.html( spinner07 );
                    break;
                default:
                    el.html( spinner01 );
            }

            //Add customized loader image

            if( settings.imagePath != '' )
            {
                el.html( '<div class="fl"><img src="'+settings.imagePath+'"></div>' );
            }
            spinner_data = centerLoader();
            textLoader( el, spinner_data, settings.loadingTxt );
        });

        //Time to hide fakeLoader
        if( !settings.cancelHide )
        {
            setTimeout( function()
            {
                $( el ).fadeOut();
            }, settings.timeToHide );
        }

        //Return Styles
        var node_css = {
            'backgroundColor':settings.bgColor,
            'zIndex':settings.zIndex
        };

        return this.css( $.extend( true, {}, node_css, settings.extCss ) );
    }; // End Fake Loader


    function textLoader( obj, measure, txt )
    {
        var _text = $( '<h5></h5>' );
        var _css = $.extend( true, {}, measure );

        if( txt == '' )
        {
            txt = 'Loading...';
        }
        else
        {
            delete _css.width;
        }

        _text.css( $.extend( true, {}, _css, {"color":"#FFFFFF", "text-align": "center"} ) );
        _text.text( txt );

        obj.prepend( _text );
    }

    //Center Spinner
    function centerLoader()
    {
        var _node = $( '.fl' );
        var winW = $( window ).width();
        var winH = $( window ).height();

        var spinnerW = _node.outerWidth();
        var spinnerH = _node.outerHeight();

        _node.css( {
            'position':'absolute',
            'left':( winW / 2 ) - ( spinnerW / 2 ),
            'top':( winH / 2 ) - ( spinnerH / 2 )
        } );

        return {
            'position':'absolute',
            'left':( winW / 2 ) - ( spinnerW / 2 ),
            'top':( winH / 2 ) - ( spinnerH / 2 ),
            'width': _node.width()
        };
    }

    $( window ).load( function()
    {
        centerLoader();
        $( window ).resize( function()
        {
            centerLoader();
        } );
    } );

}( jQuery ) );


