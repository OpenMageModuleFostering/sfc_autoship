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
 * Subscription Model Class
 *
 * This model is not persisted to the Magento DB, but instead is persisted to the platform API
 *
 */
class SFC_Autoship_Model_Subscription extends Mage_Core_Model_Abstract
{
    /**
     * Constants
     */
    // Subscription statuses
    const STATUS_ACTIVE = 'Active';
    const STATUS_CANCELLED = 'Cancelled';
    const STATUS_PAUSED = 'Paused';
    const STATUS_FAILED = 'Failed';

    // Members
    private $_platformProduct = null;
    private $_paymentProfile = null;

    protected function _construct()
    {
        parent::_construct();
        // Set default
        $this->setData('send_customer_notification', true);
    }

    public function getCustomer()
    {
        $customer = Mage::getModel('customer/customer')->load($this->getCustomerId());

        return $customer;
    }

    public function getBillingAddress()
    {
        $address = Mage::getModel('customer/address')->load($this->getBillingAddressId());

        return $address;
    }

    public function getShippingAddress()
    {
        $address = Mage::getModel('customer/address')->load($this->getShippingAddressId());

        return $address;
    }

    public function getPaymentProfile()
    {
        if($this->_paymentProfile == null || $this->_paymentProfile->getData('cim_payment_profile_id') != $this->getData('cim_payment_profile_id')) {
            $this->_paymentProfile = Mage::getModel('authnettoken/cim_payment_profile')->getCollection()
                    ->addFieldToFilter('cim_payment_profile_id', $this->getData('cim_payment_profile_id'))
                    ->getFirstItem();
        }

        return $this->_paymentProfile;
    }

    public function getProduct()
    {
        return Mage::getModel('catalog/product')->load($this->getData('product_id'));
    }

    public function getPlatformProduct()
    {
        if($this->_platformProduct == null || $this->_platformProduct->getData('product_id') != $this->getData('product_id')) {
            $this->_platformProduct = Mage::helper('autoship/platform')->getPlatformProduct($this->getProduct());
        }

        return $this->_platformProduct;
    }

    public function getIntervalText()
    {
        return $this->getInterval();
    }

    public function getQtyText()
    {
        if (substr($this->getQty(), -5) === '.0000') {
            return substr($this->getQty(), 0, -5);
        }
        else {
            return $this->getQty();
        }
    }

    public function getNextOrderDateText()
    {
        $date = date_create_from_format('Y-m-d', $this->getNextOrderDate());

        return date_format($date, 'm/d/y');
    }

}
