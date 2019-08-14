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
 * Special code to make us compatible with Fooman_AdvancedPromotions module
 */
if (file_exists(realpath(dirname(__FILE__)) . '/../../../../community/Fooman/AdvancedPromotions/Model/SalesRule/Rule/Condition/Product.php') &&
    class_exists('Fooman_AdvancedPromotions_Model_SalesRule_Rule_Condition_Product'))
{
    class SFC_Autoship_Model_SalesRule_Rule_Condition_Product_Base extends Fooman_AdvancedPromotions_Model_SalesRule_Rule_Condition_Product
    {
    }
}
else
{
    class SFC_Autoship_Model_SalesRule_Rule_Condition_Product_Base extends Mage_SalesRule_Model_Rule_Condition_Product
    {
    }
}

/**
 * Product rule condition data model
 *
 */
class SFC_Autoship_Model_SalesRule_Rule_Condition_Product extends SFC_Autoship_Model_SalesRule_Rule_Condition_Product_Base
{

    // Subscription status constants
    const SUBSCRIPTION_STATUS_ANY       = 1;
    const SUBSCRIPTION_STATUS_NEW       = 2;
    const SUBSCRIPTION_STATUS_REORDER   = 3;

    /**
     * Add special attributes
     *
     * @param array $attributes
     */
    protected function _addSpecialAttributes(array &$attributes)
    {
        parent::_addSpecialAttributes($attributes);
        $attributes['quote_item_part_of_subscription'] = Mage::helper('salesrule')->__('Subscription Status');
    }

    /**
     * Validate Product Rule Condition
     *
     * @param Varien_Object $object
     *
     * @return bool
     */
    public function validate(Varien_Object $object)
    {
        if ($this->getAttribute() == 'quote_item_part_of_subscription') {
            // Check quote item attributes
            $itemFulfilsSubscription = $object->getData('item_fulfils_subscription');
            $itemCreatesNewSubscription = $object->getData('create_new_subscription_at_checkout');
            // Get value set on rule condition
            $conditionValue = $this->getValueParsed();
            // Get operator set on rule condition
            $op = $this->getOperatorForValidate();
            // Handle different status types
            switch ($conditionValue) {
                case self::SUBSCRIPTION_STATUS_ANY:
                    $matchResult = (bool) ($itemFulfilsSubscription || $itemCreatesNewSubscription);
                    break;
                case self::SUBSCRIPTION_STATUS_NEW:
                    $matchResult = (bool) $itemCreatesNewSubscription;
                    break;
                case self::SUBSCRIPTION_STATUS_REORDER:
                    $matchResult = (bool) ($itemFulfilsSubscription && !$itemCreatesNewSubscription);
                    break;
                default:
                    $matchResult = false;
                    break;
            }
            // Only == or != operators supported
            // In case of !=, do invert $matchResult
            if($op != '==') {
                $matchResult = !$matchResult;
            }

            // Return our result
            return $matchResult;
        }
        else {
            return parent::validate($object);
        }
    }

    /**
     * Retrieve input type
     *
     * @return string
     */
    public function getInputType()
    {
        if ($this->getAttribute()=='quote_item_part_of_subscription') {
            return 'select';
        }
        else {
            return parent::getInputType();
        }
    }

    /**
     * Retrieve value element type
     *
     * @return string
     */
    public function getValueElementType()
    {
        if ($this->getAttribute()=='quote_item_part_of_subscription') {
            return 'select';
        }
        else {
            return parent::getValueElementType();
        }
    }

    public function getValueSelectOptions()
    {
        if ($this->getAttribute()=='quote_item_part_of_subscription') {
            return array(
                array('value' => self::SUBSCRIPTION_STATUS_ANY, 'label' => Mage::helper('autoship')->__('Part of Subscription (New or Re-order)')),
                array('value' => self::SUBSCRIPTION_STATUS_NEW, 'label' => Mage::helper('autoship')->__('Part of New Subscription Order')),
                array('value' => self::SUBSCRIPTION_STATUS_REORDER, 'label' => Mage::helper('autoship')->__('Part of Subscription Re-order')),
            );
        }
        else {
            return parent::getValueSelectOptions();
        }
    }

}
