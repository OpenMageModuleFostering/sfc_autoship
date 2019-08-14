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
 * Product page Subscribe block
 */
class SFC_Autoship_Block_Product_Subscribe extends SFC_Autoship_Block_Product_View
{

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
     * Return the discount text for display on product page
     *
     * @return string Discount text for product page
     */
    public function getDiscountText()
    {
        return Mage::helper('autoship/subscription')->getSubscriptionPriceText($this->getPlatformProduct(), $this->getProduct(), $this->getProductDefaultQty($this->getProduct()));
    }

}
