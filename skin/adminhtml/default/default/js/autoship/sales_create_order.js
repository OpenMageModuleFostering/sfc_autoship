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
 * @copyright 2009-2014 SUBSCRIBE PRO INC. All Rights Reserved.
 * @license   http://www.subscribepro.com/terms-of-service/ Subscribe Pro Terms of Service
 * @link      http://www.subscribepro.com/
 *
 */

// Tokenize card method -
//  Send card out to vault via JSONP for tokenization and store resulting token in form
function tokenizeCard(submitOrderOnSuccess) {
    // Get Prototype representation of field, so we can call prototype Validation methods on it inside
    var spCcNumberField = $('subscribe_pro_cc_number');
    var spCcMonthField = $('subscribe_pro_expiration');
    var spCcYearField = $('subscribe_pro_expiration_yr');
    // Wrap rest of code with $ jquery operator
    (function($){
        // Clear previous validation messages
        $('#advice-validate-ajax-subscribe_pro_cc_number').remove();
        $('#advice-validate-ajax-subscribe_pro_expiration').remove();
        $('#advice-validate-ajax-subscribe_pro_expiration_yr').remove();
        // Build data structure for JSONP call
        var card = {
            "kind": "credit_card",
            "number": $('#subscribe_pro_cc_number').val(),
            "verification_value": $('#subscribe_pro_cc_cid').val(),
            "month": $('#subscribe_pro_expiration').val(),
            "year": $('#subscribe_pro_expiration_yr').val(),
            "first_name": $('#order-billing_address_firstname').val(),
            "last_name": $('#order-billing_address_lastname').val()
        }
        // Serialize and URI encode parameters.
        var paramStr = $.param(card);
        // Build JSONP GET URL
        var url = jsonpUrl + "?environment_key=" + jsonpEnvironmentKey + "&" + paramStr;
        // Make JSONP GET call to vault
        $.ajax({
            type: "GET",
            url: url,
            dataType: "jsonp",
            timeout: 1200
        }).success(function (data) {
            console.log(data);
            if (data.status === 201) {
                // Save token in payment data
                $('#subscribe_pro_payment_token').val(data.transaction.payment_method.token);
                // Replace card number and CVV with obscured data
                $('#subscribe_pro_cc_number').val(
                    data.transaction.payment_method.first_six_digits +
                        'XXXXXX' +
                        data.transaction.payment_method.last_four_digits
                );
                $('#subscribe_pro_cc_cid').val(data.transaction.payment_method.verification_value);
                // Now submit order to Magento like normal
                if (submitOrderOnSuccess) {
                    submitOrder();
                }
            } else {
                console.log('validation error!');
                if (data.errors && data.errors.length > 0) {
                    if (data.errors[0].attribute == 'month') {
                        Validation.ajaxError(spCcMonthField, data.errors[0].message);
                    }
                    else if (data.errors[0].attribute == 'year') {
                        Validation.ajaxError(spCcYearField, data.errors[0].message);
                    }
                    else {
                        Validation.ajaxError(spCcNumberField, data.errors[0].message);
                    }
                }
                else {
                    Validation.ajaxError(spCcNumberField, 'Credit card validation error occurred!');
                }
            }
        }).error(function (request, status, error) {
            console.log('transmission error!');
            Validation.ajaxError(spCcNumberField, 'Error occurred sending card to payment vault!');
        });
    })(jQuery);
}

function isCardAlreadyTokenized() {
    return (function($){
        if ($('#subscribe_pro_cc_number').val().indexOf('XXXXXX') >= 0 && $('#subscribe_pro_payment_token').val().length > 0) {
            return true;
        }
        else {
            return false;
        }
    })(jQuery);
}

function submitOrder() {
    (function($){
        // Submit
        // Call original method
        return AdminOrder.prototype.submit.call(order);
    })(jQuery);
}

// Store card number in browser instead of POST to server
var subscribeProCardNumber;

// Handle document ready event
jQuery(document).ready(
    function ($) {
        // Replace method from AdminOrder prototype
        order.loadArea  = function(area, indicator, params) {
            if ($.inArray('billing_method', area)) {
                console.log('loadArea: billing_method');
                // Save card number in browser, so we dont have to POST it to server
                subscribeProCardNumber = $('#subscribe_pro_cc_number').val();
            }
            // Call original method
            return AdminOrder.prototype.loadArea.call(this, area, indicator, params);
        }
        order.loadAreaResponseHandler = function(response) {
            // Call original method
            result = AdminOrder.prototype.loadAreaResponseHandler.call(this, response);
            // Restore card number
            $('#subscribe_pro_cc_number').val(subscribeProCardNumber);
            // Return original method result
            return result;
        }
        // Replace method from AdminOrder prototype
        order.submit  = function() {
            //console.log('Subscribe Pro submit called.');
            // Check if free payment method is selected
            // This is necessary because of Magneto bug where 'free' doesn't get set in this.paymentMethod when free payment method is automatically selected
            var freeMethodChecked = $('#p_method_free').is(':checked');
            // tokenize card if new card entered
            if (!freeMethodChecked && this.paymentMethod == 'subscribe_pro') {
                console.log('subscribe_pro method selected.');
                if (isCardAlreadyTokenized()) {
                    // Card already tokenized, just submit to Magento
                    submitOrder();
                }
                else {
                    // Tokenize card, calling order submit when jsonp call returns
                    tokenizeCard(true);
                }
            }
            else {
                submitOrder();
            }
        }
    }
);
