<?php
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

/**
 * This class returns customer friendly error messages for all payment related errors
 *
 * How to modify error messages:
 *  - It's suggested to use Magento's translations to just change the error message text.
 *  - If you prefer to change the mapping logic, rewrite this helper class from your module.
 *
 */
class SFC_Autoship_Helper_PaymentError extends Mage_Core_Helper_Abstract
{

    // Error types
    const ERROR_TYPE_TECHNOLOGY             = 'technology';
    const ERROR_TYPE_CARD_NUMBER            = 'card_number';
    const ERROR_TYPE_CARD_TYPE              = 'card_type';
    const ERROR_TYPE_AVS                    = 'avs';
    const ERROR_TYPE_CVV                    = 'cvv';
    const ERROR_TYPE_EXP_DATE               = 'exp_date';
    const ERROR_TYPE_SOFT_DECLINE           = 'soft_decline';
    const ERROR_TYPE_HARD_DECLINE           = 'hard_decline';
    const ERROR_TYPE_CREDIT_FAILED          = 'credit_failed';
    const ERROR_TYPE_VOID_FAILED            = 'void_failed';
    const ERROR_TYPE_CAPTURE_FAILED         = 'capture_failed';
    const ERROR_TYPE_DUPLICATE_TRANSACTION  = 'duplicate_transaction';
    const ERROR_TYPE_FRAUD_REVIEW           = 'fraud_review';

    /**
     * @param string $subscribeProErrorType
     * @return null|string
     */
    public function getGatewayErrorMessage($subscribeProErrorType)
    {
        $errorMessageMap = array(
            self::ERROR_TYPE_TECHNOLOGY => 'Your order did not go through due to a technical error.  Please try again or contact customer support.',
            self::ERROR_TYPE_CARD_NUMBER => 'There is an error with your credit card number.  Please check the number and try again.',
            self::ERROR_TYPE_CARD_TYPE => 'There is an error with your credit card type or your card type is not supported.',
            self::ERROR_TYPE_AVS => 'Your order did not go through due to an issue with your credit card billing address.  Please check the address or contact your bank and try again.',
            self::ERROR_TYPE_CVV => 'Your order did not go through due to an issue matching your 3 or 4 digit credit card verification code.',
            self::ERROR_TYPE_EXP_DATE => 'Your order did not go through due to an issue with your credit card expiration date.',
            self::ERROR_TYPE_SOFT_DECLINE => 'Your order did not go through due to an issue with your credit card.',
            self::ERROR_TYPE_HARD_DECLINE => 'Your order did not go through due to an issue with your credit card.',
            self::ERROR_TYPE_CREDIT_FAILED => 'There was an error processing a credit for this transaction.',
            self::ERROR_TYPE_VOID_FAILED => 'There was an error voiding this transaction.',
            self::ERROR_TYPE_CAPTURE_FAILED => 'There was an error capturing funds for this transaction.',
            self::ERROR_TYPE_DUPLICATE_TRANSACTION => 'Your order did not go through because it may have been a duplicate of another order.  If you have only submitted your order once, please wait 10 minutes and try again.',
            self::ERROR_TYPE_FRAUD_REVIEW => 'Your order did not go through due to an issue with your credit card.',
        );

        if (isset ($errorMessageMap[$subscribeProErrorType])) {
            // Run error message through translate
            return $this->__($errorMessageMap[$subscribeProErrorType]);
        }
        else {
            return null;
        }
    }

    /**
     * @param string $attribute
     * @param string $key
     * @return null|string
     */
    public function getCreditCardErrorMessage($attribute, $key)
    {
        $errorMessageMap = array(
            'errors.expired' => array(
                'year' => 'Your order did not go through due to an issue with your credit card expiration date.',
                'month' => 'Your order did not go through due to an issue with your credit card expiration date.',
            ),
            'errors.blank' => array(
                'year' => 'Your order did not go through due to an issue with your credit card expiration date.',
                'month' => 'Your order did not go through due to an issue with your credit card expiration date.',
                'number' => 'There is an error with your credit card number.  Please check the number and try again.',
            ),
            'errors.invalid' => array(
                'year' => 'Your order did not go through due to an issue with your credit card expiration date.',
                'month' => 'Your order did not go through due to an issue with your credit card expiration date.',
                'number' => 'There is an error with your credit card number.  Please check the number and try again.',
            ),
        );

        if (isset ($errorMessageMap[$key]) && isset ($errorMessageMap[$key][$attribute])) {
            // Run error message through translate
            return $this->__($errorMessageMap[$key][$attribute]);
        }
        else {
            return null;
        }
    }

}
