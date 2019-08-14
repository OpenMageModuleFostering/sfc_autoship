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

class SFC_Autoship_Helper_Quote extends Mage_Core_Helper_Abstract
{
    protected function _construct()
    {
    }

    public function getRelevantProductFromQuoteItem(Mage_Sales_Model_Quote_Item $quoteItem)
    {
        if ($quoteItem->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            return $quoteItem->getOptionByCode('simple_product')->getProduct();
        }
        else {
            return $quoteItem->getProduct();
        }
    }

    public function getRelevantProductFromOrderItem(Mage_Sales_Model_Order_Item $quoteItem)
    {
        if ($quoteItem->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            return $quoteItem->getOptionByCode('simple_product')->getProduct();
        }
        else {
            return $quoteItem->getProduct();
        }
    }

    public function addAdditionalOptionsToOrderItem(Mage_Sales_Model_Order_Item $orderItem)
    {
        if($orderItem->getData('item_fulfils_subscription')) {
            // Get options
            $options = $orderItem->getProductOptions();
            // Get existing additional_options
            if(isset($options['additional_options']) && is_array($options['additional_options'])) {
                $additionalOptions = $options['additional_options'];
            }
            else {
                $additionalOptions = array();
            }
            // Add our details
            $additionalOptions[] = array(
                'label' => $this->__('Product Subscription Id'),
                'value' => $orderItem->getData('subscription_id'),
            );
            $additionalOptions[] = array(
                'label' => $this->__('Subscription Interval'),
                'value' => $orderItem->getData('subscription_interval'),
            );
            // Set new additional_options on order item
            $options['additional_options'] = $additionalOptions;
            $orderItem->setProductOptions($options);
        }
    }

    /**
     * Does current quote (passed in quote or current shopping cart in session) have any products which are flagged for subscription?
     *
     * @param Mage_Sales_Model_Quote $quote Quote to check.  If null, method will check quote from cart session
     * @return bool
     */
    public function hasProductsToCreateNewSubscription(Mage_Sales_Model_Quote $quote = null)
    {
        // Get platform helper
        /** @var SFC_Autoship_Helper_Product $productHelper */
        $productHelper = Mage::helper('autoship/product');

        // If passed in quote is empty, get quote from cart in session
        if($quote == null) {
            if (Mage::app()->getStore()->isAdmin()) {
                $quote = Mage::getSingleton("adminhtml/session_quote")->getQuote();
            } else {
                // Get cart, quote and quote item
                /** @var Mage_Checkout_Model_Cart $cart */
                $cart = Mage::getSingleton('checkout/cart');
                // Get quote
                $quote = $cart->getQuote();
            }
        }
        // Iterate items in quote
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            // Get subscription product profile
            $product = $this->getRelevantProductFromQuoteItem($quoteItem);
            // Lookup whether product enabled / disabled for subscription
            $isProductEnabled = $productHelper->isAvailableForSubscription($product, $quote->getStore());
            // Check product profile, if this isn't a subscription product, ignore it
            if ($isProductEnabled) {
                // Check quote item flag which indicates we should create a new subscription for this product
                if ($quoteItem->getData('create_new_subscription_at_checkout')) {
                    return true;
                }
            }
            /*
            // We longer take the platform's enabled flag on product as authoritative
            $platformProduct = $platformHelper->getPlatformProduct($product);
            // Check product profile, if this isn't a subscription product, ignore it
            if ($platformProduct->getData('enabled')) {
                // Check quote item flag which indicates we should create a new subscription for this product
                if ($quoteItem->getData('create_new_subscription_at_checkout')) {
                    return true;
                }
            }
            */
        }

        // Didn't find any, return false
        return false;
    }

    public function hasSubscriptionReorderProduct(Mage_Sales_Model_Quote $quote = null)
    {
        // If passed in quote is empty, get quote from cart in session
        if($quote == null) {
            // Get cart, quote and quote item
            /** @var Mage_Checkout_Model_Cart $cart */
            $cart = Mage::getSingleton('checkout/cart');
            // Get quote
            $quote = $cart->getQuote();
        }
        // Iterate items in quote
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            // Check quote item attributes
            $itemFulfilsSubscription = $quoteItem->getData('item_fulfils_subscription');
            $itemCreatesNewSubscription = $quoteItem->getData('create_new_subscription_at_checkout');
            // Check quote item flag which indicates we should create a new subscription for this product
            if ($itemFulfilsSubscription && !$itemCreatesNewSubscription) {
                return true;
            }
        }

        // Didn't find any, return false
        return false;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param null $params
     */
    public function onCheckoutCartAddProductComplete(Mage_Catalog_Model_Product $product, $params = null)
    {
        if (is_null($params)) {
            // Get params from request
            $request = Mage::app()->getRequest();
            $params = $request->getParams();
        }
        // Filter delivery params
        $deliveryOption = isset($params['delivery-option']) ? $params['delivery-option'] : '';
        $deliveryInterval = isset($params['delivery-interval']) ? $params['delivery-interval'] : '';
        $requestQty = isset($params['qty']) ? $params['qty'] : 1;

        $this->updateProductInCart($product, $deliveryOption, $deliveryInterval);
    }

    /**
     * @param Mage_Catalog_Model_Product $groupedProduct
     * @param null $params
     */
    public function onCheckoutCartAddGroupedProductComplete(Mage_Catalog_Model_Product $groupedProduct, $params = null)
    {
        if (is_null($params)) {
            // Get request info
            // Get params from request
            $request = Mage::app()->getRequest();
            $params = $request->getParams();
        }
        $superGroupParam = isset($params['super_group']) ? $params['super_group'] : '';
        $deliveryOptionParam = isset($params['delivery-option']) ? $params['delivery-option'] : '';
        $deliveryIntervalParam = isset($params['delivery-interval']) ? $params['delivery-interval'] : '';

        // Get product type instance
        $typeInstance = $groupedProduct->getTypeInstance(true);
        // Iterate through associated products and handle 1 at a time
        /** @var Mage_Catalog_Model_Product $product */
        foreach ($typeInstance->getAssociatedProducts($groupedProduct) as $product) {
            // Check if product was added to the cart
            if (isset($superGroupParam[$product->getId()])) {
                // Check if quantity added was > 0
                if ($superGroupParam[$product->getId()] > 0) {
                    // Get params
                    // Update cart
                    if (Mage::app()->getStore()->isAdmin()) {
                        //If in admin, there is no parent item, so transfer custom options
                        $product->setCustomOptions(array(
                            'product_type' => new Varien_Object(array(
                                'value' => 'grouped',
                                'code' => 'product_type'
                            ))
                        ));
                    }
                    $this->updateProductInCart(
                        $product,
                        is_array($deliveryOptionParam) ? $deliveryOptionParam[$product->getId()] : '',
                        is_array($deliveryOptionParam) ? $deliveryIntervalParam[$product->getId()] : '');

                }
            }
        }
    }

    /**
     * @return Mage_Sales_Model_Quote
     */
    protected function _getQuote()
    {
        return Mage::app()->getStore()->isAdmin()
            ? Mage::getSingleton("adminhtml/session_quote")->getQuote()
            : Mage::getSingleton("checkout/cart")->getQuote();
    }

    /**
     * @return Mage_Adminhtml_Model_Session_Quote|Mage_Checkout_Model_Session
     */
    protected function _getCheckoutSession()
    {
        return Mage::app()->getStore()->isAdmin()
            ? Mage::getSingleton("adminhtml/session_quote")
            : Mage::getSingleton("checkout/session");
    }

    protected function updateProductInCart($product, $deliveryOption, $deliveryInterval)
    {
        // Get platform helper
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
        /** @var SFC_Autoship_Helper_Product $productHelper */
        $productHelper = Mage::helper('autoship/product');

        // Get quote
        $quote = $this->_getQuote();

        // Check if product is enabled
        // In admin, if the product isn't available for subscription it won't have an ID yet
        if (!$productHelper->isAvailableForSubscription($product, $quote->getStore())) {
            return;
        }

        //Get quote item
        $quoteItem = $quote->getItemByProduct($product);
        if ($quoteItem == null || !strlen($quoteItem->getId())) {
            // Not sure why this would ever happen, but just to be safe
            // Return here in case the quote item hasn't been saved yet (*should* only happen if isn't enabled for subscription)
            if (Mage::app()->getStore()->isAdmin() && !is_null($quoteItem)) {
                return;
            }
            Mage::throwException($this->__('Cant find quote item which was added!'));
        }

        // Get subscription product profile
        $platformProduct = $platformHelper->getPlatformProduct($product);

        // Get new product qty from cart / quote
        $quoteQty = $quoteItem->getQty();

        // Apply default delivery option if none set
        if (!strlen($deliveryOption)) {
            if ($platformProduct->getData('subscription_option_mode') != 'subscription_only'
                && $platformProduct->getData('default_subscription_option') == 'onetime_purchase'
            ) {
                $deliveryOption = 'one-time-delivery';
            } else {
                $deliveryOption = 'subscribe';
            }
        }

        // Implement trial subscription functionality
        if ($platformProduct->getData('is_trial_product')) {
            // Force subscription delivery option
            $deliveryOption = 'subscribe';
            // Set trial price on quote item
            $quoteItem->setCustomPrice($platformProduct->getData('trial_price'));
            $quoteItem->setOriginalCustomPrice($platformProduct->getData('trial_price'));
        }

        // Only do error messages if added product is set for subscription
        if ($deliveryOption == 'subscribe') {
            // Check qty to max sure we're in min - max range for subscription
            // Check the new quantity in the cart after addition
            $removeItem = false;
            if($quoteQty < $platformProduct->getData('min_qty')) {
                $this->_getCheckoutSession()->addError(
                    $this->__('Item %s requires minimum quantity of %s for subscription.',
                        $product->getSku(),
                        $platformProduct->getData('min_qty')
                    ));
                //If invalid qty and subscription only, remove the product
                if ($platformProduct->getData('subscription_option_mode') == 'subscription_only') {
                    $removeItem = true;
                }
            }
            if($quoteQty > $platformProduct->getData('max_qty')) {
                $this->_getCheckoutSession()->addError(
                    $this->__('Item %s allows maximum quantity of %s for subscription.',
                        $product->getSku(),
                        $platformProduct->getData('max_qty')
                    ));
                //If invalid qty and subscription only, remove the product
                if ($platformProduct->getData('subscription_option_mode') == 'subscription_only') {
                    $removeItem = true;
                }
            }
            //If we found a bug when adding the item (and it's admin) we have to remove it from cart
            if ($removeItem) {
                $product = $quoteItem->getProduct();
                //We have to remove the item this way due to a bug in Magento
                foreach($quote->getItemsCollection() as $key => $compItem) {
                    if ($compItem->getId() == $quoteItem->getId()) {
                        $quote->removeItem($key);
                        $quoteItem->delete();
                        break;
                    }
                }
                $quote->setTotalsCollectedFlag(false);
                $quote->save();
                if (!Mage::app()->getStore()->isAdmin()) {
                    Mage::app()->getResponse()->setRedirect($product->getProductUrl())->sendResponse();
                    exit;
                }
                return;
            }
        }

        // Set data on quote item
        // Only set subscription option on quote item if we are in we meet all conditions
        if($quoteQty >= $platformProduct->getData('min_qty') && $quoteQty <= $platformProduct->getData('max_qty')) {
            $quoteItem->setData('create_new_subscription_at_checkout', ($deliveryOption == 'subscribe'));
        }
        else {
            $quoteItem->setData('create_new_subscription_at_checkout', false);
        }
        // Apply default interval if no interval set
        if (!strlen($deliveryInterval)) {
            $deliveryInterval = $platformProduct->getData('default_interval');
        }
        if (!strlen($deliveryInterval)) {
            // If no default interval, go for the 1st one in the list
            if (count($intervals = $platformProduct->getData('intervals'))) {
                $deliveryInterval = $intervals[0];
            }
        }
        // Set interval on quote item regardless
        $quoteItem->setData('new_subscription_interval', $deliveryInterval);
        // Save quote item
        $quoteItem->save();
    }

    /**
     * @param Mage_Checkout_Model_Cart $cart
     * @param array $data
     */
    public function onCheckoutCartUpdateItemsAfter(Mage_Checkout_Model_Cart $cart, $data)
    {
        $this->updateQuoteItems($cart->getQuote(), $data);
    }

    /**
     * Update the items in a quote based on the incoming date
     * @param Mage_Sales_Model_Quote $quote
     * @param $data
     * @return $this
     */
    public function updateQuoteItems(Mage_Sales_Model_Quote $quote, $data)
    {
        // Get platform helper
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
        /** @var SFC_Autoship_Helper_Product $productHelper */
        $productHelper = Mage::helper('autoship/product');
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        $isAdmin = Mage::app()->getStore()->isAdmin();
        $session = $this->_getCheckoutSession();

        // Set store on api helper
        $apiHelper->setConfigStore($quote->getStore());
        // Iterate items in quote
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            $hasError = false;
            // Get corresponding data for this quote item
            $itemDeliveryOption = isset($data[$quoteItem->getId()]['delivery-option']) ? $data[$quoteItem->getId()]['delivery-option'] : '';
            $itemDeliveryInterval = isset($data[$quoteItem->getId()]['delivery-interval']) ? $data[$quoteItem->getId()]['delivery-interval'] : '';
            // Get subscription product profile
            $product = $this->getRelevantProductFromQuoteItem($quoteItem);
            // Check if product is enabled for subscription
            // Check product profile, if this isn't a subscription product, ignore it
            if ($productHelper->isAvailableForSubscription($product, $quote->getStore())) {
                // Get platform product
                $platformProduct = $platformHelper->getPlatformProduct($product);
                // Only do error messages if added product is set for subscription
                if ($itemDeliveryOption == 'subscribe') {
                    // Check qty to max sure we're in min - max range for subscription
                    if($quoteItem->getQty() < $platformProduct->getData('min_qty')) {
                        $session->addError(
                            $this->__('Item %s requires minimum quantity of %s for subscription.',
                                $product->getSku(),
                                $platformProduct->getData('min_qty')
                            ));
                        $hasError = true;
                    }
                    if($quoteItem->getQty() > $platformProduct->getData('max_qty')) {
                        $session->addError(
                            $this->__('Item %s allows maximum quantity of %s for subscription.',
                                $product->getSku(),
                                $platformProduct->getData('max_qty')
                            ));
                        $hasError = true;
                    }
                }

                // Set data on quote item
                // Only set subscription option on quote item if we are in we meet all conditions
                if($quoteItem->getQty() >= $platformProduct->getData('min_qty') && $quoteItem->getQty() <= $platformProduct->getData('max_qty')) {
                    $quoteItem->setData('create_new_subscription_at_checkout', ($itemDeliveryOption == 'subscribe'));
                }
                else if ($platformProduct->getData('subscription_option_mode') != 'subscription_only') {
                    $quoteItem->setData('create_new_subscription_at_checkout', false);
                }
                $quoteItem->setData('new_subscription_interval', $itemDeliveryInterval);
                /*
                 * We have to save the admin item if there is no error for the below situation:
                 * 1 item has an error and 1 does not
                 * Without saving the one that does not, all items will revert to "no subscription"
                 */
                if ($isAdmin) {
                    if ($hasError && $quoteItem->getOrigData('create_new_subscription_at_checkout')
                        && $platformProduct->getData('subscription_option_mode') == 'subscription_only'
                    ) {
                        //If this product can only be subscription, and an invalid qty was requested, revert the quantity
                        $quoteItem->setQty($quoteItem->getOrigData('qty'));
                        $buyRequest = $quoteItem->getBuyRequest();
                        $buyRequest->setData('qty', $quoteItem->getOrigData('qty'));
                        $optionValue = @serialize($buyRequest->getData());
                        $quoteItem->getOptionByCode('info_buyRequest')->setData('value', $optionValue)->save();
                        $quoteItem->save();
                    } else if (!$hasError) {
                        //Otherwise if there's no error make an update
                        $quoteItem->save();
                    }
                }
//                if (!$hasError && $isAdmin) {
//                    $quoteItem->save();
//                }
            }
        }
        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote_Item $quoteItem
     * @param Mage_Sales_Model_Order_Item $orderItem
     */
    public function onSalesConvertQuoteItemToOrderItem(Mage_Sales_Model_Quote_Item $quoteItem, Mage_Sales_Model_Order_Item $orderItem)
    {
        // Map additional options from quote item (from the buy request) to order item
        // TODO:    It would be ideal if we can get the additional_options from buy request into additional_options option field in
        //          the quote item at time quote item is created
        $buyRequest = unserialize($quoteItem->getOptionByCode('info_buyRequest')->getValue());
        if (isset($buyRequest['additional_options']) && count($buyRequest['additional_options'])) {
            $additionalOptions = $buyRequest['additional_options'];
            $options = $orderItem->getProductOptions();
            $options['additional_options'] = $additionalOptions;
            $orderItem->setProductOptions($options);
        }
        // Set fields / attributes from quote on to order item
        $orderItem->setData('item_fulfils_subscription', $quoteItem->getData('item_fulfils_subscription'));
        $orderItem->setData('subscription_id', $quoteItem->getData('subscription_id'));
        $orderItem->setData('subscription_interval', $quoteItem->getData('subscription_interval'));
        // If this item fulfils a subscription, update additional_information
        if($quoteItem->getData('item_fulfils_subscription')) {
            $this->addAdditionalOptionsToOrderItem($orderItem);
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param Mage_Sales_Model_Quote $quote
     */
    public function onSalesConvertOrderToQuote(Mage_Sales_Model_Order $order, Mage_Sales_Model_Quote $quote)
    {
        // Check for subscribe pro vault pay method
        if (0 === strpos($order->getPayment()->getMethod(), SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SUBSCRIBE_PRO_VAULT)) {
            // Quote was using SP vault pay method
            // Reset payment data fields on order
            $quote->getPayment()->setData('cc_type', '');
            $quote->getPayment()->setData('cc_number', '');
            $quote->getPayment()->setData('cc_exp_month', '');
            $quote->getPayment()->setData('cc_exp_year', '');
            $quote->getPayment()->setAdditionalInformation('save_card', '');
            $quote->getPayment()->setAdditionalInformation('is_new_card', '');
            $quote->getPayment()->setAdditionalInformation('payment_token', '');
            $quote->getPayment()->setAdditionalInformation('obscured_cc_number', '');
        }
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    public function onCheckoutSubmitAllAfter(Mage_Sales_Model_Quote $quote)
    {
        // Create subscriptions
        $createdSubscriptions = $this->createSubscriptionsFromQuote($quote);
        // Check result
        if(count($createdSubscriptions)) {
            // At least 1 subscription was created, set flag to display message on thank you page
            Mage::getSingleton('checkout/session')->setData('created_subscription_ids', array_keys($createdSubscriptions));
        }
    }

    /**
     * @param Mage_Sales_Model_Quote $quote
     * @return array
     */
    protected function createSubscriptionsFromQuote(Mage_Sales_Model_Quote $quote)
    {
        /** @var SFC_Autoship_Helper_Product $productHelper */
        $productHelper = Mage::helper('autoship/product');
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Set store on api helper
        $apiHelper->setConfigStore($quote->getStore());

        // Keep track of subscriptions created
        $subscriptions = array();
        // Maintain failed subscription count in session
        Mage::getSingleton('checkout/session')->setData('failed_subscription_count', 0);
        // Iterate shipping addresses
        /** @var Mage_Sales_Model_Quote_Address $curAddress */
        foreach ($quote->getAllShippingAddresses() as $curAddress) {
            // Iterate items in address
            /** @var Mage_Sales_Model_Quote_Item $quoteItem */
            foreach ($curAddress->getAllItems() as $quoteItem) {
                // Get subscription product profile
                $product = $this->getRelevantProductFromQuoteItem($quoteItem);
                // Check if product is enabled for subscription
                // Check product profile, if this isn't a subscription product, ignore it
                if ($productHelper->isAvailableForSubscription($product, $quote->getStore())) {
                    // Check quote item flag which indicates we should create a new subscription for this product
                    if ($quoteItem->getData('create_new_subscription_at_checkout')) {
                        $subscription = $this->createSubscriptionAndUpdateQuoteItem($quoteItem, $curAddress);
                        $subscriptions[] = $subscription;
                    }
                }
            }
        }
        // Now iterate virtual products
        // Iterate items
        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL || $quoteItem->getProductType() == Mage_Downloadable_Model_Product_Type::TYPE_DOWNLOADABLE) {
                // Get subscription product profile
                $product = $this->getRelevantProductFromQuoteItem($quoteItem);
                // Check if product is enabled for subscription
                // Check product profile, if this isn't a subscription product, ignore it
                if ($productHelper->isAvailableForSubscription($product, $quote->getStore())) {
                    // Check quote item flag which indicates we should create a new subscription for this product
                    if ($quoteItem->getData('create_new_subscription_at_checkout')) {
                        // For virtual products, set billing address as shipping address
                        $subscription = $this->createSubscriptionAndUpdateQuoteItem($quoteItem, $quote->getBillingAddress());
                        $subscriptions[] = $subscription;
                    }
                }
            }
        }

        // Return array of created subs
        return $subscriptions;
    }

    private function createSubscriptionAndUpdateQuoteItem(Mage_Sales_Model_Quote_Item $quoteItem, Mage_Sales_Model_Quote_Address $shippingAddress)
    {
        // Get platform helper
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
        try {
            // Create subscription from this item
            $subscription =
                $this->createSubscriptionFromQuoteItem($quoteItem, $shippingAddress, $quoteItem->getData('new_subscription_interval'));

            Mage::dispatchEvent('sfc_autoship_before_create_subscription_from_quote_item',
                array('subscription' => $subscription, 'quote_item' => $quoteItem));

            // Create subscription via API
            $subscriptionId = $platformHelper->createSubscription($subscription);
            // Save in array
            $subscriptions[$subscriptionId] = $subscription;
            // Save subscription id and flag on quote item
            $quoteItem->setData('subscription_id', $subscriptionId);
            $quoteItem->setData('subscription_interval', $subscription->getData('interval'));
            $quoteItem->setData('item_fulfils_subscription', true);
            $quoteItem->save();
            // Lookup order item
            /** @var Mage_Sales_Model_Order_Item $orderItem */
            $orderItem = Mage::getModel('sales/order_item')->load($quoteItem->getId(), 'quote_item_id');
            // Save subscription id and flag on order item
            if(strlen($orderItem->getId())) {
                $orderItem->setData('subscription_id', $subscriptionId);
                $orderItem->setData('subscription_interval', $subscription->getData('interval'));
                $orderItem->setData('item_fulfils_subscription', true);
                $this->addAdditionalOptionsToOrderItem($orderItem);
                $orderItem->save();
            }

            Mage::dispatchEvent('sfc_autoship_after_create_subscription_from_quote_item',
                array('subscription' => $subscription, 'quote_item' => $quoteItem));

            return $subscription;
        }
        catch(\Exception $e) {
            // Increment failed subscription count
            Mage::getSingleton('checkout/session')->setData(
                'failed_subscription_count',
                1 + Mage::getSingleton('checkout/session')->getData('failed_subscription_count')
            );
        }
    }

    /**
     * @param Mage_Sales_Model_Quote_Item $quoteItem
     * @param Mage_Sales_Model_Quote_Address $shippingAddress
     * @param string $interval
     * @return SFC_Autoship_Model_Subscription
     */
    protected function createSubscriptionFromQuoteItem(Mage_Sales_Model_Quote_Item $quoteItem, Mage_Sales_Model_Quote_Address $shippingAddress, $interval)
    {
        // Get quote
        $quote = $quoteItem->getQuote();
        // Get customer
        $customer = $quote->getCustomer();

        // Create default subscription
        /** @var SFC_Autoship_Model_Subscription $subscription */
        $subscription = Mage::getModel('autoship/subscription');
        $subscription->setData('next_order_date', date('Y-m-d'));
        $product = $this->getRelevantProductFromQuoteItem($quoteItem);
        $subscription->setData('product_id', $product->getId());
        $subscription->setData('customer_id', $customer->getId());
        $subscription->setData('billing_address_id', $quote->getBillingAddress()->getCustomerAddressId());
        $subscription->setData('billing_first_name', $quote->getBillingAddress()->getFirstname());
        $subscription->setData('billing_last_name', $quote->getBillingAddress()->getLastname());
        $subscription->setData('magento_store_code', $quote->getStore()->getCode());
        // Workaround for Magento issue with Registering during checkout
        // UGH Magento!
        if(strlen($shippingAddress->getCustomerAddressId())) {
            $subscription->setData('shipping_address_id', $shippingAddress->getCustomerAddressId());
        }
        else {
            $subscription->setData('shipping_address_id', $quote->getBillingAddress()->getCustomerAddressId());
        }
        $subscription->setData('shipping_method', $shippingAddress->getData('shipping_method'));
        $subscription->setData('qty', $quoteItem->getQty());
        $subscription->setData('interval', $interval);
        // Set skip first order flag, since we just placed an order for today
        $subscription->setData('skip_first_order', true);
        // Save coupon code on subscription, if config setting enabled
        if(Mage::getStoreConfig('autoship_subscription/options/allow_coupon', $quote->getStore()) == 1) {
            $subscription->setData('coupon_code', $quote->getCouponCode());
        }

        // Handle product options, save them to subscription
        $this->addProductOptionsToSubscription($subscription, $quoteItem);

        // Set payment details on subscription based on quote / order
        $this->addPaymentInfoToSubscription($subscription, $quoteItem);

        // Return the new subscription model
        return $subscription;
    }

    protected function addProductOptionsToSubscription(SFC_Autoship_Model_Subscription $subscription, Mage_Sales_Model_Quote_Item $quoteItem)
    {
        // Create array to hold product options and then be stored in subscription
        $productOptions = array();
        // Get buy request object
        $buyRequest = $quoteItem->getBuyRequest();
        // If this is a bundle product
        if ($quoteItem->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            // Get bundle options from buy request
            $bundleOption = $buyRequest->getData('bundle_option');
            $bundleOptionQty = $buyRequest->getData('bundle_option_qty');
            if (is_array($bundleOption) && count($bundleOption)) {
                // Save bundle options with subscription
                $productOptions['bundle_option'] = $bundleOption;
                $productOptions['bundle_option_qty'] = $bundleOptionQty;
            }
        }
        // Save custom options
        $customOptions = $buyRequest->getData('options');
        if (is_array($customOptions) && count($customOptions)) {
            $productOptions['options'] = $customOptions;
        }
        // Store all options with subscription
        $subscription->setData('magento_product_options', $productOptions);
    }

    protected function addPaymentInfoToSubscription(SFC_Autoship_Model_Subscription $subscription, Mage_Sales_Model_Quote_Item $quoteItem)
    {
        // Get quote
        $quote = $quoteItem->getQuote();
        // Get payment info
        $payment = $quote->getPayment();

        // Get helper
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
        // Lookup payment method code based on SP config
        $configuredMethodCode = $platformHelper->getConfiguredPaymentMethodCode();

        // Assert that quote / order was placed using compatible payment method
        if(0 !== strpos($payment->getMethod(), $configuredMethodCode)) {
            Mage::throwException($this->__('Cannot create subscription(s) unless compatible payment method is selected!'));
        }

        // Set method code / gateway on sub
        $subscription->setData('payment_method_code', $configuredMethodCode);

        // Check payment method code / type
        switch ($configuredMethodCode) {

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CIM_10XX:
            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CIM:
                // Check that quote payment has payment_profile_id, otherwise get order payment
                if(!strlen($payment->getAdditionalInformation('payment_profile_id'))) {
                    // Lookup order corresponding to quote
                    $order = Mage::getModel('sales/order')->getCollection()
                        ->addFieldToFilter('quote_id', $quote->getId())
                        ->getFirstItem();
                    if(strlen($order->getId())) {
                        $payment = $order->getPayment();
                    }
                }
                // Set data on subscription
                $subscription->setData('payment_token', $payment->getAdditionalInformation('payment_profile_id'));
                $subscription->setData('customer_cardnumber', $payment->getAdditionalInformation('saved_cc_last_4'));
                return;

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SUBSCRIBE_PRO_VAULT:
                // Set data on subscription, just the payment token is enough for the SP platform to lookup the customer's payment profile
                $subscription->setData('payment_token', $payment->getAdditionalInformation('payment_token'));
                return;

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CYBERSOURCE:
                // Check that quote payment has payment_profile_id, otherwise get order payment
                if(!strlen($payment->getAdditionalInformation('payment_token'))) {
                    // Lookup order corresponding to quote
                    $order = Mage::getModel('sales/order')->getCollection()
                        ->addFieldToFilter('quote_id', $quote->getId())
                        ->getFirstItem();
                    if(strlen($order->getId())) {
                        $payment = $order->getPayment();
                    }
                }
                // Set data on subscription
                $subscription->setData('payment_token', $payment->getAdditionalInformation('payment_token'));
                $subscription->setData('customer_cardnumber', $payment->getData('cc_last4'));
                return;

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_BRAINTREE:
                return;

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_PARADOX_TRANSARMOR:
                // Lookup order corresponding to quote
                /** @var Mage_Sales_Model_Order $order */
                $order = Mage::getModel('sales/order')->getCollection()
                    ->addFieldToFilter('quote_id', $quote->getId())
                    ->getFirstItem();
                if(!strlen($order->getId())) {
                    Mage::throwException($this->__('Failed to find order corresponding to quote to create subscriptions for TransArmor quote!'));
                }
                /** @var Mage_Sales_Model_Order_Payment $payment */
                $payment = $order->getPayment();
                // Set data on subscription
                $subscription->setData('payment_token', $payment->getAdditionalInformation('token'));
                $subscription->setData('customer_cardnumber', $payment->getData('cc_last4'));
                return;

        }

    }

}
