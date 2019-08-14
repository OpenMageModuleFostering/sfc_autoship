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
 * Shopping cart api for product
 *
 * Override this class and add the 'no_discount' and 'custom_price' options for product request,
 * these options behave the same way those fields do in the admin ordering grid.
 *
 */

class SFC_Autoship_Model_Checkout_Cart_Product_Api extends Mage_Checkout_Model_Cart_Product_Api
{

    /**
     * @param  $quoteId
     * @param  $productsData
     * @param  $store
     * @return bool
     */
    public function add($quoteId, $productsData, $store = null)
    {
        $quote = $this->_getQuote($quoteId, $store);
        if (empty($store)) {
            $store = $quote->getStoreId();
        }

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $store) != '1') {
            return parent::add($quoteId, $productsData, $store);
        }

        $productsData = $this->_prepareProductsData($productsData);
        if (empty($productsData)) {
            $this->_fault('invalid_product_data');
        }

        $errors = array();
        foreach ($productsData as $productItem) {
            if (isset($productItem['product_id'])) {
                $productByItem = $this->_getProduct($productItem['product_id'], $store, 'id');
            }
            else {
                if (isset($productItem['sku'])) {
                    /* SFC Changes - Workaround for earlier Magento versions that can't get product by the sku - Starts Here */
                    // Magento bug is in Mage_Catalog_Helper_Product::getProduct method
                    $sku = $productItem['sku'];
                    // Lookup product id by sku
                    $productId = Mage::getModel('catalog/product')->getIdBySku($sku);
                    if ($productId <= 0) {
                        $this->_fault('add_product_fault', Mage::helper("autoship")->__('Failed to find product with SKU: %s', $sku));
                    }
                    $productByItem = $this->_getProduct($productId, $store, 'id');
                    if (!$productByItem instanceof Mage_Catalog_Model_Product) {
                        $this->_fault('add_product_fault', Mage::helper("autoship")->__('Failed to find product with SKU: %s', $sku));
                    }
                    /* SFC Changes - Workaround for earlier Magento versions that can't get product by the sku - Ends Here */
                }
                else {
                    $errors[] = Mage::helper('checkout')->__('One item of products do not have identifier or sku');
                    continue;
                }
            }

            $productRequest = $this->_getProductRequest($productItem);
            try {
                $result = $quote->addProduct($productByItem, $productRequest);
                if (is_string($result)) {
                    Mage::throwException($result);
                }
                /* SFC Changes / Additions - Start Here */
                $quoteItem = $result;
                // In the case of bundle products, make sure we grab the parent quote item
                if($quoteItem->getParentItem() != null && $quoteItem->getParentItem()->getProductType() == 'bundle') {
                    $quoteItem = $quoteItem->getParentItem();
                }
                // Now set custom attributes on quote item
                if (isset($productRequest['custom_price'])) {
                    $quoteItem->setCustomPrice($productRequest['custom_price']);
                    $quoteItem->setOriginalCustomPrice($productRequest['custom_price']);
                }
                if (isset($productRequest['no_discount'])) {
                    $quoteItem->setNoDiscount($productRequest['no_discount']);
                }
                // Save subscription flag and id from API created orders
                if (isset($productRequest['item_fulfils_subscription'])) {
                    $quoteItem->setData('item_fulfils_subscription', $productRequest['item_fulfils_subscription']);
                }
                if (isset($productRequest['subscription_id'])) {
                    $quoteItem->setData('subscription_id', $productRequest['subscription_id']);
                }
                if (isset($productRequest['subscription_interval'])) {
                    $quoteItem->setData('subscription_interval', $productRequest['subscription_interval']);
                }
                /* SFC Changes / Additions - End Here */
            }
            catch (Mage_Core_Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            $this->_fault('add_product_fault', implode(PHP_EOL, $errors));
        }

        try {
            $quote->collectTotals()->save();
        }
        catch (Exception $e) {
            $this->_fault('add_product_quote_save_fault', $e->getMessage());
        }

        return true;
    }

    /**
     * @param  $quoteId
     * @param  $productsData
     * @param  $store
     * @return bool
     */
    public function update($quoteId, $productsData, $store = null)
    {
        $quote = $this->_getQuote($quoteId, $store);
        if (empty($store)) {
            $store = $quote->getStoreId();
        }

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $store) != '1') {
            return parent::update($quoteId, $productsData, $store);
        }

        $productsData = $this->_prepareProductsData($productsData);
        if (empty($productsData)) {
            $this->_fault('invalid_product_data');
        }

        $errors = array();
        foreach ($productsData as $productItem) {
            if (isset($productItem['product_id'])) {
                $productByItem = $this->_getProduct($productItem['product_id'], $store, 'id');
            }
            else {
                if (isset($productItem['sku'])) {
                    $productByItem = $this->_getProduct($productItem['sku'], $store, 'sku');
                }
                else {
                    $errors[] = Mage::helper('checkout')->__('One item of products do not have identifier or sku');
                    continue;
                }
            }

            /** @var $quoteItem Mage_Sales_Model_Quote_Item */
            $quoteItem = $this->_getQuoteItemByProduct($quote, $productByItem,
                $this->_getProductRequest($productItem));
            if (is_null($quoteItem->getId())) {
                $errors[] = Mage::helper('checkout')->__('One item of products is not belong any of quote item');
                continue;
            }

            if ($productItem['qty'] > 0) {
                $quoteItem->setQty($productItem['qty']);
            }

            /* SFC Changes / Additions - Start Here */
            $productRequest = $this->_getProductRequest($productItem);
            if (isset($productRequest['custom_price'])) {
                $quoteItem->setCustomPrice($productRequest['custom_price']);
                $quoteItem->setOriginalCustomPrice($productRequest['custom_price']);
            }
            if (isset($productRequest['no_discount'])) {
                $quoteItem->setNoDiscount($productRequest['no_discount']);
            }
            // TODO:    It would be ideal if we can get the additional_options from buy request into additional_options option field in
            //          the quote item at time quote item is created
            //          Currently this isn't working:
            /*
            if (isset($productRequest['additional_options'])) {
                $quoteItem->addOption(array(
                    'code' => 'additional_options',
                    'product_id' => $quoteItem->getProductId(),
                    'value' => serialize($productRequest['additional_options'])
                    )
                );
            }
            */
            /* SFC Changes / Additions - End Here */
        }

        if (!empty($errors)) {
            $this->_fault('update_product_fault', implode(PHP_EOL, $errors));
        }

        try {
            $quote->save();
        }
        catch (Exception $e) {
            $this->_fault('update_product_quote_save_fault', $e->getMessage());
        }

        return true;
    }

}
