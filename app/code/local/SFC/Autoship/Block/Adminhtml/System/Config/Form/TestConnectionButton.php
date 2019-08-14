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

class SFC_Autoship_Block_Adminhtml_System_Config_Form_TestConnectionButton extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /*
    * Set template
    */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('autoship/system/config/test_connection_button.phtml');
    }

    /**
     * Return element html
     *
     * @param  Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }

    /**
     * Return ajax url for button
     *
     * @return string
     */
    public function getAjaxCheckUrl()
    {
        return Mage::helper('adminhtml')->getUrl(
            'autoship_admin/adminhtml_testconnectionbutton/test',
            array(
                'website' => $this->getRequest()->getParam('website'),
            ));
    }

    /**
     * Generate button html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        // Set button details
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'id' => 'sfc_autoship_test_connection_button',
                'label' => $this->helper('adminhtml')->__('Test Connection'),
                'onclick' => 'javascript:testConnection(); return false;'
            ));
        // Get button html
        $buttonHtml = $button->toHtml();
        // Append our extra span
        $finalHtml = $buttonHtml . '<span id="test_connection_result"></span>';
        return $finalHtml;
    }
}
