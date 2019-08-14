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

require 'app/Mage.php';

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

eLog("Syncing magento products to the platform.");

// Get and iterate collection of all products
$mageProducts = Mage::getModel('catalog/product')->getCollection();
/** @var Mage_Catalog_Model_Product $product */
foreach ($mageProducts as $product) {
    try {
        // Create / update product on platform
        $platformHelper->handleOnSaveProduct($product);
        eLog("Processed Product ID {$product->getId()}, sku {$product->getSku()}");
    }
    catch (Exception $e) {
        eLog("Failed to process product ({$product->getId()}) with error: " . $e->getMessage());
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
