/**
 * Subscribe Pro - Subscriptions Management Extension
 *
 * LICENSE: This source file is subject to commercial source code license of SUBSCRIBE PRO INC.
 *
 * @category  SFC
 * @package   SFC_Autoship
 * @author    Garth Brantley <garth@subscribepro.com>
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2009-2014 SUBSCRIBE PRO INC. All Rights Reserved.
 * @license   http://www.subscribepro.com/terms-of-service/ Subscribe Pro Terms of Service
 * @link      http://www.subscribepro.com/
 *
 */

$j = jQuery.noConflict(); 
jQuery.fn.highlight = function() {
   $j(this).each(function() {
        var el = $j(this);
        el.before("<div style='position:absolute;'></div>")
        el.prev()
            .width(el.width())
            .height(el.height())
            .css({
                "background-color": "#F96D3B",
                "opacity": ".9"   
            })
            .fadeOut(700, function(){$j(this).remove()});
    });
    return $j(this);
}