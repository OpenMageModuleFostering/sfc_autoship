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
 * Observer class to handle all event observers for subscriptions module
 */
class SFC_Autoship_Model_Observer
{
    /**
     * Save Product Subscription Data
     *
     */
    public function onProductSaveCommitAfter(Varien_Event_Observer $observer)
    {
        Mage::log('SFC_Autoship_Model_Observer::onProductSaveCommitAfter', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        // Get current product
        $product = $observer->getEvent()->getProduct();

        // Check that we have a real product
        if (strlen($product->getId())) {
            // Call helper to update product / product profile in Magento and on platform
            Mage::helper('autoship/platform')->handleOnSaveProduct($product);
        }
    }

    /**
     * Handle checkout_cart_add_product_complete event
     *
     * @param Varien_Event_Observer $observer
     *
     * Might be better to observe a different event, like checkout_cart_product_add_after, but these events all happen before quote is saved
     * public function onCheckoutCartProductAddAfter(Mage_Sales_Model_Quote_Item $quoteItem, Mage_Catalog_Model_Product $product)
     */
    public function onCheckoutCartAddProductComplete(Varien_Event_Observer $observer)
    {
        Mage::log('SFC_Autoship_Model_Observer::onCheckoutCartAddProductComplete', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        // Get store for config checks
        $store = Mage::getSingleton('checkout/cart')->getQuote()->getStore();

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $store) != '1') {
            return;
        }

        // Only respond to this event when configured to create new subscriptions through cart
        if (Mage::getStoreConfig('autoship_subscription/subscription/use_new_subscription_page', $store) == '1') {
            return;
        }

        // Get data from $observer
        $product = $observer->getData('product');

        // Get product type
        $productType = $product->getTypeId();

        // Call helper to handle this event
        /** @var SFC_Autoship_Helper_Quote $quoteHelper */
        $quoteHelper = Mage::helper('autoship/quote');
        // Check product type
        if ($productType == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            $quoteHelper->onCheckoutCartAddGroupedProductComplete($product);
        }
        //else if ($productType == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
        //else if ($productType == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
        //else if ($productType == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
        else {
            $quoteHelper->onCheckoutCartAddProductComplete($product);
        }

    }

    public function onCheckoutCartUpdateItemsAfter(Varien_Event_Observer $observer)
    {
        Mage::log('SFC_Autoship_Model_Observer::onCheckoutCartUpdateItemsAfter', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        // Get data from $observer
        /** @var Mage_Checkout_Model_Cart $cart */
        $cart = $observer->getData('cart');
        /** @var array $data */
        $data = $observer->getData('info');

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $cart->getQuote()->getStore()) != '1') {
            return;
        }

        // Only respond to this event when configured to create new subscriptions through cart
        if (Mage::getStoreConfig('autoship_subscription/subscription/use_new_subscription_page', $cart->getQuote()->getStore()) == '1') {
            return;
        }

        // Call helper to handle this event
        /** @var SFC_Autoship_Helper_Quote $quoteHelper */
        $quoteHelper = Mage::helper('autoship/quote');
        $quoteHelper->onCheckoutCartUpdateItemsAfter($cart, $data);

    }

    public function onSalesConvertQuoteItemToOrderItem(Varien_Event_Observer $observer)
    {
        Mage::log('SFC_Autoship_Model_Observer::onSalesConvertQuoteItemToOrderItem', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        // Get data from $observer
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        $quoteItem = $observer->getData('item');
        /** @var Mage_Sales_Model_Order_Item $orderItem */
        $orderItem = $observer->getData('order_item');

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $quoteItem->getStore()) != '1') {
            return;
        }

        // Call helper to handle this event
        /** @var SFC_Autoship_Helper_Quote $quoteHelper */
        $quoteHelper = Mage::helper('autoship/quote');
        $quoteHelper->onSalesConvertQuoteItemToOrderItem($quoteItem, $orderItem);
    }

    public function onSalesConvertOrderToQuote(Varien_Event_Observer $observer)
    {
        Mage::log('SFC_Autoship_Model_Observer::onSalesConvertOrderToQuote', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        // Get data from $observer
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getData('quote');
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getData('order');

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $quote->getStore()) != '1') {
            return;
        }

        // Call helper to handle this event
        /** @var SFC_Autoship_Helper_Quote $quoteHelper */
        $quoteHelper = Mage::helper('autoship/quote');
        $quoteHelper->onSalesConvertOrderToQuote($order, $quote);
    }

    public function onCheckoutSubmitAllAfter(Varien_Event_Observer $observer)
    {
        Mage::log('SFC_Autoship_Model_Observer::onCheckoutSubmitAllAfter', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        // Get data from $observer
        /** @var Mage_Sales_Model_Quote $quote */
        $quote = $observer->getData('quote');

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $quote->getStore()) != '1') {
            return;
        }

        // Only respond to this event when configured to create new subscriptions through cart
        if (Mage::getStoreConfig('autoship_subscription/subscription/use_new_subscription_page', $quote->getStore()) == '1') {
            return;
        }

        try {
            // Call helper to handle this event
            /** @var SFC_Autoship_Helper_Quote $quoteHelper */
            $quoteHelper = Mage::helper('autoship/quote');
            $quoteHelper->onCheckoutSubmitAllAfter($quote);
        }
        catch (\Exception $e) {
            Mage::log('Failed to create subscription(s)!', Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
            Mage::log('Error message: ' . $e->getMessage(), Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
        }
    }

    public function onCheckoutOnepageControllerSuccessAction(Varien_Event_Observer $observer)
    {
        Mage::log('SFC_Autoship_Model_Observer::onCheckoutOnepageControllerSuccessAction', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled') != '1') {
            return;
        }

        // Only respond to this event when configured to create new subscriptions through cart
        if (Mage::getStoreConfig('autoship_subscription/subscription/use_new_subscription_page') == '1') {
            return;
        }

        try {
            // Get data from $observer
            /** @var Mage_Sales_Model_Quote $quote */
            $orderIds = $observer->getData('order_ids');

            // Inject create subscription ids into block
            /** @var Mage_Core_Block_Template $blockCheckoutSuccessSubscriptions */
            $blockCheckoutSuccessSubscriptions = Mage::getSingleton('core/layout')->getBlock('checkout.success.subscriptions');
            $blockCheckoutSuccessSubscriptions->setData(
                'created_subscription_ids',
                Mage::getSingleton('checkout/session')->getData('created_subscription_ids'));
            $blockCheckoutSuccessSubscriptions->setData(
                'failed_subscription_count',
                Mage::getSingleton('checkout/session')->getData('failed_subscription_count'));
            // Clear data from checkout session
            Mage::getSingleton('checkout/session')->setData('created_subscription_ids', null);
            Mage::getSingleton('checkout/session')->setData('failed_subscription_count', 0);
        }
        catch (\Exception $e) {
            Mage::log('Failed to display subscription created message on one-page checkout success page!', Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
            Mage::log('Error message: ' . $e->getMessage(), Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
        }
    }

    /**
     * @deprecated
     * @param Varien_Event_Observer $observer
     */
    public function onSalesQuoteAddressDiscountItem(Varien_Event_Observer $observer)
    {
        Mage::log('SFC_Autoship_Model_Observer::onSalesQuoteAddressDiscountItem', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
    }

    /**
     * Check is allowed guest checkout if quote contains subscription products
     *
     * @param Varien_Event_Observer $observer
     * @return SFC_Autoship_Model_Observer
     */
    public function isAllowedGuestCheckout(Varien_Event_Observer $observer)
    {
        Mage::log('SFC_Autoship_Model_Observer::isAllowedGuestCheckout', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        // Get data from $observer
        /** @var Mage_Sales_Model_Quote $quote */
        $quote  = $observer->getData('event')->getQuote();
        $result = $observer->getData('event')->getResult();

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $quote->getStore()) != '1') {
            return $this;
        }

        // Get quote helper
        /** @var SFC_Autoship_Helper_Quote $quoteHelper */
        $quoteHelper = Mage::helper('autoship/quote');
        // Check if quote has any subscriptions in it
        if($quoteHelper->hasProductsToCreateNewSubscription($quote)) {
            // Quote has subscriptions, disable guest checkout
            $result->setData('is_allowed', false);
        }

        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function onAdminhtmlSalesOrderCreateProcessData(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Model_Sales_Order_Create $orderCreateModel */
        $orderCreateModel = $observer->getEvent()->getOrderCreateModel();

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $orderCreateModel->getQuote()->getStore()) != '1') {
            return $this;
        }

        //If request has 'item' present, we have updated, added, or removed an item
        if (Mage::app()->getRequest()->has('item')) {

            //Update all quote item
            Mage::helper("autoship/quote")->updateQuoteItems($orderCreateModel->getQuote(), Mage::app()->getRequest()->getPost('item'), true);

            $action = Mage::app()->getRequest()->getActionName();

            if (!Mage::app()->getRequest()->getPost('update_items') && !($action == 'save')) {

                //Process all newly added items, so we can set their defaults
                foreach (Mage::app()->getRequest()->getPost('item') as $productId => $config) {
                    //Validation on the product id has already been done at this point
                    $config['qty'] = isset($config['qty']) ? (float)$config['qty'] : 1;
                    $product = Mage::getModel('catalog/product')
                        ->setStore($orderCreateModel->getQuote()->getStore())
                        ->setStoreId($orderCreateModel->getQuote()->getStore()->getId())
                        ->load($productId);

                    $cartProduct = $product;

                    if ($cartProduct->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                        //In order to ensure the custom options match from product<-->quote item, we run it through prepareForCartAdvanced
                        $cartCandidates = $product->getTypeInstance(true)
                            ->prepareForCartAdvanced(new Varien_Object($config), $product, null);

                        if (sizeof($cartCandidates) < 1) {
                            Mage::throwException("Unable to add bundle product with sku: " . $product->getSku() . ' to cart');
                        }

                        /** @var Mage_Catalog_Model_Product $candidate */
                        foreach($cartCandidates as $candidate) {
                            //Ensure we get the bundled product
                            if ($candidate->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                                $cartProduct = $candidate;
                                break;
                            }
                        }
                    }

                    //Different logic for grouped
                    if ($cartProduct->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
                        Mage::helper("autoship/quote")->onCheckoutCartAddGroupedProductComplete($cartProduct, $config);
                    } else {
                        Mage::helper("autoship/quote")->onCheckoutCartAddProductComplete($cartProduct, $config);
                    }
                }
            }

            //Recollect
            $orderCreateModel->recollectCart();

        }

        return $this;
    }

    /**
     * When a customer is saved, check if they exist on the platform. If so, update the platform with their data
     * @param $obs
     * @return $this
     */
    public function onCustomerSave($obs)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $obs->getCustomer();
        //Make sure the customer isn't brand new and actually has an original email
        if (!$customer->isObjectNew()
            && $customer->getOrigData('email')
            && $customer->dataHasChangedFor('email')) {

            /** @var SFC_Autoship_Helper_Platform $platformHelper */
            $platformHelper = Mage::helper("autoship/platform");

            /** @var SFC_Autoship_Helper_Api $apiHelper */
            $apiHelper = Mage::helper("autoship/api");

            //Update config store to match store customer is associated to?
            $apiHelper->setConfigStore($customer->getStore());

            $platformCustomer = $platformHelper->getCustomer($customer->getOrigData('email'));
            if ($platformCustomer) {
                Mage::log('SFC_Autoship_Model_Observer::onCustomerSave Customer with email: ' . $customer->getEmail() . ' was changed, updating platform', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
                $platformHelper->updateCustomer($platformCustomer['id'], $customer);
            } else {
                Mage::log('SFC_Autoship_Model_Observer::onCustomerSave Customer with email: ' . $customer->getEmail() . ' was changed, but does not exist on platform', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            }
        }
        return $this;
    }

    /**
     * When re-ordering via admin, ensure we don't copy another subscription's id over
     * @see Mage_Adminhtml_Model_Sales_Order_Create::initFromOrderItem
     * @param $observer
     */
    public function onSalesConvertOrderItemToQuoteItem($observer)
    {
        /** @var Mage_Sales_Model_Quote_Item $item */
        $item = $observer->getQuoteItem();

        // For bundled product, only 1 item will be passed to the observer
        // This item *might* be the bundled product or it might not be
        // If it's not, go ahead and grab the bundled product from the parent
        // Seems to be a bug in magento, @see Mage_Adminhtml_Model_Sales_Order_Create::initFromOrderItem
        if ($item->getParentItem() && $item->getParentItem()->getProduct()->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $item = $item->getParentItem();
        }
        /** @var SFC_Autoship_Helper_Product $productHelper */
        $productHelper = Mage::helper('autoship/product');

        $product = $item->getProduct();

        // Check if product is enabled, otherwise don't bother updating the custom options
        if (!$productHelper->isAvailableForSubscription($product, $item->getQuote()->getStore())) {
            return;
        }

        //Remove the subscription, so re-ordering doesn't result in 2 subscription ids
        $item->removeOption('additional_options');
        $item->setSubscriptionId(null)->save();

        //Set basic config
        $config = array(
            'qty' => $item->getQty()
        );

        //Different logic for grouped
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            Mage::helper("autoship/quote")->onCheckoutCartAddGroupedProductComplete($product, $config);
        } else {
            Mage::helper("autoship/quote")->onCheckoutCartAddProductComplete($product, $config);
        }
    }

}
