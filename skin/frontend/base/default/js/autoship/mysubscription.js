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

function updateMySubscription(form) {
    $j("div.change,#modal_overlay").hide();
    $j("#please-wait").show();
    $j(".subscription-block .messages").html("");
    $j.ajax({
        url: form.attr('action'),
        data: form.serialize(),
        method: "post",
        success: function(transport){
            $j("#please-wait").hide();
            if(transport.match("error")){
                form.parents(".subscription-block").find(".messages").html(transport);
            } else {
                form.parents(".subscription-block").html(transport).highlight();
            }
        }
    });
}

function updateAllSubscriptions(form) {
    $j("div.change,#modal_overlay").hide();
    $j("#please-wait").show();
    $j(".subscription-block .messages").html("");
    $j.ajax({
        url: form.attr('action'),
        data: form.serialize(),
        method: "post",
        success: function(transport){
            $j("#please-wait").hide();
            if(transport.match("error")){
                form.parents(".my-account").find(".messages").html(transport);
            } else {
                form.parents(".my-account").html(transport).highlight();
            }
        }
    });
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
        $j('div.change').hide();
        $j(this).hide();
    }).hide();

    $j(".my-account").on('click', "a.change", function(e){
        e.preventDefault();
        var box = $j(this).parents(".block").find("div.change");
        box.css({
            "top": $j(window).scrollTop() + 50,
            "left": ($j(window).width() / 2) - (box.width() / 2)
        }).show();
        $j('#modal_overlay').show();
    }).on('submit', 'form.payment-form', function(e){
        e.preventDefault();
        var form = $j(this);
        if(editMultipleSubscriptions == true) {
            updateAllSubscriptions(form);
        }
        else {
            updateMySubscription(form);
        }
    }).on('change', '.shipping-address-select', function(){
        if($j(this).val() == ""){
            $j(this).closest('.co-shipping-form').find('.shipping-new-address-form').show();
        } else {
            $j(this).closest('.co-shipping-form').find('.shipping-new-address-form').hide();
        }
    }).on('submit', 'form.co-shipping-form', function(e){
        e.preventDefault();
        var form = $j(this);
        if(editMultipleSubscriptions == true) {
            updateAllSubscriptions(form);
        }
        else {
            updateMySubscription(form);
        }
    }).on('change', '.billing-address-select', function(){
        if($j(this).val() == ""){
            $j(this).closest('.co-billing-form').find('.billing-new-address-form').show();
        } else {
            $j(this).closest('.co-billing-form').find('.billing-new-address-form').hide();
        }
    }).on('submit', 'form.co-billing-form', function(e){
        e.preventDefault();
        var form = $j(this);
        if(editMultipleSubscriptions == true) {
            updateAllSubscriptions(form);
        }
        else {
            updateMySubscription(form);
        }
    }).on("click", "a.link.more.details", function(e){
        e.preventDefault();
        var link = $j(this);
        $j('#'+link.attr('href')).toggle();
    }).on("change", "select.delivery_qty", function(e){
        e.preventDefault();
        var form = $j(this).closest("form");
        updateMySubscription(form);
    }).on("change", "select.delivery_interval", function(e){
        e.preventDefault();
        var form = $j(this).closest("form");
        updateMySubscription(form);
    }).on("click", "button.change.skip", function(e){
        e.preventDefault();
        var box = $j(this).parents(".subscription-block").find("div.change.skip-delivery");
        box.css({
            "top": $j(window).scrollTop() + 50,
            "left": ($j(window).width() / 2) - (box.width() / 2)
            }).show();
        $j('#modal_overlay').show();
    }).on("click", "a.change.cancel", function(e){
        e.preventDefault();
        var box = $j(this).parents(".subscription-block").find("div.change.cancel");
        box.css({
            "top": $j(window).scrollTop() + 50,
            "left": ($j(window).width() / 2) - (box.width() / 2)
        }).show();
        $j('#modal_overlay').show();
    }).on("click", "a.change.restart", function(e){
        e.preventDefault();
        var box = $j(this).parents(".subscription-block").find("div.change.restart");
        box.css({
            "top": $j(window).scrollTop() + 50,
            "left": ($j(window).width() / 2) - (box.width() / 2)
        }).show();
        $j('#modal_overlay').show();
    }).on("click", "button.no", function(e){
        $j("div.change,#modal_overlay").hide();
    }).on("click", "button.skip_yes", function(e){
        e.preventDefault();
        var form = $j(this);
        $j("div.change,#modal_overlay").hide();
        $j("#please-wait").show();
        $j(".subscription-block .messages").html("");
        $j.ajax({
            url: form.attr('href'),
            method: "get",
            success: function(transport){
                $j("#please-wait").hide();
                if(transport.match("error")){
                    form.parents(".subscription-block").find(".messages").html(transport);
                } else {
                    form.parents(".subscription-block").html(transport).highlight();
                }
            }
        });
    }).on("click", "button.cancel_yes", function(e){
        e.preventDefault();
        var form = $j(this);
        $j("div.change,#modal_overlay").hide();
        $j("#please-wait").show();
        $j(".subscription-block .messages").html("");
        $j.ajax({
            url: form.attr('href'),
            method: "get",
            success: function(transport){
                $j("#please-wait").hide();
                if(transport.match("error")){
                    form.parents(".subscription-block").find(".messages").html(transport);
                } else {
                    form.parents(".subscription-block").fadeOut(1000);
                    $j(".inactive-subscriptions").prepend('<div class="subscription-block">' + transport + '</div>');
                }
            }
        });
    }).on("click", "button.restart_yes", function(e){
        e.preventDefault();
        var form = $j(this);
        $j("div.change,#modal_overlay").hide();
        $j("#please-wait").show();
        $j(".subscription-block .messages").html("");
        $j.ajax({
            url: form.attr('href'),
            method: "get",
            success: function(transport){
                $j("#please-wait").hide();
                if(transport.match("error")){
                    form.parents(".subscription-block").find(".messages").html(transport);
                } else {
                    form.parents(".subscription-block").html(transport).highlight();
                }
            }
        });
    });

});
