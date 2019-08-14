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

class SFC_Autoship_Model_Payment_Profile extends Mage_Core_Model_Abstract
{
    const PAYMENT_TYPE_THIRDPARTY = 'third_party_token';

    public function getCollection()
    {
        return Mage::getModel('autoship/payment_profile_collection');
    }

    public function getId()
    {
        return $this->getData('id');
    }

    /**
     * Init profile with customer data from customer record
     */
    public function initProfileWithCustomerDefault($customer)
    {
        // Load customer
        if (!$customer instanceof Mage_Customer_Model_Customer) {
            /** @var Mage_Customer_Model_Customer $model */
            $customer = Mage::getModel('customer/customer')->load($customer);
        }
        $this->setData('customer_email', $customer->getData('email'));
        $this->setData('magento_customer_id', $customer->getId());
        $this->setData('billing_first_name', $customer->getData('firstname'));
        $this->setData('billing_last_name', $customer->getData('lastname'));
        // Grab default billing addy id
        $addressId = $customer->getData('default_billing');
        // Add address data if default billing addy exists
        if ($addressId) {
            // Get address
            $address = Mage::getModel('customer/address')->load($addressId);
            // Create billing address from customer address
            $billingAddress = Mage::getModel('autoship/payment_profile_address')
                ->updateFromCustomerAddress($address);
            $this->setData('billing_address', $billingAddress);
        }
        else {
            $billingAddress = Mage::getModel('autoship/payment_profile_address');
            $this->setData('billing_address', $billingAddress);
        }

        $this->mapFlatFields();
    }

    public function updatePaymentProfileFromVaultData(array $vaultPaymentProfile)
    {
        $vaultPaymentProfileMinusBillingAddress = $vaultPaymentProfile;
        unset($vaultPaymentProfileMinusBillingAddress['billing_address']);
        $this->addData($vaultPaymentProfileMinusBillingAddress);
        $this->getData('billing_address')->addData($vaultPaymentProfile['billing_address']);
        // Map extra fields
        $this->mapFlatFields();
    }

    public static function makePaymentProfileFromVaultData(array $vaultPaymentProfile)
    {
        // New object
        $paymentProfile = Mage::getModel('autoship/payment_profile');
        $billingAddress = Mage::getModel('autoship/payment_profile_address');
        $paymentProfile->setData('billing_address', $billingAddress);
        $paymentProfile->updatePaymentProfileFromVaultData($vaultPaymentProfile);

        return $paymentProfile;
    }

    public function mapFlatFields()
    {
        $billingAddress = $this->getData('billing_address');
        foreach ($billingAddress->getData() as $billingField => $billingValue) {
            $this->setData('billing_' . $billingField, $billingValue);
        }

        $this->setData('billing_name', $this->getData('billing_first_name') . ' ' . $this->getData('billing_last_name'));

        $this->setData('creditcard_number', $this->getData('creditcard_first_digits') . 'XXXXXX' . $this->getData('creditcard_last_digits'));

        $this->setData('creditcard_exp_date', sprintf("%02d", $this->getData('creditcard_month')) . ' / ' . $this->getData('creditcard_year'));
    }

    public function unmapFlatFields()
    {
        // Un-map billing address flat fields
        $billingAddress = $this->getData('billing_address');
        foreach ($this->getData() as $field => $value) {
            if (strpos($field, 'billing_') === 0 && $field != 'billing_address') {
                $billingAddress->setData(substr($field, strlen('billing_')), $value);
            }
        }
    }

    /**
     * @return bool
     */
    public function isThirdParty()
    {
        return $this->getData('payment_method_type') == self::PAYMENT_TYPE_THIRDPARTY;
    }

}
