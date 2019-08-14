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

class SFC_Autoship_Block_Subscription_Form_Payment extends SFC_Autoship_Block_Subscription_Abstract
{

    /**
     * Retrieve payment method code
     *
     * @return string
     * @deprecated No longer used
     */
    public function getMethodCode()
    {
        // Get helpers
        /** @var SFC_Autoship_Helper_Platform $helperPlatform */
        $helperPlatform = Mage::helper('autoship/platform');
        // Lookup payment method from platform
        $methodCode = $helperPlatform->getConfiguredPaymentMethodCode();

        return $methodCode;
    }

    /**
     * Retrieve payment configuration object
     *
     * @return Mage_Payment_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }

    /**
     * Retrieve available credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code => $name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }

        return $types;
    }

    /**
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        if (is_null($months)) {
            $months[0] = $this->__('Month');
            $months = array_merge($months, $this->_getConfig()->getMonths());
            $this->setData('cc_months', $months);
        }

        return $months;
    }

    /**
     * Retrieve credit card expire years
     *
     * @return array
     */
    public function getCcYears()
    {
        $years = $this->getData('cc_years');
        if (is_null($years)) {
            $years = $this->_getConfig()->getYears();
            $years = array(0 => $this->__('Year')) + $years;
            $this->setData('cc_years', $years);
        }

        return $years;
    }

    public function getCurrentCard()
    {
        $paymentProfile = $this->getSubscription()->getData('payment_profile');
        $card = array(
            'payment_token' => $paymentProfile['payment_token'],
            'creditcard_last_digits' => $paymentProfile['creditcard_last_digits'],
        );

        return $card;
    }

    public function useVerification()
    {
        // Get helpers
        /** @var SFC_Autoship_Helper_Platform $helperPlatform */
        $helperPlatform = Mage::helper('autoship/platform');

        // Check the pay method
        switch($helperPlatform->getConfiguredPaymentMethodCode())
        {
            default:
            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CIM_10XX:
                return false;

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CIM:
            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CYBERSOURCE:
                if ($method = $this->getMethod()) {
                    $configData = $method->getConfigData('useccv');
                    if (is_null($configData)) {
                        return true;
                    }
                    else {
                        return (bool)$configData;
                    }
                }
                else {
                    return false;
                }
        }

    }

    public function getSavedCards()
    {
        //
        // Only get cards once per request, store in registry keyed on customer id
        //
        // Grab customer object from session
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $savedCards = Mage::registry('sfc_autoship_saved_cards_' . $customer->getId());
        if ($savedCards == null) {
            $savedCards = $this->getSavedCardsImpl();
            Mage::unregister('sfc_autoship_saved_cards_' . $customer->getId());
            Mage::register('sfc_autoship_saved_cards_' . $customer->getId(), $savedCards);
        }

        return $savedCards;
    }

    protected function getSavedCardsImpl()
    {
        // Get helpers
        /** @var SFC_Autoship_Helper_Platform $helperPlatform */
        $helperPlatform = Mage::helper('autoship/platform');

        // Grab customer object from session
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        // Array to hold cards in std data structure / format
        $cards = array();
        // Check the pay method
        switch($helperPlatform->getConfiguredPaymentMethodCode())
        {
            default:
                break;

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CIM_10XX:
                // Lookup profiles for this customer
                $profileCollection = Mage::getModel('authnettoken/cim_payment_profile')->getCollection();
                $profileCollection
                    ->addFieldToFilter('customer_id', $customer->getId());
                // Translate to std data structure
                foreach($profileCollection as $curProfile) {
                    $cards[] = array(
                        'payment_token' => $curProfile->getData('cim_payment_profile_id'),
                        'creditcard_last_digits' => $curProfile->getData('customer_cardnumber'),
                        'edit_url' => ''
                    );
                }
                break;

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CIM:
                // Lookup profiles for this customer
                $profileCollection = Mage::getModel('sfc_cim_core/cim_payment_profile')->getCollection();
                $profileCollection
                    ->addFieldToFilter('customer_id', $customer->getId());
                // Translate to std data structure
                foreach($profileCollection as $curProfile) {
                    $cards[] = array(
                        'payment_token' => $curProfile->getData('cim_payment_profile_id'),
                        'creditcard_last_digits' => $curProfile->getData('customer_cardnumber'),
                        'edit_url' => ''
                    );
                }
                break;

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SUBSCRIBE_PRO_VAULT:
                /** @var SFC_Autoship_Helper_Vault $vaultHelper */
                $vaultHelper = Mage::helper('autoship/vault');
                // Lookup all profiles for customer
                $paymentProfiles = $vaultHelper->getPaymentProfilesForCustomer($customer->getData('email'));
                foreach ($paymentProfiles as $paymentProfile) {
                    $cards[] = array(
                        'payment_token' => $paymentProfile->getData('payment_token'),
                        'creditcard_last_digits' => $paymentProfile->getData('creditcard_last_digits'),
                        'creditcard_month' => $paymentProfile->getData('creditcard_month'),
                        'creditcard_year' => $paymentProfile->getData('creditcard_year'),
                        'edit_url' => Mage::getUrl('subscriptions/mycreditcards/edit') . 'id/' . $paymentProfile->getId(),
                        'is_third_party' => $paymentProfile->isThirdParty()
                    );
                }

                break;

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CYBERSOURCE:
                // Lookup profiles for this customer
                $profileCollection = Mage::getModel('sfc_cybersource/payment_profile')->getCollection();
                $profileCollection
                    ->addFieldToFilter('customer_id', $customer->getId());
                // Translate to std data structure
                foreach($profileCollection as $curProfile) {
                    $cards[] = array(
                        'payment_token' => $curProfile->getData('payment_token'),
                        'creditcard_last_digits' => $curProfile->getData('customer_cardnumber'),
                        'edit_url' => ''
                    );
                }
                break;
        }

        // Return array of cards
        return $cards;
    }

    public function getSubmitUrl()
    {
        return '';
    }

    /**
     * Retrieve payment method model
     *
     * @return Mage_Payment_Model_Method_Abstract
     */
    protected function getMethod()
    {
        // Get helpers
        /** @var SFC_Autoship_Helper_Platform $helperPlatform */
        $helperPlatform = Mage::helper('autoship/platform');
        /** @var Mage_Payment_Helper_Data $helperPayment */
        $helperPayment = Mage::helper('payment');

        // Get method from object
        $method = $this->getData('method');
        // Check if we already created method instance
        if (!($method instanceof Mage_Payment_Model_Method_Abstract)) {
            // Lookup payment method from platform
            $methodCode = $helperPlatform->getConfiguredPaymentMethodCode();
            // Get method instance from code
            $method = $helperPayment->getMethodInstance($methodCode);
            // Save instance on this block
            $this->setData('method', $method);
        }

        // Return method instance
        return $method;
    }

}

