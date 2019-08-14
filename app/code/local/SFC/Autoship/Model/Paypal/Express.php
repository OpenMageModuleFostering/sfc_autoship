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

class SFC_Autoship_Model_Paypal_Express extends Mage_Paypal_Model_Express
{

    /**
     * Check whether payment method can be used
     * @param Mage_Sales_Model_Quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        // Check config to see if extension functionality is enabled
        if ($quote != null) {
            $store = $quote->getStore();
        }
        else {
            $store = null;
        }
        if (Mage::getStoreConfig('autoship_general/general/enabled', $store) != '1') {
            return parent::isAvailable($quote);
        }

        // If quote present and subscription products in quote, don't allow paypal
        if ($quote instanceof Mage_Sales_Model_Quote) {
            // Get quote helper
            /** @var SFC_Autoship_Helper_Quote $quoteHelper */
            $quoteHelper = Mage::helper('autoship/quote');
            // Check if quote has subscription products, if yes disallow paypal express
            if ($quoteHelper->hasProductsToCreateNewSubscription($quote)) {
                return false;
            }
        }

        return parent::isAvailable($quote);
    }

}
