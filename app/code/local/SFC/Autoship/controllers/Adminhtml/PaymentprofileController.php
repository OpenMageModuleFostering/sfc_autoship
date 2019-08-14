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

class SFC_Autoship_Adminhtml_PaymentprofileController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Set admin breadcrumbs
     */
    protected function _initAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('customers/paymentprofile');
        $this->_addBreadcrumb(
            Mage::helper('adminhtml')->__('Payment Profile Manager'),
            Mage::helper('adminhtml')->__('Payment Profile Manager'));

        return $this;
    }

    /**
     * Edit existing payment profile
     */
    public function editAction()
    {
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');

        $this->_initAction();

        $this->_title($this->__('Subscribe Pro'));
        $this->_title($this->__('Customer'));
        $this->_title($this->__('Edit Item'));

        try {
            // Get profile and customer IDs from request
            $profileId = $this->getRequest()->getParam('id');
            $customerId = $this->getRequest()->getParam('customer_id');

            // Load customer
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel('customer/customer')->load($customerId);

            // Set store on api helper
            $store = $customer->getStore();
            $apiHelper->setConfigStore($store);

            // Lookup profile via Subscribe Pro API
            /** @var SFC_Autoship_Model_Payment_Profile $profile */
            $profile = $vaultHelper->getPaymentProfile($profileId);

            // Save profile in registry
            Mage::register('paymentprofile_data', $profile);
            Mage::register('customer_id', $customer->getId());

            $this->loadLayout();
            $this->_setActiveMenu('autoship/paymentprofile');
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Payment Profile'), Mage::helper('adminhtml')->__('Payment Profile'));
            $this->_addBreadcrumb(Mage::helper('adminhtml')->__('Payment Profile'), Mage::helper('adminhtml')->__('Information'));
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('autoship/adminhtml_customer_paymentprofiles_edit'));
            $this->_addLeft($this->getLayout()->createBlock('autoship/adminhtml_customer_paymentprofiles_edit_tabs'));
            $this->renderLayout();

            return;
        }
        catch(\Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('autoship')->__('Failed to find saved credit card.'));
            $this->_redirect('*/*/');
        }
    }

    /**
     * Create new CIM payment profile
     */
    public function newAction()
    {
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        $this->_title($this->__('Subscribe Pro'));
        $this->_title($this->__('Payment Profile'));
        $this->_title($this->__('New Payment Profile'));

        // Get profile and customer IDs from request
        $customerId = $this->getRequest()->getParam('customer_id');

        // Load customer
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);

        // Set store on api helper
        $store = $customer->getStore();
        $apiHelper->setConfigStore($store);

        // Create new model for editing & init with customer fields
        /** @var SFC_Autoship_Model_Payment_Profile $profile */
        $profile = Mage::getModel('autoship/payment_profile');
        $profile->initProfileWithCustomerDefault($customer);

        // Save profile in registry
        Mage::register('paymentprofile_data', $profile);
        Mage::register('customer_id', $customer->getId());

        $this->loadLayout();
        $this->_setActiveMenu('autoship/paymentprofile');

        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);

        $this->_addBreadcrumb(Mage::helper('autoship')->__('Payment Profile Manager'), Mage::helper('adminhtml')
            ->__('Payment Profile Manager'));
        $this->_addBreadcrumb(Mage::helper('autoship')->__('Info'), Mage::helper('adminhtml')->__('info'));


        $this->_addContent($this->getLayout()->createBlock('autoship/adminhtml_customer_paymentprofiles_edit'));
        $this->_addLeft($this->getLayout()->createBlock('autoship/adminhtml_customer_paymentprofiles_edit_tabs'));

        $this->renderLayout();

    }

    /**
     * Save data to existing payment profile
     */
    public function saveAction()
    {
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');

        $postData = $this->getRequest()->getPost();

        if ($postData) {
            try {
                // Load customer
                /** @var Mage_Customer_Model_Customer $customer */
                $customer = Mage::getModel('customer/customer')->load($postData['magento_customer_id']);

                // Set store on api helper
                $store = $customer->getStore();
                $apiHelper->setConfigStore($store);

                if ($profileId = $this->getRequest()->getParam('id')) {
                    // Get profile and customer IDs from request
                    $profileId = $postData['id'];

                    // Lookup profile via Subscribe Pro API
                    /** @var SFC_Autoship_Model_Payment_Profile $profile */
                    $profile = $vaultHelper->getPaymentProfile($profileId);

                    // Save post data to model
                    $profile->addData($postData);
                    $profile->unmapFlatFields();

                    // Call update method on API
                    $vaultHelper->updatePaymentProfile($profile);
                }
                else {
                    // Create new card
                    /** @var SFC_Autoship_Model_Payment_Profile $profile */
                    $profile = Mage::getModel('autoship/payment_profile');
                    $profile->initProfileWithCustomerDefault($customer);

                    // Save post data to model
                    $profile->addData($postData);
                    $profile->unmapFlatFields();

                    // Create or update customer and create pay profile
                    $platformHelper->createOrUpdateCustomer($customer);
                    $vaultHelper->createPaymentProfile($profile);
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('Saved credit card.'));
                Mage::getSingleton('adminhtml/session')->setCustomerData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $profile->getId()));

                    return;
                }
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError('Failed to save credit card!');
            }
        }

        // Send customer back to saved credit cards grid
        $this->_redirect('adminhtml/customer/edit/tab/customer_info_tabs_paymentprofile', array('id' => $postData['magento_customer_id']));
    }

    /**
     * Delete multiple items
     */
    public function massRemoveAction()
    {
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');

        // Get profile and customer IDs from request
        $customerId = $this->getRequest()->getParam('customer_id');

        try {
            // Get list of profile ids to delete / redact
            $profileIds = $this->getRequest()->getPost('ids', array());

            // Load customer
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel('customer/customer')->load($customerId);

            // Set store on api helper
            $store = $customer->getStore();
            $apiHelper->setConfigStore($store);

            // Iterate each selected profile
            foreach ($profileIds as $profileId) {
                try {
                    $vaultHelper->redactPaymentProfileId($profileId);
                }
                catch (\Exception $e) {
                    $message =
                        'Failed to delete payment profile ID #' . $profileId;
                    Mage::getSingleton('adminhtml/session')->addError($message);
                }
            }
            Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('autoship')->__('Deleted saved credit card(s).'));
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError('Failed to delete saved credit card!');
        }

        $this->_redirect('adminhtml/customer/edit/tab/customer_info_tabs_paymentprofile', array('id' => $customerId));
    }

    /**
     * Profile grid for AJAX request
     */
    public function gridAction()
    {
        $customer = Mage::getModel('customer/customer')->load($this->getRequest()->getParam('id'));
        Mage::register('current_customer', $customer);
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('autoship/adminhtml_customer_paymentprofiles_paymentprofile')->toHtml()
        );
    }

}
