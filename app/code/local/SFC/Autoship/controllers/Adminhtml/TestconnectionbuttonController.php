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

class SFC_Autoship_Adminhtml_TestconnectionbuttonController extends Mage_Adminhtml_Controller_Action
{

    public function testAction()
    {
        // Get platform helper
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        $store = $this->getStore();
        // Set store on api helper
        $apiHelper->setConfigStore($store);
        // Log
        Mage::log('Testing connection for website: ' . $store->getWebsite()->getCode() . ' and store: ' . $store->getCode(), Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        // Clear cache
        Mage::app()->cleanCache(array(SFC_Autoship_Helper_Api::CACHE_TYPE_CONFIG, SFC_Autoship_Helper_Api::CACHE_TYPE_PRODUCTS));
        // Update configuration
        $this->updateStoreConfiguration();
        // Call platform to get Account Configuration, just to test connection
        try {
            $platformHelper->getAccountConfig();
            Mage::log('Connection test successful.', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            $result = 1;
        }
        catch(\Exception $e) {
            Mage::logException($e);
            Mage::log('Connection test failed!', Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
            $result = 0;
        }
        // Return result
        Mage::app()->getResponse()->setBody($result);
    }

    /**
     * Save configuration values from ajax call.  Only save them temporarily so we can test that they work.
     * Don't save them permanently until user clicks Save Config button.
     */
    protected function updateStoreConfiguration()
    {
        $store = $this->getStore();
        $store->setConfig('autoship_general/platform_api/platform_host', $this->getRequest()->getParam('host'));
        $store->setConfig('autoship_general/platform_api/client_id', Mage::helper('core')->encrypt($this->getRequest()->getParam('client_id'), $store));
        $clientSecret = $this->getRequest()->getParam('client_secret');
        if (strlen($clientSecret) && $clientSecret != '******') {
            $store->setConfig('autoship_general/platform_api/client_secret', Mage::helper('core')->encrypt($this->getRequest()->getParam('client_secret'), $store));
        }
    }

    /**
     * @return Mage_Core_Model_Store
     */
    protected function getStore()
    {
        $params = $this->getRequest()->getParams();
        if (isset($params['website']) && strlen($params['website'])) {
            $store = Mage::app()->getWebsite($params['website'])->getDefaultStore();
        }
        else {
            $store = Mage::app()->getStore();
        }

        return $store;
    }

}
