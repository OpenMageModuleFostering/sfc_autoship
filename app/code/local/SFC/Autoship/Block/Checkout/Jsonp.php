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

class SFC_Autoship_Block_Checkout_Jsonp extends Mage_Checkout_Block_Onepage_Abstract
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('autoship/checkout/jsonp.phtml');
    }

    public function getEnvironmentKey()
    {
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
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

    public function getBillingAddress()
    {
        return $this->getQuote()->getBillingAddress();
    }

    public function getCustomer()
    {
        return $this->getQuote()->getCustomer();
    }

}
