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

class SFC_Autoship_Block_Adminhtml_Customer_Paymentprofiles_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        // Call parent
        parent::__construct();
        // Make some adjustments to the form container
        $this->_objectId = 'id';
        $this->_blockGroup = 'autoship';
        $this->_controller = 'adminhtml_customer_paymentprofiles';
        // Get payment profile from registry
        /** @var SFC_Autoship_Model_Payment_Profile $model */
        $model = Mage::registry('paymentprofile_data');
        if ($model && $model->getId()) {
            if (!$model->isThirdParty()) {
                $this->_updateButton('save', 'label', Mage::helper('autoship')->__('Save Card'));
            } else {
                $this->_removeButton('save');
            }
        }
        $this->_removeButton('delete');
        $this->_removeButton('reset');
    }

    public function getHeaderText()
    {
        if (Mage::registry('paymentprofile_data') && Mage::registry('paymentprofile_data')->getId()) {
            // Editing existing card
            return Mage::helper('autoship')->__(
                "Edit Saved Credit Card %s",
                Mage::registry('paymentprofile_data')->getData('creditcard_number'));
        }
        else {
            // Creating new card
            // Load customer record
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel('customer/customer')->load(Mage::registry('customer_id'));
            // Build title
            return Mage::helper('autoship')->__('New Saved Credit Card - ' . $customer->getName());
        }
    }

    /**
     * Return URL to customer edit page
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('adminhtml/customer/edit/tab/customer_info_tabs_paymentprofile',
            array('id' => Mage::registry('customer_id')));
    }

}