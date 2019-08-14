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
 * Shopping cart api
 *
 * Override this class and handle passing existing payment tokens to various payment methods.
 *
 */

class SFC_Autoship_Model_Checkout_Cart_Api extends Mage_Checkout_Model_Cart_Api
{

    /**
     * Create an order from the shopping cart (quote)
     *
     * @param  $quoteId
     * @param  $store
     * @param  $agreements array
     * @return string
     */
    public function createOrder($quoteId, $store = null, $agreements = null)
    {
        // Quote helper to handle this event
        /** @var SFC_Autoship_Helper_Quote $quoteHelper */
        $quoteHelper = Mage::helper('autoship/quote');

        // Get quote
        $quote = $this->_getQuote($quoteId, $store);
        if (empty($store)) {
            $store = $quote->getStoreId();
        }

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $store) != '1') {
            return parent::createOrder($quoteId, $store, $agreements);
        }

        // Fire event indicating a subscription re-order
        if ($quoteHelper->hasSubscriptionReorderProduct($quote)) {
            Mage::dispatchEvent('sfc_autoship_before_subscription_reorder_place',
                array('quote_id' => $quoteId, 'quote' => $quote));
        }

        try {
            // Call parent
            return parent::createOrder($quoteId, $store, $agreements);
        }
        catch (\Mage_Api_Exception $apiException) {
            // In case of exception, look for transaction error detail in registry
            $errorDetailString = Mage::registry('subscribepro_transaction_error_detail');
            // Build new customer message with error detail
            $customMessage = $apiException->getCustomMessage();
            if (strlen($errorDetailString)) {
                $customMessage = "Credit card transaction failed!\n" . $errorDetailString;
            }
            // Rethrow exception with more detailed error message
            $this->_fault($apiException->getMessage(), $customMessage);
        }
    }


}
