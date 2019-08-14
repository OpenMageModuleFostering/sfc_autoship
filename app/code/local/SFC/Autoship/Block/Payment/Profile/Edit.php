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

class SFC_Autoship_Block_Payment_Profile_Edit extends Mage_Adminhtml_Block_Template
{

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
    }

    public function getEnvironmentKey()
    {
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
        // Lookup payment method code based on SP config
        $accountConfig = $platformHelper->getAccountConfig();
        // Get env key
        $environmentKey = $accountConfig['transparent_redirect_environment_key'];

        return $environmentKey;
    }

    public function getIsNewProfile()
    {
        return !(strlen($this->getCimProfile()->getId()));
    }

    public function getTitle()
    {
        if ($this->getIsNewProfile()) {
            // Creating a new profile
            return ' Enter New Credit Card ';
        }
        else {
            // Editing an existing profile
            return ' Edit Saved Credit Card XXXX' . $this->getData('cim_profile')->getData('creditcard_last_digits');
        }
    }

    public function getRedirectUrl()
    {
        return $this->getUrl('subscriptions/mycreditcards/redirect/', array('_secure' => true));
    }

    public function getRedirectPostUrl()
    {
        return 'https://core.spreedly.com/v1/payment_methods';
    }

    public function getBackUrl()
    {
        return $this->getUrl('subscriptions/mycreditcards/', array('_secure' => true));
    }

    public function getSaveUrl()
    {
        return $this->getUrl('subscriptions/mycreditcards/save/', array('_secure' => true));
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
     * Retrieve credit card expire months
     *
     * @return array
     */
    public function getCcMonths()
    {
        $months = $this->getData('cc_months');
        $output = array();
        if (is_null($months)) {
            $months[0] = $this->__('Month');
            $months = array_merge($months, $this->_getConfig()->getMonths());
            foreach ($months as $k => $v) {
                if (strlen($k) == 1 && $k != 0) {
                    $value = '0' . $k;
                    $output[$value] = $v;
                }
                elseif ($v != 'Month') {
                    $output[$k] = $v;
                }
            }
            $this->setData('cc_months', $months);
        }

        return $output;
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
            $this->setData('cc_years', $years);
        }

        return $years;
    }

    public function getCcTypeInCyberSourceFormat()
    {
        $ccType = $this->getCimProfile()->getData('cc_type');
        if(strlen($ccType)) {
            return Mage::helper('sfc_cybersource/gateway')->mapMagentoCardTypeToCyberSource($ccType);
        }
        else {
            return '';
        }
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = Mage::app()->getStore();
        }
        $path = 'payment/' . SFC_Autoship_Model_Payment_Method::METHOD_CODE . '/' . $field;

        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Retrieve use verification configuration
     *
     * @return boolean
     */
    public function useVerification()
    {
        if (!$this->getIsNewProfile()) {
            return false;
        }

        $configData = $this->getConfigData('useccv');
        if (is_null($configData)) {
            return true;
        }

        return (bool)$configData;
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

    public function getLocaleLanguage()
    {
        return locale_get_primary_language(Mage::app()->getLocale()->getLocaleCode());
    }

}
