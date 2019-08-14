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

class SFC_Autoship_Model_System_Config_Source_Cctype extends Mage_Payment_Model_Source_Cctype
{
    public function getAllowedTypes()
    {
        // Lookup available types in payment method config
        $availableTypes = Mage::getStoreConfig('payment/' . SFC_Autoship_Model_Payment_Method::METHOD_CODE . '/cctypes');
        // Explode available types
        $availableTypes = explode(',', $availableTypes);

        return $availableTypes;
    }

    public function getCcAvailableTypes()
    {
        // Use parent to get option array
        $optionArray = $this->toOptionArray();
        // Parse option array into simple hash
        $availableTypes = array();
        foreach ($optionArray as $option) {
            $availableTypes[$option['value']] = $option['label'];
        }

        return $availableTypes;
    }

    public function getCcAvailableTypesSubscribeProFormat()
    {
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');

        // Use parent to get option array
        $optionArray = $this->toOptionArray();
        // Parse option array into simple hash
        $availableTypes = array();
        foreach ($optionArray as $option) {
            $ccType = $vaultHelper->mapMagentoCardTypeToSubscribePro($option['value'], false);
            if (strlen($ccType)) {
                $availableTypes[$ccType] = $option['label'];
            }
        }

        return $availableTypes;
    }

    public function getCcAllTypesSubscribeProFormat()
    {
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');

        // Parse option array into simple hash
        $availableTypes = array();
        foreach (Mage::getSingleton('payment/config')->getCcTypes() as $code => $name) {
            try {
                $availableTypes[$vaultHelper->mapMagentoCardTypeToSubscribePro($code)] = $name;
            }
            catch(\Exception $e) {

            }
        }

        return $availableTypes;
    }

    public function toOptionArraySubscribeProFormat()
    {
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');

        // Use parent to get option array
        $optionArray = $this->toOptionArray();
        foreach ($optionArray as $option) {
            $ccType = $vaultHelper->mapMagentoCardTypeToSubscribePro($option['value'], false);
            if (strlen($ccType)) {
                $option['value'] = $ccType;
            }
        }

        return $optionArray;
    }
}
