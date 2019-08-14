<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */
Class SFC_Autoship_Block_Adminhtml_Sales_Order_Create_Product_Subscription
    extends Mage_Adminhtml_Block_Template
{
    /**
     * Get order item object from parent block
     *
     * @return Mage_Sales_Model_Quote_Item
     */
    public function getItem()
    {
        return $this->getParentBlock()->getData('item');
    }

    /**
     * @return Mage_Sales_Model_Quote_Item
     */
    public function getQuoteItem()
    {
        return $this->getItem();
    }

    /**
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct()
    {
        return Mage::helper('autoship/quote')->getRelevantProductFromQuoteItem($this->getQuoteItem());
    }

    /**
     * Return the product profile for the current product
     *
     * @return SFC_Autoship_Model_Platform_Product The Magento product profile entity object for the current product
     */
    public function getPlatformProduct()
    {
        return Mage::helper('autoship/platform')->getPlatformProduct($this->getProduct());
    }

    /**
     * Indicates whether this product is eligible for subscription or not
     *
     * @return bool
     */
    public function isItemSubscriptionEligible()
    {
        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled') != '1') {
            return false;
        }

        //Quote item is null when removing a product
        if (!$this->getQuoteItem()) {
            return false;
        }

        $platformProduct = $this->getPlatformProduct();

        return $platformProduct->getEnabled();
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
     * @return bool
     */
    public function isItemFlaggedToCreateNewSubscription()
    {
        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled') != '1') {
            return false;
        }

        // Get quote item
        $quoteItem = $this->getQuoteItem();
        // Return subscription flag
        return $quoteItem->getData('create_new_subscription_at_checkout');
    }

    /**
     * Get new subscription interval set on current quote item
     *
     * @return string
     */
    public function getNewSubscriptionInterval()
    {
        // Get quote item
        $quoteItem = $this->getQuoteItem();
        // Return subscription flag
        return $quoteItem->getData('new_subscription_interval');
    }

    /**
     * Return eligible subscription intervals for this product
     *
     * @return array Array of eligible subscription interval strings (for example: One Month, Two Months, etc)
     */
    public function getIntervals()
    {
        return $this->getPlatformProduct()->getIntervals();
    }

    /**
     * Gets minimal sales quantity
     *
     * @param Mage_Catalog_Model_Product $product
     * @return int|null
     */
    public function getMinimalQty($product)
    {
        $stockItem = $product->getStockItem();
        if ($stockItem) {
            return ($stockItem->getMinSaleQty()
            && $stockItem->getMinSaleQty() > 0 ? $stockItem->getMinSaleQty() * 1 : null);
        }
        return null;
    }

    /**
     * Get default qty - either as preconfigured, or as 1.
     * Also restricts it by minimal qty.
     *
     * @param null|Mage_Catalog_Model_Product $product
     * @return int|float
     */
    public function getProductDefaultQty($product = null)
    {
        if (!$product) {
            $product = $this->getProduct();
        }

        $qty = $this->getMinimalQty($product);
        $config = $product->getPreconfiguredValues();
        $configQty = $config->getQty();
        if ($configQty > $qty) {
            $qty = $configQty;
        }

        return $qty;
    }

    /**
     * Return the discount text for display on product page
     *
     * @return string Discount text for product page
     */
    public function getDiscountText()
    {
        return Mage::helper('autoship/subscription')->getSubscriptionPriceText($this->getPlatformProduct(), $this->getProduct(), $this->getProductDefaultQty($this->getProduct()));
    }
}