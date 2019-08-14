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

class SFC_Autoship_Block_Payment_Form_Cc extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('autoship/payment/form/cc.phtml');
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
        return Mage::getModel('autoship/system_config_source_cctype')->getCcAvailableTypes();
    }

    /**
     * Retrieve available credit card types in Subscribe Pro format
     *
     * @return array
     */
    public function getCcAvailableTypesSubscribeProFormat()
    {
        return Mage::getModel('autoship/system_config_source_cctype')->getCcAvailableTypesSubscribeProFormat();
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

    /**
     * Retrieve additional information field value data from payment info object
     *
     * @param   string $field
     * @return  mixed
     */
    public function getAdditionalInfo($field)
    {
        return $this->htmlEscape($this->getMethod()->getInfoInstance()->getAdditionalInformation($field));
    }

    /**
     * Retrive has verification configuration
     *
     * @return boolean
     */
    public function hasVerification()
    {
        if ($this->getMethod()) {
            $configData = $this->getMethod()->getConfigData('useccv');
            if (is_null($configData)) {
                return true;
            }

            return (bool)$configData;
        }

        return true;
    }

    public function useSaveCardCheckbox()
    {
        $checkoutSaveCardCheckbox = (bool) $this->getMethod()->getConfigData('checkout_save_card_checkbox');
        $isGuestCheckout = ($this->getQuote()->getCheckoutMethod() == Mage_Sales_Model_Quote::CHECKOUT_METHOD_GUEST);

        return $checkoutSaveCardCheckbox && !$isGuestCheckout && !$this->forceSaveCard();
    }

    public function forceSaveCard()
    {
        /** @var SFC_Autoship_Helper_Quote $quoteHelper */
        $quoteHelper = Mage::helper('autoship/quote');
        // Check if quote has any subscriptions in it
        if($quoteHelper->hasProductsToCreateNewSubscription()) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        Mage::dispatchEvent('payment_form_block_to_html_before', array(
            'block' => $this
        ));

        return parent::_toHtml();
    }

    /**
     * Retrieve checkout session model
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        if (empty($this->_checkout)) {
            $this->_checkout = Mage::getSingleton('checkout/session');
        }
        return $this->_checkout;
    }

    /**
     * Retrieve sales quote model
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        if (empty($this->_quote)) {
            $this->_quote = $this->getCheckout()->getQuote();
        }
        return $this->_quote;
    }

    public function getBillingAddress()
    {
        return $this->getQuote()->getBillingAddress();
    }

}
