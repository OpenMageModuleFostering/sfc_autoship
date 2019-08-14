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
    jQuery("div.change,#modal_overlay").hide();
    jQuery("#please-wait").show();
    jQuery(".subscription-block .messages").html("");
    jQuery.post(form.attr('action'), form.serialize(), function(transport){
        jQuery("#please-wait").hide();
        if(transport.match("error")){
            form.parents(".subscription-block").find(".messages").html(transport);
        } else {
            form.parents(".subscription-block").html(transport).highlight();
        }
    });
}

function updateAllSubscriptions(form) {
    jQuery("div.change,#modal_overlay").hide();
    jQuery("#please-wait").show();
    jQuery(".subscription-block .messages").html("");
    jQuery.post(form.attr('action'), form.serialize(), function(transport){
        $j("#please-wait").hide();
        if(transport.match("error")){
            form.parents(".my-account").find(".messages").html(transport);
        } else {
            form.parents(".my-account").html(transport).highlight();
        }
    });
}

jQuery(document).ready(function(){

    jQuery(".wrapper").after("<div id='modal_overlay'></div>");
    jQuery('#modal_overlay').css({
        position: "absolute",
        top: 0,
        left: 0,
        height: jQuery(document).height(),
        width: "100%",
        zIndex: 900
    }).bind('click', function(){
        jQuery('div.adjust').hide();
        jQuery(this).hide();
    }).hide();

    jQuery(document).on('submit', 'form.payment-form', function(e){
        e.preventDefault();
        var form = jQuery(this);
        if(editMultipleSubscriptions == true) {
            updateAllSubscriptions(form);
        }
        else {
            updateMySubscription(form);
        }
    }).on('change', '.shipping-address-select', function(){
        if(jQuery(this).val() == ""){
            jQuery(this).closest('.co-shipping-form').find('.shipping-new-address-form').show();
        } else {
            jQuery(this).closest('.co-shipping-form').find('.shipping-new-address-form').hide();
        }
    }).on('submit', 'form.co-shipping-form', function(e){
        e.preventDefault();
        var form = jQuery(this);
        if(editMultipleSubscriptions == true) {
            updateAllSubscriptions(form);
        }
        else {
            updateMySubscription(form);
        }
    }).on('change', '.billing-address-select', function(){
        if(jQuery(this).val() == ""){
            jQuery(this).closest('.co-billing-form').find('.billing-new-address-form').show();
        } else {
            jQuery(this).closest('.co-billing-form').find('.billing-new-address-form').hide();
        }
    }).on('submit', 'form.co-billing-form', function(e){
        e.preventDefault();
        var form = jQuery(this);
        if(editMultipleSubscriptions == true) {
            updateAllSubscriptions(form);
        }
        else {
            updateMySubscription(form);
        }
    }).on("click", "a.link.more.details", function(e){
        e.preventDefault();
        var link = jQuery(this);
        jQuery('#'+link.attr('href')).toggle();
    }).on("change", "select.delivery_qty", function(e){
        e.preventDefault();
        var form = jQuery(this).closest("form");
        updateMySubscription(form);
    }).on("change", "select.delivery_interval", function(e){
        e.preventDefault();
        var form = jQuery(this).closest("form");
        updateMySubscription(form);
    }).on("click", "button.adjust.skip", function(e){
        e.preventDefault();
        var box = jQuery(this).parents(".subscription-block").find("div.adjust.skip-delivery");
        box.css({
            "top": jQuery(window).scrollTop() + 50,
            "left": (jQuery(window).width() / 2) - (box.width() / 2)
            }).show();
        jQuery('#modal_overlay').show();
    }).on("click", "a.adjust.cancel", function(e){
        e.preventDefault();
        var box = jQuery(this).parents(".subscription-block").find("div.adjust.cancel");
        box.css({
            "top": jQuery(window).scrollTop() + 50,
            "left": (jQuery(window).width() / 2) - (box.width() / 2)
        }).show();
        jQuery('#modal_overlay').show();
    }).on("click", "a.adjust.restart", function(e){
        e.preventDefault();
        var box = jQuery(this).parents(".subscription-block").find("div.adjust.restart");
        box.css({
            "top": jQuery(window).scrollTop() + 50,
            "left": (jQuery(window).width() / 2) - (box.width() / 2)
        }).show();
        jQuery('#modal_overlay').show();
    }).on("click", "button.no", function(e){
        jQuery("div.adjust,#modal_overlay").hide();
    }).on("click", "button.skip_yes", function(e){
        e.preventDefault();
        var form = jQuery(this);
        jQuery("div.adjust,#modal_overlay").hide();
        jQuery("#please-wait").show();
        jQuery(".subscription-block .messages").html("");
        jQuery.ajax({
            url: form.attr('href'),
            method: "get",
            success: function(transport){
                jQuery("#please-wait").hide();
                if(transport.match("error")){
                    form.parents(".subscription-block").find(".messages").html(transport);
                } else {
                    form.parents(".subscription-block").html(transport).highlight();
                }
            }
        });
    }).on("click", "button.cancel_yes", function(e){
        e.preventDefault();
        var form = jQuery(this);
        jQuery("div.adjust,#modal_overlay").hide();
        jQuery("#please-wait").show();
        jQuery(".subscription-block .messages").html("");
        jQuery.ajax({
            url: form.attr('href'),
            method: "get",
            success: function(transport){
                jQuery("#please-wait").hide();
                if(transport.match("error")){
                    form.parents(".subscription-block").find(".messages").html(transport);
                } else {
                    form.parents(".subscription-block").fadeOut(1000);
                    jQuery(".inactive-subscriptions").prepend('<div class="subscription-block">' + transport + '</div>');
                }
            }
        });
    }).on("click", "button.restart_yes", function(e){
        e.preventDefault();
        var form = jQuery(this);
        jQuery("div.adjust,#modal_overlay").hide();
        jQuery("#please-wait").show();
        jQuery(".subscription-block .messages").html("");
        jQuery.ajax({
            url: form.attr('href'),
            method: "get",
            success: function(transport){
                jQuery("#please-wait").hide();
                if(transport.match("error")){
                    form.parents(".subscription-block").find(".messages").html(transport);
                } else {
                    form.parents(".subscription-block").html(transport).highlight();
                }
            }
        });
    }).on('click', ".subscription-more-details a.adjust", function(e){
        e.preventDefault();
        var box = jQuery(this).parents(".block").find("div.adjust");
        box.css({
            "top": jQuery(window).scrollTop() + 50,
            "left": (jQuery(window).width() / 2) - (box.width() / 2)
        }).show();
        jQuery('#modal_overlay').show();
    });

});
