<?php
/**
 * StoreFront CyberSource Tokenized Payment Extension for Magento
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to commercial source code license of StoreFront Consulting, Inc.
 *
 * @category  SFC
 * @package   SFC_CyberSource
 * @author    Garth Brantley <garth@storefrontconsulting.com>
 * @copyright 2009-2013 StoreFront Consulting, Inc. All Rights Reserved.
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.storefrontconsulting.com/cybersource-saved-credit-cards-extension-for-magento/
 *
 */

/**
 */
class SFC_Autoship_Model_System_Config_Source_Year
{
    /**
     * Retrieve Option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $years = $this->_getConfig()->getYears();
        $yearOptionArray = array();
        foreach ($years as $year) {
            $yearOptionArray[] = array(
                'value' => $year,
                'label' => Mage::helper('autoship')->__($year)
            );

        }

        return $yearOptionArray;
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

}

/**
 */
class SFC_CyberSource_Model_Source_Year
{
    /**
     * Retrieve Option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        $years = $this->_getConfig()->getYears();
        $yearOptionArray = array();
        foreach ($years as $year) {
            $yearOptionArray[] = array(
                'value' => $year,
                'label' => Mage::helper('autoship')->__($year)
            );

        }

        return $yearOptionArray;
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

}
