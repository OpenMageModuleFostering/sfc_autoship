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

class SFC_Autoship_Block_Mysubscriptions extends Mage_Core_Block_Template
{
    private $_activeSubscriptions = null;
    private $_inactiveSubscriptions = null;

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
    }

    public function getActiveSubscriptions()
    {
        if ($this->_activeSubscriptions == null) {
            // Get current customer from session
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            // Query for subscriptions
            $allSubscriptions = Mage::helper('autoship/platform')->getSubscriptions(
                $customer,
                array()
            );
            // Iterate and filter out Active subs, building inactive subs array
            $this->_activeSubscriptions = array();
            foreach ($allSubscriptions as $subscription) {
                if ($subscription->getStatus() == 'Active' || $subscription->getStatus() == 'Failed' || $subscription->getStatus() == 'Retry') {
                    $this->_activeSubscriptions[] = $subscription;
                }
            }

            // Sort subscriptions
            usort($this->_activeSubscriptions, function($a, $b) {
                // 1st compare by next order date reversed
                $dateResult = (0 - strcmp($a->getData('next_order_date'), $b->getData('next_order_date')));
                if($dateResult != 0) {
                    return $dateResult;
                }
                else {
                    // Next, compare by shipping address
                    return strcmp($a->getData('shipping_address_id'), $b->getData('shipping_address_id'));
                }
            });

        }

        return $this->_activeSubscriptions;
    }

    public function getInactiveSubscriptions()
    {
        if ($this->_inactiveSubscriptions == null) {
            // Get current customer from session
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            // Query for subscriptions
            $allSubscriptions = Mage::helper('autoship/platform')->getSubscriptions(
                $customer,
                array()
            );
            // Iterate and filter out Active subs, building inactive subs array
            $this->_inactiveSubscriptions = array();
            foreach ($allSubscriptions as $subscription) {
                if ($subscription->getStatus() != 'Active' && $subscription->getStatus() != 'Failed' && $subscription->getStatus() != 'Retry') {
                    $this->_inactiveSubscriptions[] = $subscription;
                }
            }

            // Sort subscriptions
            usort($this->_inactiveSubscriptions, function($a, $b) {
                // 1st compare by next order date reversed
                $dateResult = (0 - strcmp($a->getData('next_order_date'), $b->getData('next_order_date')));
                if($dateResult != 0) {
                    return $dateResult;
                }
                else {
                    // Next, compare by shipping address
                    return strcmp($a->getData('shipping_address_id'), $b->getData('shipping_address_id'));
                }
            });

        }

        return $this->_inactiveSubscriptions;
    }

    public function getActiveSubscriptionsCount()
    {
        return count($this->getActiveSubscriptions());
    }

    public function getIn_activeSubscriptionsCount()
    {
        return count($this->getInactiveSubscriptions());
    }

    public function getNextOrderDateMode()
    {
        $nextOrderDateMode = Mage::getStoreConfig('autoship_subscription/options/next_order_date_mode');

        return $nextOrderDateMode;
    }

}
