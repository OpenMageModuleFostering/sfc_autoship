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

class SFC_Autoship_Block_Adminhtml_Sales_Order_Create_Jsonp extends Mage_Checkout_Block_Onepage_Abstract
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('autoship/sales/create_order/jsonp.phtml');
    }

    protected function getStore()
    {
        // If we are in admin store, try to find correct store from current quote
        /** @var Mage_Adminhtml_Model_Session_Quote $adminhtmlQuoteSession */
        $adminhtmlQuoteSession = Mage::getSingleton('adminhtml/session_quote');
        $quote = $adminhtmlQuoteSession->getQuote();
        $store = $quote->getStore();

        return $store;
    }

    public function showJsonp()
    {
        $spPayMethodActive = Mage::getStoreConfig('payment/subscribe_pro/active', $this->getStore()) == '1';

        return $spPayMethodActive;
    }

    public function getEnvironmentKey()
    {
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        // Set store on api helper
        $apiHelper->setConfigStore($this->getStore());
        // Lookup payment method code based on SP config
        $accountConfig = $platformHelper->getAccountConfig();
        // Get env key
        $environmentKey = $accountConfig['transparent_redirect_environment_key'];

        return $environmentKey;
    }

    public function getJSONPUrl()
    {
        return 'https://core.spreedly.com/v1/payment_methods.js';
    }

}
