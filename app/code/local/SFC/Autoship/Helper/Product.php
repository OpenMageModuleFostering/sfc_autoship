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

class SFC_Autoship_Helper_Product extends Mage_Core_Helper_Abstract
{

    private $platformConnectionVerified = false;

    protected function _construct()
    {
    }

    /**
     * Determine if product is available for subscription on a given store
     *
     * @param Mage_Catalog_Model_Product|integer $product
     * @param null|integer|Mage_Core_Model_Store $store
     * @param bool $verifyApiConnection
     * @return bool
     */
    public function isAvailableForSubscription($product, $store = null, $verifyApiConnection = true)
    {
        // Lookup current store if not passed in
        if ($store == null) {
            $store = Mage::app()->getStore();
        }
        // Lookup store from numeric ID
        if (is_numeric($store)) {
            $store = Mage::app()->getStore($store);
        }
        // Get product id
        if ($product instanceof Mage_Catalog_Model_Product) {
            $productId = $product->getId();
        }
        else {
            $productId = $product;
        }
        // Get product resource object
        /** @var Mage_Catalog_Model_Resource_Product $resource */
        $resource = Mage::getModel('catalog/product')->getResource();
        // Lookup whether product is enabled for the website
        $websiteIds = $resource->getWebsiteIds($productId);
        if (in_array($store->getWebsite()->getId(), $websiteIds)) {
            // Product is enabled for website
            // Lookup whether product enabled / disabled for subscription
            $isProductEnabled = $resource->getAttributeRawValue($productId, 'subscription_enabled', $store) == '1';
        }
        else {
            // Product not enabled for website, so by proxy we say not available for subscription
            $isProductEnabled = false;
        }

        if ($isProductEnabled) {
            if ($verifyApiConnection) {
                return $this->verifyPlatformConnection($product, $store);
            }
            else {
                return true;
            }
        }
        else {
            return false;
        }
    }

    /**
     * @param $product
     * @param $store
     * @return bool
     */
    protected function verifyPlatformConnection($product, $store)
    {
        if ($this->platformConnectionVerified) {
            return true;
        }
        else {
            /** @var SFC_Autoship_Helper_Platform $platformHelper */
            $platformHelper = Mage::helper('autoship/platform');
            /** @var SFC_Autoship_Helper_Api $apiHelper */
            $apiHelper = Mage::helper('autoship/api');
            $apiHelper->setConfigStore($store);
            try {
                // Fetch product, this will get it from cache, or fetch it
                // Basically this is going to correspond to whether the request will be successful otherwise
                // Its a cheap & dirty way to get a jump on API comm issue and pretend like product is not enabled for sub
                $response = $apiHelper->fetchProducts(array('sku' => $product->getSku()));
                // Set flag
                $this->platformConnectionVerified = true;

                return true;
            }
            catch(\Exception $e) {
                // Set flag
                $this->platformConnectionVerified = false;
                // Log
                Mage::log('Failed to verify connection to Subscribe Pro platform!', Zend_Log::ERR, SFC_Autoship_Helper_Data::API_LOG_FILE);

                return false;
            }
        }
    }

}
