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

class SFC_Autoship_Block_Adminhtml_Customer_Paymentprofiles_Paymentprofile
    extends Mage_Adminhtml_Block_Widget_Grid
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    /**
     * Construct
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('paymentprofile');
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setFilterVisibility(false);
    }

    /**
     * Prepare collection
     *
     * @return Mage_Adminhtml_Block_Widget_Grid::_prepareCollection()
     */
    protected function _prepareCollection()
    {
        // Get cur customer
        $customer = $this->getCustomer();
        // Create collection
        $collection = Mage::getModel('autoship/payment_profile')->getCollection();
        if ($this->canShowTab()) {
            // Get collection filtered by the current customer id
            /** @var Varien_Data_Collection $collection */
            $collection->addFilter('customer_email', $customer->getData('email'));
        }
        // set collection
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare layout.  Create buttons for grid (New CC, Export, etc)
     *
     * @return Mage_Core_Block_Abstract|void
     */
    protected function _prepareLayout()
    {
        // Build URL to add new payment profile
        $urlString = 'adminhtml/sppaymentprofile/new';
        $url = $this->getUrl($urlString, array('customer_id' => Mage::registry('current_customer')->getId()));

        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button->setData(array(
            'label' => Mage::helper('autoship')->__('New Credit Card'),
            'onclick' => 'setLocation(\'' . $url . '\')',
        ));
        $this->setChild('payment_profile_button', $button);

        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button->setData(array(
            'label' => Mage::helper('adminhtml')->__('Export'),
            'onclick' => $this->getJsObjectName() . '.doExport()',
            'class' => 'task'
        ));
        $this->setChild('export_button', $button);

        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button->setData(array(
            'label' => Mage::helper('adminhtml')->__('Reset Filter'),
            'onclick' => $this->getJsObjectName() . '.resetFilter()',
        ));
        $this->setChild('reset_filter_button', $button);

        $button = $this->getLayout()->createBlock('adminhtml/widget_button');
        $button->setData(array(
            'label' => Mage::helper('adminhtml')->__('Search'),
            'onclick' => $this->getJsObjectName() . '.doFilter()',
            'class' => 'task'
        ));
        $this->setChild('search_button', $button);

        // Now call parent implementation
        parent::_prepareLayout();
    }

    /**
     * Generate the html for out new payment profile button
     *
     * @return string
     */
    public function  getSearchButtonHtml()
    {
        return parent::getSearchButtonHtml() . $this->getChildHtml('payment_profile_button');
    }

    /**
     * Generate grid columns
     *
     * @return Mage_Adminhtml_Block_Widget_Grid::_prepareColumns()
     */
    protected function _prepareColumns()
    {
        // payment profile id
        $this->addColumn('id', array(
            'header' => Mage::helper('autoship')->__('Id'),
            'align' => 'left',
            'width' => '30px',
            'type' => 'number',
            'index' => 'id',
            'filter' => false,
        ));

        $this->addColumn('billing_name', array(
            'header' => Mage::helper('autoship')->__('Customer Name'),
            'index' => 'billing_name',
            'type' => 'text',
        ));

        $this->addColumn('creditcard_type', array(
            'header'  => Mage::helper('autoship')->__('Card Type'),
            'index'   => 'creditcard_type',
            'type'    => 'options',
            'options' => Mage::getModel('autoship/system_config_source_cctype')->getCcAllTypesSubscribeProFormat(),
        ));

        $this->addColumn('creditcard_number', array(
            'header' => Mage::helper('autoship')->__('Card Number'),
            'index' => 'creditcard_number',
            'type' => 'text'
        ));

        $this->addColumn('creditcard_exp_date', array(
            'header' => Mage::helper('autoship')->__('Expiration Date'),
            'index' => 'creditcard_exp_date',
            'type' => 'text'
        ));

        $this->addColumn('payment_token', array(
            'header' => Mage::helper('autoship')->__('Payment Token'),
            'index' => 'payment_token',
            'type' => 'text'
        ));

        return parent::_prepareColumns();
    }

    /**
     * Get URL to view the payment profile
     *
     * @param SFC_Autoship_Model_Payment_Profile $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            'adminhtml/sppaymentprofile/edit',
            array(
                'id' => $row->getId(),
                'customer_id' => $row->getData('magento_customer_id')
            ));
    }

    /**
     * Get the url for this grid. Used for ajax calls.
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('adminhtml/sppaymentprofile/grid', array('_current' => true));
    }

    /**
     * Delete multiple items from grid
     *
     * @return $this|Mage_Adminhtml_Block_Widget_Grid
     */
    protected function _prepareMassaction()
    {
        // Get cur customer
        $customer = $this->getCustomer();
        // Setup mass action block details
        $this->setMassactionIdField('id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        $this->getMassactionBlock()->setUseSelectAll(true);
        $this->getMassactionBlock()->addItem('remove_profile', array(
            'label' => Mage::helper('autoship')->__('Delete Credit Card(s)'),
            'url' => $this->getUrl(
                'adminhtml/sppaymentprofile/massRemove',
                array(
                    'customer_id' => $customer->getId(),
                )),
            'confirm' => Mage::helper('autoship')->__('Are you sure?')
        ));

        return $this;
    }

    public function getTabLabel()
    {
        return $this->__('Credit Cards (Subscribe Pro Vault)');
    }

    public function getTabTitle()
    {
        return $this->__('Credit Cards (Subscribe Pro Vault)');
    }

    public function canShowTab()
    {
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $this->getCustomer();
        // Check customer
        if ($customer == null || !strlen($customer->getId())) {
            return false;
        }
        // Get store from cur customer website
        $store = $customer->getStore();
        // Check config enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled', $store) != '1') {
            return false;
        }
        // Check configured payment method
        $paymentMethodCode = $platformHelper->getConfiguredPaymentMethodCode();
        if ($paymentMethodCode != SFC_Autoship_Model_Payment_Method::METHOD_CODE) {
            return false;
        }

        return true;
    }

    public function isHidden()
    {
        return false;
    }

    /**
     * Get the current customer, for which we're showing the grid of credit cards
     *
     * @return Mage_Customer_Model_Customer
     */
    public function getCustomer()
    {
        // Cur customer
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::registry('current_customer');

        // If we found customer, set config store for API calls from customer website / store
        if ($customer != null) {
            /** @var SFC_Autoship_Helper_Api $apiHelper */
            $apiHelper = Mage::helper('autoship/api');
            // Get store from cur customer website
            $store = $customer->getStore();
            // Set store on api helper
            $apiHelper->setConfigStore($store);
        }

        return $customer;
    }

}
