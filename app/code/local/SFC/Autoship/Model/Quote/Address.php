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

class SFC_Autoship_Model_Quote_Address extends Mage_Sales_Model_Quote_Address
{
    /**
     * Mage override : filters shipping rate collection.
     */
    public function getShippingRatesCollection()
    {
        // Check if extension enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $this->getQuote()->getStore()) != '1') {
            return parent::getShippingRatesCollection();
        }
        // Call parent method to collect rates
        parent::getShippingRatesCollection();
        // Now filter out the subscription shipping method if appropriate
        if (!$this->allowSubscriptionShippingMethod()) {
            $subscriptionShippingMethod = Mage::getStoreConfig('autoship_subscription/subscription/shipping_method', $this->getQuote()->getStore());
            // Iterate rates and look for one matching our subscription method
            foreach ($this->_rates as $key => $rate) {
                $rateData = $rate->getData();
                if (isset($rateData['code']) && $rateData['code'] == $subscriptionShippingMethod) {
                    // If we find a rate matching our subscription method, remove it, making it unavailable
                    $this->_rates->removeItemByKey($key);
                }
            }
        }

        return $this->_rates;
    }

    /**
     * Method to determine if shipping method configured for subscriptions should be allowed in current context
     * Detect if code is running inside frontend of site
     * If Autoship extension is disabled, method should be allowed
     * If New Sub Page is disabled, method should be allowed
     * If shipping_method_onetime_enabled is set to Enabled, method should be allowed
     * If code is called from admin panel, or from API, method should be allowed
     * If code is run from New Subscription page, method should be allowed
     * Otherwise, if this is a normal customer check from the frontend and the Disable setting is set, method should be disallowed
     *
     * @return bool Is this the admin panel?
     */
    protected function allowSubscriptionShippingMethod()
    {
        // Check if extension enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $this->getQuote()->getStore()) != '1') {
            return true;
        }
        // Check if New Sub page disabled
        if (Mage::getStoreConfig('autoship_subscription/subscription/use_new_subscription_page', $this->getQuote()->getStore()) != '1') {
            return true;
        }
        // Check shipping_method_onetime_enabled setting
        if (Mage::getStoreConfig('autoship_subscription/subscription/shipping_method_onetime_enabled', $this->getQuote()->getStore()) == '1') {
            return true;
        }
        // Check if admin store is set as current store
        if (Mage::app()->getStore()->isAdmin()) {
            return true;
        }
        // Check if admin theme area is in effect
        if (Mage::getDesign()->getArea() == 'adminhtml') {
            return true;
        }
        // Check if API call session is active
        if (Mage::getModel('api/session')->isLoggedIn()) {
            return true;
        }
        // Now we know we're on the frontend, but if we're on the new subscriptions page, we need to allow the shipping method
        // Allow use of shipping method from new subscription controller
        if (Mage::app()->getRequest()->getControllerName() == 'newsubscription') {
            return true;
        }

        return false;
    }

}
