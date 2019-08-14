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
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @author    Garth Brantley <garth@subscribepro.com>
 * @copyright 2009-2014 SUBSCRIBE PRO INC. All Rights Reserved.
 * @license   http://www.subscribepro.com/terms-of-service/ Subscribe Pro Terms of Service
 * @link      http://www.subscribepro.com/
 *
 */

define('MAGENTO_ROOT', dirname(dirname(__FILE__)));

require (MAGENTO_ROOT . '/app/Mage.php');

if (!Mage::isInstalled()) {
    die("Application is not installed yet, please complete install wizard first.\n");
}

// Only for urls
// Don't remove this
$_SERVER['SCRIPT_NAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_NAME']);
$_SERVER['SCRIPT_FILENAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_FILENAME']);

Mage::app('admin')->setUseSessionInUrl(false);

umask(0);

/** @var SFC_Autoship_Helper_Platform $platformHelper */
$platformHelper = Mage::helper('autoship/platform');
/** @var SFC_Autoship_Helper_Api $apiHelper */
$apiHelper = Mage::helper('autoship/api');

eLog("Syncing all Magento customers to Subscribe Pro.");

// Get and iterate collection of all customers
$mageCustomers = Mage::getModel('customer/customer')->getCollection();
/** @var Mage_Customer_Model_Customer $customerRow */
foreach ($mageCustomers as $customerRow) {
    try {
        //We must load the storeId through this method
        //Otherwise customers created in admin will always read the default setting for subscription enabled
        $storeId = Mage::app()->getWebsite($customerRow->getWebsiteId())->getDefaultGroup()->getDefaultStoreId();
        if (!Mage::getStoreConfig('autoship_general/general/enabled', $storeId) == '1') {
            continue;
        }
        // Load the full customer model
        $customer = Mage::getModel('customer/customer')->load($customerRow->getId());
        // Set config store / website
        $apiHelper->setConfigStore($storeId);
        // Sync customer to SP
        $spCustomer = $platformHelper->createOrUpdateCustomer($customer);
        eLog("Processed Customer ID {$customer->getId()}");
    }
    catch (Exception $e) {
        eLog("Failed to process Customer ({$customer->getId()}) with error: " . $e->getMessage());
    }
}

function eLog($message)
{
    if (is_string($message)) {
        echo $message . "\n";
    }
    else {
        print_r($message);
    }
}