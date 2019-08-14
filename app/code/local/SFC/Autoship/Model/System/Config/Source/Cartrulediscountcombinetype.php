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

class SFC_Autoship_Model_System_Config_Source_Cartrulediscountcombinetype
{

    const TYPE_APPLY_GREATEST = 0;
    const TYPE_APPLY_LEAST = 1;
    const TYPE_APPLY_CART_DISCOUNT = 2;
    const TYPE_APPLY_SUBSCRIPTION = 3;
    const TYPE_COMBINE_SUBSCRIPTION = 4;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::TYPE_COMBINE_SUBSCRIPTION, 'label'=>Mage::helper('adminhtml')->__('Combine Subscription Discount With Other Discounts')),
            array('value' => self::TYPE_APPLY_GREATEST, 'label'=>Mage::helper('adminhtml')->__('Apply Greatest Discount')),
            array('value' => self::TYPE_APPLY_LEAST, 'label'=>Mage::helper('adminhtml')->__('Apply Least Discount')),
            array('value' => self::TYPE_APPLY_CART_DISCOUNT, 'label'=>Mage::helper('adminhtml')->__('Always Apply Cart Rule Discount')),
            array('value' => self::TYPE_APPLY_SUBSCRIPTION, 'label'=>Mage::helper('adminhtml')->__('Always Apply Subscription Discount')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $valueArray = array();
        foreach($this->toOptionArray() as $curElement) {
            $valueArray[$curElement['value']] = $curElement['label'];
        }
        return $valueArray;
    }

}
