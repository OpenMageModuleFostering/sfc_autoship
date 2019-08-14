/**
 * Subscribe Pro - Subscriptions Management Extension
 *
 * PHP version 5
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

$j = jQuery;

function updateSubscriptionData(transport) {
    $j('#updated-subscription-content').append(transport);
    $j('.new-subscription').find('.billing-address-block').html($j('#updated-subscription-content > .billing-address-block'));
    $j('.new-subscription').find('.shipping-address-block').html($j('#updated-subscription-content > .shipping-address-block'));
    $j('#updated-subscription-content > .payment-block .input-box input').val($j('.new-subscription .payment-block .input-box input').val());
    $j('.new-subscription').find('.payment-block').html($j('#updated-subscription-content > .payment-block'));
    $j('.new-subscription').find('.summary-block').html($j('#updated-subscription-content > .summary-block'));
    $j('#updated-subscription-content').html('');

    var billingForm = new VarienForm('co-billing-form-');
    var billingAddressRegionUpdater = new RegionUpdater(':billing:country_id', ':billing:region', ':billing:region_id', regionsJson, undefined, ':billing:postcode');
    var shippingForm = new VarienForm('co-shipping-form-');
    var shippingAddressRegionUpdater = new RegionUpdater(':shipping:country_id', ':shipping:region', ':shipping:region_id', regionsJson, undefined, ':shipping:postcode');

}

$j(document).ready(function(){

    $j(".wrapper").after("<div id='modal_overlay'></div>");

    $j('#modal_overlay').css({
        position: "absolute",
        top: 0,
        left: 0,
        height: $j(document).height(),
        width: "100%",
        zIndex: 900
    }).bind('click', function(){
        $j('div.change,#please-wait').hide();
        $j(this).hide();
    }).hide();

    $j(".new-subscription").on('click', 'a.change', function(e){
        e.preventDefault();
        var box = $j(this).parents(".block").find("div.change");
        box.css({
            "top": $j(window).scrollTop() + 50,
            "left": ($j(window).width() / 2) - (box.width() / 2)
            }).show();
        $j('#modal_overlay').show();
    }).on('change', '.billing-address-select', function(){
        if($j(this).val() == ""){
            $j('.billing-new-address-form').show();
        } else {
            $j('.billing-new-address-form').hide();
        }
    }).on('submit', '.co-billing-form', function(e){
        e.preventDefault();
        var form = $j(this);
        $j("#please-wait").show();
        $j('.billing-address-block .messages').html("");
        $j.ajax({
            url: form.attr('action'),
            data: form.serialize(),
            method: "post",
            success: function(transport){
                $j("#please-wait").hide();
                if(transport.match("error")){
                    $j('.billing-address-block .messages').html(transport);
                } else {
                    updateSubscriptionData(transport);
                    $j('div.change,#modal_overlay').hide();
                }
            }
        });
    }).on('submit', '#co-payment-form', function(e){
        e.preventDefault();
        var form = $j(this);
        $j("#please-wait").show();
        $j('.payment-block .messages').html("");
        $j.ajax({
            url: form.attr('action'),
            data: form.serialize(),
            method: "post",
            success: function(transport){
                $j("#please-wait").hide();
                if(transport.match("error")){
                    $j('.payment-block .messages').html(transport);
                } else {
                    updateSubscriptionData(transport);
                    $j('div.change,#modal_overlay').hide();
                }
            }
        });
    }).on('change', '.shipping-address-select', function(){
        if($j(this).val() == ""){
            $j('.shipping-new-address-form').show();
        } else {
            $j('.shipping-new-address-form').hide();
        }
    }).on('submit', '.co-shipping-form', function(e){
        e.preventDefault();
        var form = $j(this);
        $j("#please-wait").show();
        $j('.shipping-address-block .messages').html("");
        $j.ajax({
            url: form.attr('action'),
            data: form.serialize(),
            method: "post",
            success: function(transport){
                $j("#please-wait").hide();
                if(transport.match("error")){
                    $j('.shipping-address-block .messages').html(transport);
                } else {
                    updateSubscriptionData(transport);
                    $j('div.change,#modal_overlay').hide();
            }
            }
        });
    }).on('click', '#subscribe', function(e){
        e.preventDefault();
        var form = $j(this);
        $j("#please-wait").show();
        $j('.summary-block .messages').html("");
        $j.ajax({
            url: form.attr('href'),
            data: {"delivery_date":$j("#delivery_date").val(), "coupon_code":$j("#coupon_code").val()},
            method: "post",
            success: function(transport){
                $j("#please-wait").hide();
                if(transport.match("error")){
                    $j('.summary-block .messages').html(transport);
                } else {
                    window.location = transport;
                }
            }
        });
    });
    
    $j("#delivery_qty,#delivery_interval").bind('change', function(e){
        $j("#please-wait").show();
        var form = $j("#frequency_form"); // fake form
        $j.ajax({
            url: form.val(),
            data: {
                "qty": $j("#delivery_qty").val(),
                "interval": $j("#delivery_interval").val()
            },
            method: "post",
            success: function(transport){
                $j("#please-wait").hide();
                updateSubscriptionData(transport);
            }
        });        
    });

});
