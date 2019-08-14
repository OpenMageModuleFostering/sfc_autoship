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
 * Block to display each individual subscription on My Subscriptions Page
 */
class SFC_Autoship_Block_Mysubscriptions_Subscription extends SFC_Autoship_Block_Subscription_Abstract
{

    public function createChildSubscriptionBlock($templateName)
    {
        $block = $this->getLayout()->createBlock('autoship/mysubscriptions_subscription');
        $block->setParentBlock($this);
        $block->setTemplate('autoship/mysubscriptions/subscription/' . $templateName);
        $block->setSubscription($this->getSubscription());

        return $block;
    }

    /**
     * Returns a formatted version of the shipping address for this subscription, ready for display on page
     *
     * @return string Formatted version of shipping address
     */
    public function getFormattedShippingAddress()
    {
        $address = $this->getSubscription()->getShippingAddress();
        $addressText = '';
        foreach ($address->getStreet() as $curStreetComponent) {
            $addressText .= ' ' . $curStreetComponent;
        }
        $addressText = substr($addressText, 1);

        return $addressText;
    }

    /**
     * Retrieve given media attribute label or product name if no label
     *
     * @param Mage_Catalog_Model_Product $product
     * @param string $mediaAttributeCode
     *
     * @return string
     */
    public function getImageLabel($product = null, $mediaAttributeCode = 'image')
    {
        if (is_null($product)) {
            $product = $this->getProduct();
        }

        $label = $product->getData($mediaAttributeCode . '_label');
        if (empty($label)) {
            $label = $product->getName();
        }

        return $label;
    }

    public function showBillingAddressBlock()
    {
        /** @var SFC_Autoship_Helper_Platform $helperPlatform */
        $helperPlatform = Mage::helper('autoship/platform');
        // Check if using the SP Vault
        if ($helperPlatform->getConfiguredPaymentMethodCode() == SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SUBSCRIBE_PRO_VAULT) {
            return false;
        }
        else {
            return true;
        }
    }

    public function getNewCardUrl()
    {
        /** @var SFC_Autoship_Helper_Platform $helperPlatform */
        $helperPlatform = Mage::helper('autoship/platform');
        switch($helperPlatform->getConfiguredPaymentMethodCode())
        {
            default:
                return '';

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_PARADOX_TRANSARMOR:
                return $this->getUrl('transarmor/manage/');
            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CIM_10XX:
                return $this->getUrl('creditcards/index/new');
            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CIM:
                return $this->getUrl('creditcards/index/new');
            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CYBERSOURCE:
                return $this->getUrl('creditcards/index/new');
            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SUBSCRIBE_PRO_VAULT:
                return $this->getUrl('subscriptions/mycreditcards/new/');

        }
    }

}

