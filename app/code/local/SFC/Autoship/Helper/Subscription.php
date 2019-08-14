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
 * Helper class to assist with displaying and formatting subscription
 */
class SFC_Autoship_Helper_Subscription extends Mage_Core_Helper_Abstract
{

    /**
     * Return the price for purchasing the product as a one time purchase, optionally format the returned price
     *
     * @param Mage_Catalog_Model_Product $product Mage product object
     * @param int $qty Product quantity for order, goes into catalog price calculation
     * @param bool $formatted True to return the price formatted, false to return the raw price number
     * @param null $inclTax
     * @return string Price of product, either formatted or as a raw number
     * @internal param bool $factorTax
     */
    public function getOneTimePurchasePrice(Mage_Catalog_Model_Product $product, $qty = 1, $formatted = false, $inclTax = null)
    {
        // Set customer group and store on product
        $product->setStoreId(Mage::app()->getStore()->getId());
        $product->setCustomerGroupId(Mage::getSingleton('customer/session')->getCustomer()->getGroupId());
        // Lookup price - Get catalog rule / special price / tier pricing / etc calculation
        $finalPrice = $product->getFinalPrice($qty);
        // If the product isn't discounted then default back to the original price
        if ($finalPrice===false) {
            $finalPrice = $product->getPrice();
        }
        if (is_null($inclTax)) {
            $inclTax = Mage::helper("tax")->displayPriceIncludingTax();
        }
        /**
         * Get the final price based on whether or not we've included tax
         */
        $finalPrice = $inclTax ? $this->getProductPriceInclTax($product, $finalPrice) : $this->getProductPriceExclTax($product, $finalPrice);
        // Format price if requested
        if ($formatted) {
            $finalPrice = Mage::helper('core')->currency($finalPrice, true, false);
        }

        // Return price
        return $finalPrice;
    }

    protected function isProductDiscountedInCatalog(Mage_Catalog_Model_Product $product, $qty = 1)
    {
        // Lookup final price
        $finalPrice = $this->getOneTimePurchasePrice($product, $qty);
        //Adjust the normal price for tax settings
        $productPrice = $this->adjustProductPriceForTax($product, $product->getPrice());

        // Check if product discounted
        $isProductDiscounted = ($finalPrice != $productPrice);

        return $isProductDiscounted;
    }

    /**
     * Return the price for purchasing the product with a subscription, optionally format the returned price
     *
     * @param SFC_Autoship_Model_Platform_Product $platformProduct Subscription profile for product
     * @param Mage_Catalog_Model_Product $product Mage product object
     * @param int $qty Product quantity for order, goes into catalog price calculation
     * @param bool $formatted True to return the price formatted, false to return the raw price number
     * @return string Price of product, either formatted or as a raw number
     */
    public function getSubscriptionPrice(SFC_Autoship_Model_Platform_Product $platformProduct, Mage_Catalog_Model_Product $product,
        $qty = 1, $formatted = false)
    {
        // Trial product info
        $isTrialProduct = $platformProduct->getData('is_trial_product');
        $trialPrice = $platformProduct->getData('trial_price');
        // Lookup discount % / amount
        $discount = $platformProduct->getDiscount();
        $isDiscountPercentage = $platformProduct->getData('is_discount_percentage');
        // Lookup final price
        $finalPrice = $this->getOneTimePurchasePrice($product, $qty);
        // Get config settings
        // Don't need $store param to getStoreConfig.  This method only called from frontend where store already set.
        $applyDiscountToCatalogPrice = Mage::getStoreConfig('autoship_subscription/discount/apply_discount_to_catalog_price');

        // Check for trial product
        if ($isTrialProduct) {
            // Set trial price as subscription price
            $subscriptionPrice = $trialPrice;
        }
        else {
            // Calculate discount using all biz logic
            if ($discount > 0.0) {
                if (!$applyDiscountToCatalogPrice && $this->isProductDiscountedInCatalog($product, $qty)) {
                    // Don't apply any subscription discount, because config is turned off and product already has discount
                    $subscriptionPrice = $finalPrice;
                }
                else {
                    if ($isDiscountPercentage) {
                        /**
                         * In the case of a percentage discount, we need to apply a discount to only the portion of the product price
                         * that is "discountable"
                         */
                        $subscriptionPrice = $finalPrice - ($this->getDiscountableProductPrice($product, $qty) * $discount);
                    }
                    else {
                        $subscriptionPrice = $finalPrice - $discount;
                    }
                }
            }
            else {
                $subscriptionPrice = $finalPrice;
            }
        }

        // Format price if requested
        if ($formatted) {
            $subscriptionPrice = Mage::helper('core')->currency($subscriptionPrice, true, false);
        }

        // Return price
        return $subscriptionPrice;
    }

    /**
     * Return the price for purchasing the product with a subscription, formatted and with text indicating the discount amount
     *
     * @param SFC_Autoship_Model_Platform_Product $platformProduct Subscription profile for product
     * @param Mage_Catalog_Model_Product $product Mage product object
     * @return string Price of product, formatted and with text indicating the discount
     */
    public function getSubscriptionPriceText(SFC_Autoship_Model_Platform_Product $platformProduct, Mage_Catalog_Model_Product $product, $qty = 1)
    {
        // Lookup config setting
        // Trial product info
        $isTrialProduct = $platformProduct->getData('is_trial_product');
        // Don't need $store param to getStoreConfig.  This method only called from frontend where store already set.
        $applyDiscountToCatalogPrice = Mage::getStoreConfig('autoship_subscription/discount/apply_discount_to_catalog_price');
        // Lookup discount % / amount
        $discount = $platformProduct->getDiscount();
        $isDiscountPercentage = $platformProduct->getData('is_discount_percentage');
        // Lookup price, including discount using method from SFC_Autoship_Block_Product_View
        $priceFormatted = $this->getSubscriptionPrice($platformProduct, $product, $qty, true);
        //Start an array to keep track of variables which can be used in translation code
        $translateVars = array($priceFormatted);
        // Build output text
        $priceText = '%s';
        // Add discount text
        if ($isTrialProduct) {
            $priceText .= ' trial price.';
        }
        else {
            if ($discount > 0.0 && ($applyDiscountToCatalogPrice || !$this->isProductDiscountedInCatalog($product, $qty))) {
                if ($isDiscountPercentage) {
                    $translateVars[] = 100.0 * $discount;
                    //%% = literal '%' character
                    $priceText .= ' with %s%% subscription discount.';
                    if (Mage::helper("tax")->needPriceConversion() && !Mage::helper("tax")->discountTax() &&
                        Mage::helper("tax")->displayPriceIncludingTax()
                    ) {
                        //If we are displaying a discount in which the math doesn't add up, display an additional note so the user is aware
                        $translateVars[] = substr($priceText, 0, -1);
                        $priceText = '%s (incl. tax).';
                    }
                }
                else {
                    $translateVars[] = Mage::helper('core')->currency($discount, true, false);
                    $priceText .= ' with %s subscription discount.';
                }
            }
        }

        // return text
        array_unshift($translateVars, $priceText);
        // translate expects each variable as a separate argument (not 1 argument of an array of variables)
        return call_user_func_array(array($this, '__'), $translateVars);
    }

    /**
     * Estimate order totals for a subscription order
     *
     * @param SFC_Autoship_Model_Subscription $subscription Subscription
     * @param SFC_Autoship_Model_Platform_Product $platformProduct Subscription profile for product
     * @param Mage_Catalog_Model_Product $product Mage product object
     * @return array Array containing estimated order totals
     */
    public function estimateSubscriptionOrderTotals(Mage_Customer_Model_Customer $customer,
        SFC_Autoship_Model_Subscription $subscription, SFC_Autoship_Model_Platform_Product $platformProduct,
        Mage_Catalog_Model_Product $product)
    {
        // Calculate basics
        $productPrice = $this->getSubscriptionPrice($platformProduct, $product);
        $lineItemQty = $subscription->getQty();
        $lineItemPrice = $lineItemQty * $productPrice;
        // Build estimate
        $estimate = array(
            'product_total' => $lineItemPrice,
            'shipping' => 0.0,
            'tax' => 0.0,
            'total' => $lineItemPrice,
        );

        // Check that we have necessary subscription settings to create / update a quote
        if (!strlen($subscription->getShippingAddressId()) ||
            !strlen($subscription->getShippingMethod())
        ) {
            // Not all setting set yet to generate a quote
            // Just pass through defaults
            // Calculate total
            $estimate['total'] = $estimate['product_total'] + $estimate['tax'] + $estimate['shipping'];
        }
        else {
            // Create or update Magento quote with all settings
            $quote = $this->createOrUpdateQuote($customer, $subscription, $platformProduct, $product);

            // Get quote totals
            // Adjust estimate based on Mage quote
            $totals = $quote->getTotals();
            foreach ($totals as $total) {
                if ($total->getCode() == 'subtotal') {
                    $estimate['product_total'] = $total->getValue();
                }
                if ($total->getCode() == 'discount') {
                    $estimate['discount'] = $total->getValue();
                }
                if ($total->getCode() == 'tax') {
                    $estimate['tax'] = $total->getValue();
                }
                if ($total->getCode() == 'shipping') {
                    $estimate['shipping'] = $total->getValue();
                }
                if ($total->getCode() == 'grand_total') {
                    $estimate['total'] = $total->getValue();
                }
            }
        }

        // Return estimate
        return $estimate;
    }

    public function createOrUpdateQuote(Mage_Customer_Model_Customer $customer, SFC_Autoship_Model_Subscription $subscription,
        SFC_Autoship_Model_Platform_Product $platformProduct, Mage_Catalog_Model_Product $product)
    {
        /*@var $quote Mage_Sales_Model_Quote*/
        $quote = null;
        // We are storing a quoteId inside subscription object, check if we find existing quoteId
        // If no existing quote, create a new one
        if (!strlen($subscription->getQuoteId())) {
            // Create new quote and set customer and product
            $quote = $this->createQuote($customer, $subscription, $platformProduct, $product);
        }
        else {
            // Otherwise, load existing quote
            $quote = Mage::getModel('sales/quote');
            $quote->load($subscription->getQuoteId());

            // Update product
            $quoteItem = $this->getQuoteItemByProduct($quote, $product, array(
                'product_id' => $product->getId(),
                'qty' => $subscription->getQty()
            ));
            $quoteItem->setQty($subscription->getQty());
        }

        /*
         * Now set / update various info on Magento quote
         */

        // Set billing address
        if (strlen($subscription->getBillingAddressId())) {
            $customerBillingAddress = Mage::getModel('customer/address')->load((int)$subscription->getBillingAddressId());
            $customerBillingAddress->explodeStreetAddress();
            if ($customerBillingAddress->getRegionId()) {
                $customerBillingAddress->setRegion($customerBillingAddress->getRegionId());
            }
            $quoteBillingAddress = Mage::getModel('sales/quote_address');
            $quoteBillingAddress->importCustomerAddress($customerBillingAddress);
            $quoteBillingAddress->implodeStreetAddress();
            if (($validateRes = $quoteBillingAddress->validate()) !== true) {
                // Address validation failed
                throw Mage::Exception($this->__('Customer address validation failed!'));
            }
            $quoteBillingAddress->setEmail($quote->getCustomer()->getEmail());
            $quote->setBillingAddress($quoteBillingAddress);
        }

        // Set shipping address        
        $customerShippingAddress = Mage::getModel('customer/address')->load((int)$subscription->getShippingAddressId());
        $customerShippingAddress->explodeStreetAddress();
        if ($customerShippingAddress->getRegionId()) {
            $customerShippingAddress->setRegion($customerShippingAddress->getRegionId());
        }
        $quoteShippingAddress = Mage::getModel('sales/quote_address');
        $quoteShippingAddress->importCustomerAddress($customerShippingAddress);
        $quoteShippingAddress->implodeStreetAddress();
        if (($validateRes = $quoteShippingAddress->validate()) !== true) {
            // Address validation failed
            throw Mage::Exception($this->__('Customer address validation failed!'));
        }
        $quoteShippingAddress
            ->setCollectShippingRates(true)
            ->setSameAsBilling(0);
        $quote->setShippingAddress($quoteShippingAddress);
        $quote->save();

        // Get shipping rate
        $rate = $quote->getShippingAddress()->collectShippingRates()->getShippingRateByCode($subscription->getShippingMethod());
        if (!$rate) {
            Mage::throwException($this->__('Shipping method not available!'));
        }
        $quote->getShippingAddress()->setShippingMethod($subscription->getShippingMethod());

        // Set payment method
        if (strlen($subscription->getCimPaymentProfileId())) {
            $paymentProfile = $subscription->getPaymentProfile();
            $curLast4 = substr($paymentProfile->getCustomerCardnumber(), -4);
            $encodedMethodCode = 'authnettoken' . '_cc4_' . $curLast4;
            $paymentData = array(
                'method' => $encodedMethodCode,
            );
            $quote->getShippingAddress()->setPaymentMethod($paymentData['method']);
            $quote->getShippingAddress()->setCollectShippingRates(true);
            try {
                // Import payment data
                $payment = $quote->getPayment();
                $payment->importData($paymentData);
            }
            catch (Mage_Core_Exception $e) {
                Mage::throwException($this->__('Payment method can not be set on quote!'));
            }
        }

        try {
            // Calculate totals on quote
            $quote->setTotalsCollectedFlag(false)
                ->collectTotals()
                ->save();
        }
        catch (Mage_Core_Exception $e) {
            Mage::throwException($this->__('Failed to calculate totals for quote!'));
        }

        // Return quote object
        return $quote;
    }

    public function createQuote(Mage_Customer_Model_Customer $customer, SFC_Autoship_Model_Subscription $subscription,
        SFC_Autoship_Model_Platform_Product $platformProduct, Mage_Catalog_Model_Product $product)
    {
        // Create quote
        /*@var $quote Mage_Sales_Model_Quote*/
        $quote = Mage::getModel('sales/quote');
        $quote->setStoreId(Mage::app()->getStore()->getId())
            ->setIsActive(false)
            ->setIsMultiShipping(false)
            ->save();

        try {
            // Build request and Add product
            $productRequest = new Varien_Object();
            $productRequest->setQty($subscription->getQty());
            $productRequest->setProductId($product->getId());
            $quoteItem = $quote->addProduct($product, $productRequest);
            //$itemPrice = $this->getSubscriptionPrice($platformProduct, $product);
            $quoteItem->setData('item_fulfils_subscription', true);
            $quoteItem->setData('subscription_id', '0'); // Dummy value, just to get estimate
            // We no longer use the custom price method
            //$quoteItem->setCustomPrice($itemPrice);
            //$quoteItem->setOriginalCustomPrice($itemPrice);
            //$quoteItem->setNoDiscount(true);
        }
        catch (Mage_Core_Exception $e) {
            Mage::throwException($this->__('Failed to add product to quote!'));
        }

        // Set customer
        $quote
            ->setCustomer($customer)
            ->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER);

        // Return new quote
        return $quote;
    }

    /**
     * Method inspired by Mage_Checkout_Model_Api_Resource_Product::_getQuoteItemByProduct
     * Get QuoteItem by Product and request info
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param Mage_Catalog_Model_Product $product
     * @param Varien_Object $requestInfo
     * @return Mage_Sales_Model_Quote_Item
     * @throw Mage_Core_Exception
     */
    protected function getQuoteItemByProduct(Mage_Sales_Model_Quote $quote, Mage_Catalog_Model_Product $product,
        Varien_Object $requestInfo)
    {
        $cartCandidates = $product->getTypeInstance(true)
            ->prepareForCartAdvanced(
                $requestInfo,
                $product,
                Mage_Catalog_Model_Product_Type_Abstract::PROCESS_MODE_FULL
            );

        /**
         * Error message
         */
        if (is_string($cartCandidates)) {
            Mage::throwException($cartCandidates);
        }

        /**
         * If prepare process return one object
         */
        if (!is_array($cartCandidates)) {
            $cartCandidates = array($cartCandidates);
        }

        /** @var $item Mage_Sales_Model_Quote_Item */
        $item = null;
        foreach ($cartCandidates as $candidate) {
            if ($candidate->getParentProductId()) {
                continue;
            }

            $item = $quote->getItemByProduct($candidate);
        }

        if (is_null($item)) {
            $item = Mage::getModel('sales/quote_item');
        }

        return $item;
    }

    /**
     * Get the product price including tax
     * @param Mage_Catalog_Model_Product $product
     * @param $finalPrice
     * @return float
     */
    public function getProductPriceInclTax(Mage_Catalog_Model_Product $product, $finalPrice)
    {
        $priceInclTax = Mage::helper("tax")->priceIncludesTax();
        if (!$priceInclTax) {
            $finalPrice = Mage::helper("tax")->getPrice($product, $finalPrice, true);
        }
        return $finalPrice;
    }

    /**
     * Get the product price excluding tax
     * @param Mage_Catalog_Model_Product $product
     * @param $finalPrice
     * @return float
     */
    public function getProductPriceExclTax(Mage_Catalog_Model_Product $product, $finalPrice)
    {
        $priceInclTax = Mage::helper("tax")->priceIncludesTax();
        if ($priceInclTax) {
            $finalPrice = Mage::helper("tax")->getPrice($product, $finalPrice, false);
        }
        return $finalPrice;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param int $qty
     * @return float
     */
    public function getDiscountableProductPrice(Mage_Catalog_Model_Product $product, $qty = 1)
    {
        $applyDiscountToTax = Mage::helper("tax")->discountTax();
        $finalPrice = $this->getOneTimePurchasePrice($product, $qty, false, $applyDiscountToTax); //Get the one time Purchase price, with or without tax
        return $finalPrice;
    }

    public function adjustProductPriceForTax(Mage_Catalog_Model_Product $product, $productPrice)
    {
        /**
         * Adjust the product price based on tax settings
         */
        $displayWithTax = Mage::helper("tax")->displayPriceIncludingTax();
        $priceInclTax = Mage::helper("tax")->priceIncludesTax();
        if ($displayWithTax && !$priceInclTax) {
            $productPrice = Mage::helper("tax")->getPrice($product, $productPrice, true);
        } elseif ($priceInclTax && !$displayWithTax) {
            $productPrice = Mage::helper("tax")->getPrice($product, $productPrice, false);
        }
        return $productPrice;
    }
}
