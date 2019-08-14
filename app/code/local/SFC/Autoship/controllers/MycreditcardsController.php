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

class SFC_Autoship_MycreditcardsController extends Mage_Core_Controller_Front_Action
{
    /**
     * Authenticate customer
     */
    public function preDispatch()
    {
        parent::preDispatch();
        // Require logged in customer
        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
        // Check if payment vault payment method enabled
        if (Mage::getStoreConfig('payment/' . SFC_Autoship_Model_Payment_Method::METHOD_CODE . '/active') != '1') {
            // Send customer to 404 page
            $this->_forward('defaultNoRoute');
            return;
        }
    }

    /**
     * Authorize customer from session against the specified profile.  This ensures customer is allowed to edit / update / delete profile.
     *
     * @param SFC_CyberSource_Model_payment_profile $paymentProfile Profile to authorize against current customer
     * @throws SFC_CyberSource_Helper_Gateway_Exception
     */
    protected function authorizeCustomerForProfile(SFC_CyberSource_Model_payment_profile $paymentProfile)
    {
        // Get customer session
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');
        // Authorize customer for this profile - in other words profile must belong to this customer
        if ($customerSession->getCustomerId() != $paymentProfile->getData('customer_id')) {
            throw new SFC_CyberSource_Helper_Gateway_Exception('Customer not authorized to edit this profile!');
        }
    }

    /**
     * Customer Dashboard, payment profile grid
     */
    public function indexAction()
    {
        // Load layout
        $this->loadLayout();

        // Set page title
        /** @var Mage_Page_Block_Html_Head $headBlock */
        $headBlock = $this->getLayout()->getBlock('head');
        $headBlock->setTitle($this->__('My Credit Cards'));

        $this->renderLayout();
    }

    /**
     * New payment profile
     */
    public function newAction()
    {
        // Get core session
        /** @var Mage_Core_Model_Session $coreSession */
        $coreSession = Mage::getSingleton('core/session');
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        // Get customer session
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');

        // Load layout
        $this->loadLayout();

        // Set page title
        /** @var Mage_Page_Block_Html_Head $headBlock */
        $headBlock = $this->getLayout()->getBlock('head');
        $headBlock->setTitle($this->__('Enter New Saved Credit Card'));

        try {
            // Create new profile
            /** @var SFC_Autoship_Model_Payment_Profile $paymentProfile */
            $paymentProfile = Mage::getModel('autoship/payment_profile');
            // Init new cim profile with customer info
            $paymentProfile->initProfileWithCustomerDefault($customerSession->getCustomerId());
            // Pass fields to view for rendering
            $this->getLayout()->getBlock('payment_profile_edit')->setData('cim_profile', $paymentProfile);
        }
        catch (Exception $e) {
            $coreSession->addError('Failed to load new credit credit card page!');
            // Send customer back to grid
            $this->_redirect('creditcards/*/');

            return;
        }

        // Render layout
        $this->renderLayout();
    }

    /**
     * Edit payment profile
     */
    public function editAction()
    {
        // Get core session
        /** @var Mage_Core_Model_Session $coreSession */
        $coreSession = Mage::getSingleton('core/session');
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');

        // Load layout
        $this->loadLayout();

        // Set page title
        /** @var Mage_Page_Block_Html_Head $headBlock */
        $headBlock = $this->getLayout()->getBlock('head');
        $headBlock->setTitle($this->__('Edit Saved Credit Card'));

        try {
            // Get profile ID & load the profile
            $profileId = $this->getRequest()->getParam('id');
            $paymentProfile = $vaultHelper->getPaymentProfile($profileId);
            // Pass fields to view for rendering
            $this->getLayout()->getBlock('payment_profile_edit')->setData('cim_profile', $paymentProfile);
        }
        catch (Exception $e) {
            Mage::log('Error: ' . $e->getMessage(), Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
            $coreSession->addError('Failed to retrieve credit card for edit!');
            // Send customer back to grid
            $this->_redirect('subscriptions/mycreditcards/');

            return;
        }

        $this->renderLayout();
    }

    public function redirectAction()
    {
        // Get core session
        /** @var Mage_Core_Model_Session $coreSession */
        $coreSession = Mage::getSingleton('core/session');
        // Get customer session
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');

        try {
            // Get token
            $token = $this->getRequest()->getParam('token');
            // Create or update customer on platform
            $platformHelper->createOrUpdateCustomer($customerSession->getCustomer());
            // Store token
            $vaultHelper->storeToken($customerSession->getCustomerId(), $token);
        }
        catch (Exception $e) {
            Mage::log('Error: ' . $e->getMessage(), Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
            $coreSession->addError('Failed to store credit card!');
        }

        // Send customer back to grid
        $this->_redirect('subscriptions/mycreditcards/');
    }

    /**
     * Save a payment profile which is being edited
     */
    public function saveAction()
    {
        // Get core session
        /** @var Mage_Core_Model_Session $coreSession */
        $coreSession = Mage::getSingleton('core/session');
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');

        // Get post data
        $postData = $this->getRequest()->getPost();
        //print_r($postData);die;

        try {
            // Get profile ID & load the profile
            $profileId = $postData['payment_profile_id'];
            $paymentProfile = $vaultHelper->getPaymentProfile($profileId);
            // Update payment profile with post data
            $paymentProfile->updatePaymentProfileFromVaultData($postData);
            // Update profile in vault
            $vaultHelper->updatePaymentProfile($paymentProfile);

            $coreSession->addSuccess('Credit card was successfully updated!');
        }
        catch (Exception $e) {
            Mage::log('Error: ' . $e->getMessage(), Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
            $coreSession->addError('Failed to save credit card!');
        }

        // Send customer back to grid
        $this->_redirect('subscriptions/mycreditcards/');
    }

    /**
     * Delete payment profile
     */
    public function deleteAction()
    {
        // Get core session
        /** @var Mage_Core_Model_Session $coreSession */
        $coreSession = Mage::getSingleton('core/session');
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');

        // Get id of profile to delete
        $profileId = $this->getRequest()->getParam('id');
        try {
            // Get pay profile
            $paymentProfile = $vaultHelper->getPaymentProfile($profileId);
            // Redact profile in vault
            $vaultHelper->redactPaymentProfile($paymentProfile);

            $coreSession->addSuccess('Your credit card was deleted.');
        }
        catch (Exception $e) {
            $coreSession->addError('Failed to delete saved credit card!');
        }

        // Send customer back to grid
        $this->_redirect('subscriptions/mycreditcards/');
    }

}
