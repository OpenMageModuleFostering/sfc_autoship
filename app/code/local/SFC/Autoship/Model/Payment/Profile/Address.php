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

class SFC_Autoship_Model_Payment_Profile_Address extends Mage_Core_Model_Abstract
{

    /**
     * Construct
     */
    protected function _construct()
    {
    }

    public function getId()
    {
        return $this->getData('id');
    }

    /**
     * Set billing address fields on payment profile from a Magento customer address
     *
     * @param Mage_Customer_Model_Address_Abstract $billingAddress
     * @return SFC_Autoship_Model_Payment_Profile_Address
     */
    public function updateFromCustomerAddress(Mage_Customer_Model_Address_Abstract $billingAddress)
    {
        $this->setData('first_name', $billingAddress->getData('firstname'));
        $this->setData('last_name', $billingAddress->getData('lastname'));
        $this->setData('company', $billingAddress->getData('company'));
        $this->setData('street1', (string) $billingAddress->getStreet(1));
        if(strlen($billingAddress->getStreet(2)))
        $this->setData('street2', (string) $billingAddress->getStreet(2));
        $this->setData('city', $billingAddress->getData('city'));
        $this->setData('region', $billingAddress->getRegionCode());
        $this->setData('postcode', $billingAddress->getData('postcode'));
        $this->setData('country', $billingAddress->getData('country_id'));
        $this->setData('phone', $billingAddress->getData('telephone'));

        return $this;
    }

}
