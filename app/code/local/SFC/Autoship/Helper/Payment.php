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
 * Special code to make us compatible with Amasty_Payrestriction module
 */
if (file_exists(realpath(dirname(__FILE__)) . '/../../../Amasty/Payrestriction/Helper/Payment/Data.php') &&
    class_exists('Amasty_Payrestriction_Helper_Payment_Data'))
{
    class SFC_Autoship_Helper_Payment_Base extends Amasty_Payrestriction_Helper_Payment_Data
    {
    }
}
else
{
    class SFC_Autoship_Helper_Payment_Base extends Mage_Payment_Helper_Data
    {
    }
}

class SFC_Autoship_Helper_Payment extends SFC_Autoship_Helper_Payment_Base
{

    public function getPaymentTokenFromMethodCode($code)
    {
        // Check if code includes key
        $key = SFC_Autoship_Model_Payment_Method::METHOD_CODE . SFC_Autoship_Model_Payment_Method::METHOD_CODE_KEY_TOKEN;
        if ($key == substr($code, 0, strlen($key))) {
            $encodedMethodParts = explode(SFC_Autoship_Model_Payment_Method::METHOD_CODE_KEY_TOKEN, $code);
            $paymentToken = $encodedMethodParts[1];

            return $paymentToken;
        }
        else {
            return null;
        }
    }


    /**
     * Retrieve method model object
     *
     * @param   string $code
     * @return  Mage_Payment_Model_Method_Abstract|false
     */
    public function getMethodInstance($code)
    {
        // Log
        Mage::log('SFC_Autoship_Helper_Payment::getMethodInstance', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        // Check if code includes key
        $key = SFC_Autoship_Model_Payment_Method::METHOD_CODE . SFC_Autoship_Model_Payment_Method::METHOD_CODE_KEY_TOKEN;
        if ($key == substr($code, 0, strlen($key))) {
            $classKey = self::XML_PATH_PAYMENT_METHODS . '/' . SFC_Autoship_Model_Payment_Method::METHOD_CODE . '/model';
            $class = Mage::getStoreConfig($classKey);
            /** @var SFC_Autoship_Model_Payment_Method $model */
            $model = Mage::getModel($class);

            return $model;
        }
        else {
            return parent::getMethodInstance($code);
        }
    }

    /**
     *
     * @param mixed $store
     * @param mixed $quote
     * @return mixed
     */
    public function getStoreMethods($store = null, $quote = null)
    {
        // Log
        Mage::log('SFC_Autoship_Helper_Payment::getStoreMethods', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        if(is_numeric($store)) {
            Mage::log('Store Id: ' . $store, Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        }
        if($store != null && is_object($store)) {
            Mage::log('Store Id: ' . $store->getId(), Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        }
        if(is_numeric($quote)) {
            Mage::log('Quote Id: ' . $quote, Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        }
        if($quote != null && is_object($quote)) {
            Mage::log('Quote Id: ' . $quote->getId(), Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        }

        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Call parent implementation
        $parentMethods = parent::getStoreMethods($store, $quote);
        // Build new list
        $methods = array();
        // Find payment method in list
        /** @var Mage_Payment_Model_Method_Abstract $method */
        foreach ($parentMethods as $method) {
            // Check, is this method current method
            if ($method->getCode() == SFC_Autoship_Model_Payment_Method::METHOD_CODE) {
                // Get customer
                $customer = $this->getCustomerForProfiles($quote);
                if (strlen($customer->getId())) {
                    // Set website / store for config on API helper
                    $store = Mage::app()->getWebsite($customer->getData('website_id'))->getDefaultStore();
                    $apiHelper->setConfigStore($store);
                    $paymentProfiles = $vaultHelper->getPaymentProfilesForCustomer($customer->getData('email'));
                    // Iterate payment profiles
                    /** @var SFC_Autoship_Model_Payment_Profile $paymentProfile */
                    foreach ($paymentProfiles as $paymentProfile) {
                        // Build new method out of profile
                        /** @var  $newMethodInstance */
                        $newMethodInstance = $this->getMethodInstance(SFC_Autoship_Model_Payment_Method::METHOD_CODE);
                        $newMethodInstance->setSavedPaymentProfile($paymentProfile);
                        $methods[] = $newMethodInstance;
                    }
                }
            }
            // Add method from parent implementation
            $methods[] = $method;
        }

        return $methods;
    }

    /**
     * @return Mage_Core_Model_Store
     */
    protected function detectStore()
    {
        if (!Mage::app()->getStore()->isAdmin()) {
            return Mage::app()->getStore();
        }
        else {
            // If we are in admin store, try to find correct store from current quote
            /** @var Mage_Adminhtml_Model_Session_Quote $adminhtmlQuoteSession */
            $adminhtmlQuoteSession = Mage::getSingleton('adminhtml/session_quote');
            $quote = $adminhtmlQuoteSession->getQuote();
            $store = $quote->getStore();

            return $store;
        }
    }

    protected function getCustomerForProfiles($quote)
    {
        // If quote is null, use customer from session
        if ($quote != null && is_object($quote) && $quote instanceof Mage_Sales_Model_Quote) {
            $customer = $quote->getCustomer();

            return $customer;
        }
        else {
            /** @var Mage_Customer_Model_Session $customerSession */
            $customerSession = Mage::getSingleton('customer/session');
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = $customerSession->getCustomer();

            return $customer;
        }
    }

}
