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
function tokenizeCard(paymentSaveThis) {
    (function($){
        // Turn on waiting indicator
        checkout.setLoadWaiting('payment');
        // Build data structure for JSONP call
        var card = {
            "kind": "credit_card",
            "number": $('#subscribe_pro_cc_number').val(),
            "verification_value": $('#subscribe_pro_cc_cid').val(),
            "month": $('#subscribe_pro_expiration').val(),
            "year": $('#subscribe_pro_expiration_yr').val(),
            "first_name": $('#subscribe_pro_firstname').val(),
            "last_name": $('#subscribe_pro_lastname').val()
        }
        // Serialize and URI encode parameters.
        var paramStr = $.param(card);
        // Build JSONP GET URL
        var url = jsonpUrl + "?environment_key=" + jsonpEnvironmentKey + "&" + paramStr;
        // Make JSONP GET call to vault
        $.ajax({
            type: "GET",
            url: url,
            dataType: "jsonp"
        }).success(function (data) {
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
                // Call Magento payment save ajax call
                paymentSaveAjaxCall(paymentSaveThis);
            } else {
                console.log('validation error!');
            }
        }).error(function (request, status, error) {
            checkout.setLoadWaiting(false);
            console.log('transmission error!');
        });
    })(jQuery);
}

// Call Magento payment save ajax call
function paymentSaveAjaxCall(paymentSaveThis) {
    (function($){
        // Make original payment save function ajax call to Magento
        var request = new Ajax.Request(
            paymentSaveThis.saveUrl,
            {
                method:'post',
                onComplete: paymentSaveThis.onComplete,
                onSuccess: paymentSaveThis.onSave,
                onFailure: checkout.ajaxFailure.bind(checkout),
                parameters: Form.serialize(paymentSaveThis.form)
            }
        );
    })(jQuery);
}

// Handle document ready event
jQuery(document).ready(
    function ($) {
        // Replace payment save function
        payment.save = function () {
            if (this.currentMethod != 'subscribe_pro') {
                // Call original payment save function
                return Payment.prototype.save.call(this);
            }
            // Save this ref so we can use to call original payment.save method
            var paymentSaveThis = this;
            // Check if we are already running and return immediately if so
            if (checkout.loadWaiting!=false) return;
            // Check if we already have a tokenized card
            var cardAlreadyTokenized =
                $('#subscribe_pro_payment_token').val().length > 0 &&
                $('#subscribe_pro_cc_number').val().indexOf('XXXXXX') >= 0
                ;
            // Check if card is already tokenized
            if (cardAlreadyTokenized) {
                // Turn on waiting indicator
                checkout.setLoadWaiting('payment');
                // Call Magento payment save ajax call
                paymentSaveAjaxCall(paymentSaveThis);
            }
            else {
                // Card not yet tokenized, run it through vault
                // Run validation
                var validator = new Validation(paymentSaveThis.form);
                if (paymentSaveThis.validate() && validator.validate()) {
                    // Validation passed
                    // Call vault to tokenize card, then call Magento Ajax payment save action
                    tokenizeCard(paymentSaveThis);
                }
            }
        }
        ;
    }
)
;
