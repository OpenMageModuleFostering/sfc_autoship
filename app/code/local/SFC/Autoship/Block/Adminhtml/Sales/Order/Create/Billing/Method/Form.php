<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */ 
class SFC_Autoship_Block_Adminhtml_Sales_Order_Create_Billing_Method_Form extends Mage_Adminhtml_Block_Sales_Order_Create_Billing_Method_Form
{
    /**
     * Check payment method model
     *
     * @param Mage_Payment_Model_Method_Abstract|null $method
     * @return bool
     */
    protected function _canUseMethod($method)
    {
        $quote = $this->getQuote();

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $quote->getStore()) != '1') {
            return parent::_canUseMethod($method);
        }

        // We should create subs during checkout, and we are checking a payment method other than Authorize.Net CIM
        // If other payment method being used, only allow this when there are no subscriptions to create
        // Get quote helper
        /** @var SFC_Autoship_Helper_Quote $quoteHelper */
        $quoteHelper = Mage::helper('autoship/quote');
        // Check if quote has any subscriptions in it
        if(!$quoteHelper->hasProductsToCreateNewSubscription($quote)) {
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