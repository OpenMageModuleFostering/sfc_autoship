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
 * Special code to make us compatible with AW_Points module
 */
if (file_exists(realpath(dirname(__FILE__)) . '/../../../AW/Points/Block/Checkout/Onepage/Payment/Methods.php') &&
    class_exists('AW_Points_Block_Checkout_Onepage_Payment_Methods'))
{
    class SFC_Autoship_Block_Checkout_Onepage_Payment_Methods_Base extends AW_Points_Block_Checkout_Onepage_Payment_Methods
    {
    }
}
else
{
    class SFC_Autoship_Block_Checkout_Onepage_Payment_Methods_Base extends Mage_Checkout_Block_Onepage_Payment_Methods
    {
    }
}

class SFC_Autoship_Block_Checkout_Onepage_Payment_Methods extends SFC_Autoship_Block_Checkout_Onepage_Payment_Methods_Base
{

    protected function _canUseMethod($method)
    {
        Mage::log('SFC_Autoship_Block_Checkout_Onepage_Payment_Methods::_canUseMethod', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        // Get cart, quote and quote item
        /** @var Mage_Checkout_Model_Cart $cart */
        $cart = Mage::getSingleton('checkout/cart');
        // Get quote
        $quote = $cart->getQuote();
        Mage::log('Quote store: ' . $quote->getStore()->getCode() . ' id: ' . $quote->getStore()->getId(), Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $quote->getStore()) != '1') {
            return parent::_canUseMethod($method);
        }

        /** @var Mage_Payment_Model_Method_Abstract $method*/
        // Check if New Subscription page is enabled, or if we should create subscriptions in checkout
        if(Mage::getStoreConfig('autoship_subscription/subscription/use_new_subscription_page', $quote->getStore()) == '1') {
            return parent::_canUseMethod($method);
        }
        else {
            // We should create subs during checkout, and we are checking a payment method other than Authorize.Net CIM
            // If other payment method being used, only allow this when there are no subscriptions to create
            // Get quote helper
            /** @var SFC_Autoship_Helper_Quote $quoteHelper */
            $quoteHelper = Mage::helper('autoship/quote');
            // Check if quote has any subscriptions in it
            if(!$quoteHelper->hasProductsToCreateNewSubscription()) {
                // Quote has no subscriptions,
                // Go through normal qualification process for payment methods
                return parent::_canUseMethod($method);
            }
            else {
                // Quote has subscriptions, only allow payment methods compatible with subscriptions
                // Get helper
                /** @var SFC_Autoship_Helper_Platform $platformHelper */
                $platformHelper = Mage::helper('autoship/platform');
                /** @var SFC_Autoship_Helper_Api $apiHelper */
                $apiHelper = Mage::helper('autoship/api');
                $apiHelper->setConfigStore($quote->getStore());
                // Lookup payment method code based on SP config
                $configuredMethodCode = $platformHelper->getConfiguredPaymentMethodCode();
                // Check for configured payment method code
                if(0 === strpos($method->getCode(), $configuredMethodCode)) {
                    // This is the pay method which is allowed by Subscribe Pro config
                    // Run normal check
                    return parent::_canUseMethod($method);
                }
                else {
                    // This is some other payment method, not allowed when checking out and creating subscriptions
                    return false;
                }

            }
        }
    }


}
