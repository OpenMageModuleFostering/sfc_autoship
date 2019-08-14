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

class SFC_Autoship_Block_Payment_Form_Cc_Saved extends SFC_Autoship_Block_Payment_Form_Cc
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('autoship/payment/form/cc_saved.phtml');
    }

    /**
     * @return SFC_Autoship_Model_Payment_Profile
     */
    public function getSavedPaymentProfile()
    {
        /** @var SFC_Autoship_Model_Payment_Method $method */
        $method = $this->getMethod();
        $paymentProfile = $method->getSavedPaymentProfile();

        return $paymentProfile;
    }

    public function getObscuredCardNumber()
    {
        $paymentProfile = $this->getSavedPaymentProfile();

        return $paymentProfile->getData('creditcard_first_digits') . 'XXXXXX' . $paymentProfile->getData('creditcard_last_digits');
    }

    public function getMagentoCardType()
    {
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        // Get payment profile
        $paymentProfile = $this->getSavedPaymentProfile();

        return $vaultHelper->mapSubscribeProCardTypeToMagento($paymentProfile->getData('creditcard_type'), false);
    }

}
