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
 * Special code to make us compatible with Mexbs_Tieredcoupon module
 */
if (file_exists(realpath(dirname(__FILE__)) . '/../../../Mexbs/Tieredcoupon/Model/SalesRule/Validator.php') &&
    class_exists('Mexbs_Tieredcoupon_Model_SalesRule_Validator'))
{
    class SFC_Autoship_Model_SalesRule_Validator_Base extends Mexbs_Tieredcoupon_Model_SalesRule_Validator
    {
    }
}
else
{
    class SFC_Autoship_Model_SalesRule_Validator_Base extends Mage_SalesRule_Model_Validator
    {
    }
}

class SFC_Autoship_Model_SalesRule_Validator extends SFC_Autoship_Model_SalesRule_Validator_Base
{
    /**
     * Quote item discount calculation process
     *
     * @param   Mage_Sales_Model_Quote_Item_Abstract $quoteItem
     * @return  Mage_SalesRule_Model_Validator
     */
    public function process(Mage_Sales_Model_Quote_Item_Abstract $quoteItem)
    {
        // Get quote & address & Store
        $quote = $quoteItem->getQuote();
        $address = $this->_getAddress($quoteItem);
        $store = $quoteItem->getStore();

        // Check config to see if extension functionality is enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $store) != '1') {
            return parent::process($quoteItem);
        }

        // Save appliedRuleIds before calling parent method
        $appliedRuleIds = array(
            'quoteItem' => $quoteItem->getAppliedRuleIds(),
            'quote' => $quote->getAppliedRuleIds(),
            'address' => $address->getAppliedRuleIds(),
        );
        // Save discountDescriptionArray
        $discountDescriptionArray = $address->getDiscountDescriptionArray();

        // Call parent method to process shopping cart price rules
        parent::process($quoteItem);

        // Check if item is part of a subscription or a subscription that is about to be created
        $partOfSubscription = $this->_isItemPartOfSubscription($quoteItem, $store);

        if($partOfSubscription) {

            // Get platform helper
            /** @var SFC_Autoship_Helper_Platform $platformHelper */
            $platformHelper = Mage::helper('autoship/platform');
            /** @var SFC_Autoship_Helper_Api $apiHelper */
            $apiHelper = Mage::helper('autoship/api');
            $apiHelper->setConfigStore($store);

            // Lookup product profile, including current settings and discount
            $product = Mage::helper('autoship/quote')->getRelevantProductFromQuoteItem($quoteItem);
            $platformProduct = $platformHelper->getPlatformProduct($product);

            // Get configuration setting for apply_discount_to_catalog_price
            $applyDiscountToCatalogPrice = Mage::getStoreConfig('autoship_subscription/discount/apply_discount_to_catalog_price', $store);

            // Get item price for comparison with product price
            $itemPrice = $this->_getComparableItemPrice($quoteItem);
            // Calculate subscription discount for item
            if($platformProduct->getData('is_discount_percentage')) {
                // Percent discount
                // Call _getDiscountableItemPrice so we factor in tax if necessary
                $subscriptionDiscount = $platformProduct->getData('discount') * $this->_getDiscountableItemPrice($quoteItem) * $quoteItem->getQty();
            }
            else {
                // Fixed price discount
                $subscriptionDiscount = $platformProduct->getData('discount') * $quoteItem->getQty();
            }

            $productPrice = $product->getPrice();
            // Check to see if product price is discounted (by catalog price rules or special price)
            $catalogDiscountApplied = ($itemPrice < $productPrice);
            // Get already applied discount
            $alreadyAppliedDiscount = $quoteItem->getDiscountAmount();

            // Apply our subscription discount if all our biz logic is satisfied
            // Flag to check if any biz logic matched
            $applyOnlySubscriptionDiscount = false;
            $combineSubscriptionDiscount = false;
            // Check if catalog price already discounted and check config setting
            if(!$catalogDiscountApplied || $applyDiscountToCatalogPrice) {
                // Check the cart rule combine type config setting
                switch(Mage::getStoreConfig('autoship_subscription/discount/cartrule_combine_type', $store)) {
                    case SFC_Autoship_Model_System_Config_Source_Cartrulediscountcombinetype::TYPE_APPLY_GREATEST:
                        // Check which is greater
                        if($subscriptionDiscount >= $alreadyAppliedDiscount) {
                            $applyOnlySubscriptionDiscount = true;
                        }
                        break;
                    case SFC_Autoship_Model_System_Config_Source_Cartrulediscountcombinetype::TYPE_APPLY_LEAST:
                        // Check which is less
                        if($subscriptionDiscount <= $alreadyAppliedDiscount) {
                            $applyOnlySubscriptionDiscount = true;
                        }
                        break;
                    case SFC_Autoship_Model_System_Config_Source_Cartrulediscountcombinetype::TYPE_APPLY_CART_DISCOUNT:
                        // Check if any cart rules were applied
                        // If any cart rules applied, go with cart rules, otherwise go with subscription discount
                        if($alreadyAppliedDiscount == 0) {
                            // Apply sub discount only if no other rules matched
                            $applyOnlySubscriptionDiscount = true;
                        }
                        break;
                    case SFC_Autoship_Model_System_Config_Source_Cartrulediscountcombinetype::TYPE_APPLY_SUBSCRIPTION:
                        $applyOnlySubscriptionDiscount = true;
                        break;
                    case SFC_Autoship_Model_System_Config_Source_Cartrulediscountcombinetype::TYPE_COMBINE_SUBSCRIPTION:
                        $combineSubscriptionDiscount = true;
                        break;
                }
            }

            // Apply only subscription discount
            if ($applyOnlySubscriptionDiscount) {
                // Now lets apply subscription discount
                $quoteItem->setDiscountAmount($subscriptionDiscount);
                $quoteItem->setBaseDiscountAmount($subscriptionDiscount);

                // If we are applying subscription discount, wipe out "appliedRuleIds" and descriptions added by shopping cart price rules
                $quoteItem->setAppliedRuleIds($appliedRuleIds['quoteItem']);
                $address->setAppliedRuleIds($appliedRuleIds['address']);
                $quote->setAppliedRuleIds($appliedRuleIds['quote']);

                // Since we applied subscription discount, wipe out any discount descriptions from shopping cart price rules and add in our own
                $discountDescriptionArray['subscription'] = Mage::helper('autoship')->__('Subscription');
                $address->setDiscountDescriptionArray($discountDescriptionArray);
            }
            // Combine subscription discount with already applied discounts
            else if ($combineSubscriptionDiscount) {
                $newDiscountAmount = $quoteItem->getDiscountAmount() + $subscriptionDiscount;
                $newBaseDiscountAmount = $quoteItem->getBaseDiscountAmount() + $subscriptionDiscount;
                $quoteItem->setDiscountAmount($newDiscountAmount);
                $quoteItem->setBaseDiscountAmount($newBaseDiscountAmount);

                // Since we applied subscription discount, add in our discount description
                $discountDescriptionArray = $address->getDiscountDescriptionArray();
                $discountDescriptionArray['subscription'] = Mage::helper('autoship')->__('Subscription');
                $address->setDiscountDescriptionArray($discountDescriptionArray);
            }
        }

        return $this;
    }

    /**
     * Determine if an item is part of a subscription purchase
     * @param Mage_Sales_Model_Quote_Item_Abstract $baseItem
     * @param $store
     * @return bool
     */
    protected function _isItemPartOfSubscription(Mage_Sales_Model_Quote_Item_Abstract $baseItem, $store)
    {
        if ($baseItem->getParentItem() && $baseItem->getParentItem()->isChildrenCalculated()) {
            $quoteItem = $baseItem->getParentItem();
        } else {
            $quoteItem = $baseItem;
        }
        return ($quoteItem->getData('item_fulfils_subscription')
            ||
            (
                $quoteItem->getData('create_new_subscription_at_checkout')
                &&
                (Mage::getStoreConfig('autoship_subscription/subscription/use_new_subscription_page', $store) != '1')
            )
        );
    }

    /**
     * Return the total item price on which the discount can be applied.
     * If the config setting allows for it, take the item price including tax, versus w/o tax
     * @param Mage_Sales_Model_Quote_Item_Abstract $quoteItem
     * @return float
     */
    protected function _getDiscountableItemPrice(Mage_Sales_Model_Quote_Item_Abstract $quoteItem)
    {
        if (Mage::helper("tax")->priceIncludesTax() && Mage::helper("tax")->discountTax()) {
            /**
             * If the settings are such that item prices include tax,
             * magento doesn't factor tax into _getItemPrice.
             *
             * However, if item prices DON'T include tax but Apply Discounts to Taxes is set to yes,
             * _getItemPrice appears to return the value with tax
             */
            return Mage::app()->getStore()->convertPrice($quoteItem->getPriceInclTax());
        }
        return $this->_getItemPrice($quoteItem);
    }

    /**
     * Get the item price for comparison with the product
     * @param Mage_Sales_Model_Quote_Item_Abstract $quoteItem
     * @return float
     */
    protected function _getComparableItemPrice(Mage_Sales_Model_Quote_Item_Abstract $quoteItem)
    {
        if (Mage::helper("tax")->priceIncludesTax()) {
            /**
             * If Magento is set such that product prices include tax,
             * _getItemPrice returns the item *without* tax
             */
            return Mage::app()->getStore()->convertPrice($quoteItem->getPriceInclTax());
        } elseif (Mage::helper("tax")->discountTax()) {
            /**
             * If product price doesn't  include tax but we've set Magento to apply discounts to tax,
             * the result of _getItemPrice actually *includes* the tax - but for comparison to the product we remove it
             */
            return Mage::app()->getStore()->convertPrice($quoteItem->getCalculationPrice());
        }
        return $this->_getItemPrice($quoteItem);
    }

}
