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
class SFC_Autoship_Block_Adminhtml_Customer_Paymentprofiles_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare edit form
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        // Get payment profile from registry
        $model = Mage::registry('paymentprofile_data');

        // Create form
        $form = new Varien_Data_Form();

        // New Fieldset
        $fieldset = $form->addFieldset(
            'autoship_form_customer',
            array(
                'legend' => Mage::helper('autoship')->__('Customer')
            ));

        $fieldset->addField('id', 'hidden', array(
            'label' => Mage::helper('autoship')->__('Payment Profile ID'),
            'class' => '',
            'required' => false,
            'name' => 'id',
        ));

        $fieldset->addField('magento_customer_id', 'hidden', array(
            'label' => Mage::helper('autoship')->__('Magento Customer ID'),
            'class' => '',
            'required' => false,
            'name' => 'magento_customer_id',
        ));

        $fieldset->addField('customer_email', 'hidden', array(
            'label' => Mage::helper('autoship')->__('Customer Email'),
            'class' => '',
            'required' => false,
            'name' => 'customer_email',
        ));

        $fieldset->addField('payment_token', 'hidden', array(
            'label' => Mage::helper('autoship')->__('Payment Token'),
            'class' => '',
            'required' => false,
            'name' => 'payment_token',
        ));

        $fieldset->addField('customer_id_readonly', 'label', array(
            'label' => Mage::helper('autoship')->__('Customer ID'),
            'class' => '',
            'required' => false,
            'readonly' => true,
            'name' => 'customer_id_readonly',
        ));

        $fieldset->addField('email_readonly', 'label', array(
            'label' => Mage::helper('autoship')->__('Email'),
            'class' => '',
            'required' => false,
            'readonly' => true,
            'name' => 'email_readonly',
        ));

        // New Fieldset
        $fieldset = $form->addFieldset(
            'autoship_form_creditcard',
            array(
                'legend' => Mage::helper('autoship')->__('Card Details')
            ));

        if (strlen($model->getId())) {
            $fieldset->addField('creditcard_number', 'text', array(
                'label' => Mage::helper('autoship')->__('Credit Card'),
                'class' => 'required-entry',
                'readonly' => 'readonly',
                'disabled' => true,
                'required' => true,
                'name' => 'creditcard_number',
            ));
        }
        else {
            $fieldset->addField('creditcard_number', 'text', array(
                'label' => Mage::helper('autoship')->__('Credit Card'),
                'class' => 'required-entry',
                'required' => true,
                'name' => 'creditcard_number',
            ));
        }


        $fieldset->addField('creditcard_month', 'select', array(
            'label' => Mage::helper('autoship')->__('Expiration Month'),
            'class' => '',
            'required' => false,
            'values' => Mage::getModel('autoship/system_config_source_month')->toOptionArray(),
            'name' => 'creditcard_month',
        ));

        $fieldset->addField('creditcard_year', 'select', array(
            'label' => Mage::helper('autoship')->__('Expiration Year'),
            'class' => '',
            'required' => false,
            'values' => Mage::getModel('autoship/system_config_source_year')->toOptionArray(),
            'name' => 'creditcard_year',
        ));

        // New Fieldset
        $fieldset = $form->addFieldset(
            'autoship_form_billing',
            array(
                'legend' => Mage::helper('autoship')->__('Billing Address')
            ));

        // customer first name
        $fieldset->addField('billing_first_name', 'text', array(
            'label' => Mage::helper('autoship')->__('First Name'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'billing_first_name',
        ));

        // customer last name
        $fieldset->addField('billing_last_name', 'text', array(
            'label' => Mage::helper('autoship')->__('Last Name'),
            'class' => 'required-entry',
            'required' => true,
            'name' => 'billing_last_name',
        ));

        // street1
        $fieldset->addField('billing_street1', 'text', array(
            'label' => Mage::helper('autoship')->__('Street Address 1'),
            'class' => '',
            'required' => false,
            'name' => 'billing_street1',
        ));

        // street2
        $fieldset->addField('billing_street2', 'text', array(
            'label' => Mage::helper('autoship')->__('Street Address 2'),
            'class' => '',
            'required' => false,
            'name' => 'billing_street2',
        ));

        // city
        $fieldset->addField('billing_city', 'text', array(
            'label' => Mage::helper('autoship')->__('City'),
            'class' => '',
            'required' => false,
            'name' => 'billing_city',
        ));

        // region
        $fieldset->addField('billing_region', 'text', array(
            'label' => Mage::helper('autoship')->__('State'),
            'class' => '',
            'required' => false,
            'name' => 'billing_region',
        ));

        // postcode
        $fieldset->addField('billing_postcode', 'text', array(
            'label' => Mage::helper('autoship')->__('Zip/Postal Code'),
            'class' => '',
            'required' => false,
            'name' => 'billing_postcode',
        ));

        // country_id
        $fieldset->addField('billing_country', 'text', array(
            'label' => Mage::helper('autoship')->__('Country'),
            'class' => '',
            'required' => false,
            'name' => 'billing_country',
        ));

        // phone number
        $fieldset->addField('billing_phone', 'text', array(
            'label' => Mage::helper('autoship')->__('Phone Number'),
            'class' => '',
            'required' => false,
            'name' => 'billing_phone',
        ));

        // Tweak model with extra fields
        $model->setData('customer_id_readonly', $model->getData('magento_customer_id'));
        $model->setData('email_readonly', $model->getData('customer_email'));
        $model->setData('payment_token_readonly', $model->getData('payment_token'));
        // Set values for form
        $form->setValues($model->getData());
        // Set form on this widget
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
