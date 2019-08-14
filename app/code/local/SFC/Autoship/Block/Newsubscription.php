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

class SFC_Autoship_Block_Newsubscription extends SFC_Autoship_Block_Subscription_Abstract
{

    public function _prepareLayout()
    {
        // Set subscription on block
        $this->setSubscription(Mage::getSingleton('customer/session')->getNewSubscription());

        // Call parent
        parent::_prepareLayout();
    }

    public function getSummary()
    {
        try {
            $summary = Mage::helper('autoship/subscription')->estimateSubscriptionOrderTotals(
                Mage::getSingleton('customer/session')->getCustomer(),
                $this->getSubscription(),
                $this->getPlatformProduct(),
                $this->getProduct());

            // Return summary data
            return $summary;
        }
        catch (Exception $e) {
            Mage::log('Error calculating estimated order summary: ' . $e->getMessage());
            Mage::logException($e);

            return 'Error estimating tax and shipping!';
        }
    }

    public function getPostActionUrl()
    {
        return $this->getUrl('autoship/newsubscription/createpost');
    }

}
