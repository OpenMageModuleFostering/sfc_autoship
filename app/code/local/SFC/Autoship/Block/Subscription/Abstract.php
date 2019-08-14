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
 * Abstract block, acts as base class for any block that displays 1 subscription
 */
class SFC_Autoship_Block_Subscription_Abstract extends Mage_Core_Block_Template
{
    private $_subscription = null;

    /**
     * @param SFC_Autoship_Model_Subscription $subscription
     * @return $this
     */
    public function setSubscription(SFC_Autoship_Model_Subscription $subscription)
    {
        $this->_subscription = $subscription;

        return $this;
    }

    /**
     * @return null|SFC_Autoship_Model_Subscription
     */
    public function getSubscription()
    {
        return $this->_subscription;
    }

    /**
     * Get the product for this subscription
     *
     * @return Mage_Catalog_Model_Product The Magento product for this subscription
     */
    public function getProduct()
    {
        return $this->getSubscription()->getProduct();
    }

    /**
     * Get the product profile for this subscription
     *
     * @return SFC_Autoship_Model_Platform_Product The product product model object for this subscription
     */
    public function getPlatformProduct()
    {
        return $this->getSubscription()->getPlatformProduct();
    }

    /**
     * Return eligible subscription intervals for the subscription product
     *
     * @return array Array of eligible subscription interval strings (for example: One Month, Two Months, etc)
     */
    public function getIntervals()
    {
        // Lookup intervals from product and subscription
        $productIntervals = $this->getPlatformProduct()->getData('intervals');
        $subscriptionInterval = $this->getSubscription()->getData('interval');
        // Add product interval if not present
        if (!is_array($productIntervals)) {
            return array($subscriptionInterval);
        }
        else if (!in_array($subscriptionInterval, $productIntervals)) {
            return array_merge(array($subscriptionInterval), $productIntervals);
        }
        else {
            return $productIntervals;
        }
    }

    /**
     * Return the price for purchasing the current product as a one time pruchase, optionally format the returned price
     *
     * @param bool $formatted True to return the price formatted, false to return the raw price number
     * @return string Price of product, either formatted or as a raw number
     */
    public function getOneTimePurchasePrice($formatted = false)
    {
        return Mage::helper('autoship/subscription')
            ->getOneTimePurchasePrice($this->getProduct(), $this->getSubscription()->getQty(), $formatted);
    }

    /**
     * Return the price for purchasing the product with a subscription, optionally format the returned price
     *
     * @param bool $formatted True to return the price formatted, false to return the raw price number
     * @return string Price of product, either formatted or as a raw number
     */
    public function getSubscriptionPrice($formatted = false)
    {
        return Mage::helper('autoship/subscription')
            ->getSubscriptionPrice($this->getPlatformProduct(), $this->getProduct(), $this->getSubscription()->getQty(), $formatted);
    }

    /**
     * Return the price for purchasing the product with a subscription, formatted and with text indicating the discount amount
     *
     * @return string Price of product, formatted and with text indicating the discount
     */
    public function getSubscriptionPriceText()
    {
        return Mage::helper('autoship/subscription')
            ->getSubscriptionPriceText($this->getPlatformProduct(), $this->getProduct(), $this->getSubscription()->getQty());
    }

    public function useCouponCode()
    {
        $allowCouponConfig = Mage::getStoreConfig('autoship_subscription/options/allow_coupon');

        return ($allowCouponConfig == 1);
    }

    public function getNextOrderDateMode()
    {
        $nextOrderDateMode = Mage::getStoreConfig('autoship_subscription/options/next_order_date_mode');

        return $nextOrderDateMode;
    }

}
