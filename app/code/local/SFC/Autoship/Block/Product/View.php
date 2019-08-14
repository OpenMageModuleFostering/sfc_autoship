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
 * Product View block - Override the Magento catalog block
 *
 */
class SFC_Autoship_Block_Product_View extends Mage_Catalog_Block_Product_View
{
    private $_platformProduct = null;

    /**
     * Return true if product has options
     *
     * @return bool
     */
    public function hasOptions()
    {
        if ($this->isProductAutoshipEligible()) {
            return true;
        }
        else {
            return parent::hasOptions();
        }
    }

    /**
     * Return the product profile for the current product
     *
     * @return SFC_Autoship_Model_Platform_Product The Magento product profile entity object for the current product
     */
    public function getPlatformProduct()
    {
        if ($this->_platformProduct == null) {
            $this->_platformProduct = Mage::helper('autoship/platform')->getPlatformProduct($this->getProduct());
        }

        return $this->_platformProduct;
    }

    /**
     * Indicates whether this product is eligible for autoship or not
     *
     * @return bool
     */
    public function isProductAutoshipEligible()
    {
        // Get product
        $product = $this->getProduct();
        // Lookup whether product enabled / disabled for subscription
        $isProductEnabled = Mage::helper('autoship/product')->isAvailableForSubscription($product);

        return $isProductEnabled;
    }

    /**
     * Is this product a trial subscription product?
     *
     * @return boolean
     */
    public function isTrialProduct()
    {
        $platformProduct = $this->getPlatformProduct();

        return ($platformProduct->getData('is_trial_product'));
    }

    /**
     * Subscription option mode
     *
     * @return string
     */
    public function getSubscriptionOptionMode()
    {
        $platformProduct = $this->getPlatformProduct();

        return ($platformProduct->getData('subscription_option_mode'));
    }

    /**
     * Default subscription option
     *
     * @return string
     */
    public function getDefaultSubscriptionOption()
    {
        $platformProduct = $this->getPlatformProduct();

        return ($platformProduct->getData('default_subscription_option'));
    }

    /**
     * Return the price for purchasing the current product as a one time purchase, optionally format the returned price
     *
     * @param bool $formatted True to return the price formatted, false to return the raw price number
     * @return string Price of product, either formatted or as a raw number
     */
    public function getOneTimePurchasePrice($formatted = false)
    {
        return Mage::helper('autoship/subscription')
            ->getOneTimePurchasePrice($this->getProduct(), $this->getProductDefaultQty($this->getProduct()), $formatted);
    }

    /**
     * Return the price for purchasing the product with a subscription, optionally format the returned price
     *
     * @param bool $formatted True to return the price formatted, false to return the raw price number
     * @return string Price of product, either formatted or as a raw number
     */
    public function getSubscriptionPrice($formatted = false)
    {
        return Mage::helper('autoship/subscription')->getSubscriptionPrice($this->getPlatformProduct(), $this->getProduct(), $this->getProductDefaultQty($this->getProduct()), $formatted);
    }

    /**
     * Gets minimal sales quantity
     *
     * @param Mage_Catalog_Model_Product $product
     * @return int|null
     */
    public function getMinimalQty($product)
    {
        $mageDefault = parent::getMinimalQty($product);
        $platformProduct = $this->getPlatformProduct();
        if ($platformProduct->getData('subscription_option_mode') == 'subscription_only') {
            if ($platformProduct->getData('min_qty')) {
                return (!is_null($mageDefault) && $mageDefault > $platformProduct->getData('min_qty'))
                    ? $mageDefault
                    : $platformProduct->getData('min_qty');
            }
        }
        return $mageDefault;
    }
}
