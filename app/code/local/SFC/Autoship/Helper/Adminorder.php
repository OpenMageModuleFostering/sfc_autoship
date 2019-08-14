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
 * Helper class - Access with Mage::helper('autoship/adminorder')
 *
 * Based on code from Mage_Adminhtml_Sales_Order_CreateController which calls Mage_Adminhtml_Model_Sales_Order_Create a lot
 * With inspiration from {@link http://inchoo.net/ecommerce/magento/programmatically-create-order-in-magento/ Inchoo's Programmatically Creating and Order}
 *
 */
class SFC_Autoship_Helper_Adminorder extends Mage_Core_Helper_Abstract
{
    /**
     * Instance vars to hold details about quote / order
     */
    private $_storeId = '1';
    private $_groupId = '1';
    private $_paymentData;
    private $_orderData;
    private $_productQtys;
    private $_customer;
    private $_billingAddressId;
    private $_shippingAddressId;

    /**
     * Set details for quote / order creation.
     * Only SFC Authorize.NET CIM payment method is supported at this time
     * @param Mage_Customer_Model_Customer $customer
     * @param $storeId
     * @param $productQtys
     * @param $billingAddressId
     * @param $shippingAddressId
     * @param $cimPaymentProfileId
     * @param $shippingMethod
     */
    public function setOrderDetails(Mage_Customer_Model_Customer $customer, $storeId, $productQtys, $billingAddressId,
        $shippingAddressId, $cimPaymentProfileId, $shippingMethod)
    {
        // Save order info
        $this->_storeId = $storeId;
        $this->_customer = $customer;
        $this->_billingAddressId = $billingAddressId;
        $this->_shippingAddressId = $shippingAddressId;

        // Save products array
        $this->_productQtys = $productQtys;

        // Build array of order data
        $this->_orderData = array(
            'currency' => 'USD',
            'account' => array(
                'group_id' => $this->_groupId,
                'email' => $this->_customer->getEmail()
            ),
            'shipping_method' => $shippingMethod,
        );
        // Build array of payment data
        // Only SFC Authorize.NET CIM payment method is supported at this time
        $this->_paymentData = array(
            'method' => SFC_AuthnetToken_Model_Cim::METHOD_CODE,
            'cc_cid' => null,
            'payment_profile_id' => $cimPaymentProfileId,
            // TO DO: Lookup real cc last 4 digits
            'saved_cc_last_4' => '1111'
        );
    }

    /**
     * Retrieve order create model
     *
     * @return  Mage_Adminhtml_Model_Sales_Order_Create
     */
    protected function _getOrderCreateModel()
    {
        return Mage::getSingleton('adminhtml/sales_order_create');
    }

    /**
     * Retrieve session object
     *
     * @return Mage_Adminhtml_Model_Session_Quote
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session_quote');
    }

    /**
     * Initialize order creation session data
     *
     */
    protected function _initSession($customerId, $storeId)
    {
        /* Get/identify customer */
        $this->_getSession()->setCustomerId((int)$customerId);
        /* Get/identify store */
        $this->_getSession()->setStoreId((int)$storeId);

        return $this;
    }

    /**
     * Creates order from info in instance vars
     */
    public function createOrder()
    {
        try {
            // Log
            Mage::log('Initing quote session...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            // Init session (Mage_Adminhtml_Model_Session_Quote)
            $this->_initSession($this->_customer->getId(), $this->_storeId);
            // Log
            Mage::log('Creating quote...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            // Create quote
            $this->createQuote();
            // Log
            Mage::log('Setting payment method data...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            // Set payment data on quote
            $this->_getOrderCreateModel()->setPaymentData($this->_paymentData);
            $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($this->_paymentData);
            // Set product options
            // TO DO: Handle product options, configurable products, etc
            /*
            $item = $this->_getOrderCreateModel()->getQuote()->getItemByProduct($this->_product);
            $item->addOption(new Varien_Object(
            array(
                'product' => $this->_product,
                'code' => 'option_ids',
                'value' => '5' // Option id goes here. If more options, then comma separate
                )
            ));
            $item->addOption(new Varien_Object(
            array(
                'product' => $this->_product,
                'code' => 'option_5',
                'value' => 'Some value here'
                )
            ));
            */
            // Log
            Mage::log('Placing order...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            // Adjust Mage config
            Mage::app()->getStore()->setConfig(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, '0');
            $order = $this->_getOrderCreateModel()->createOrder();
            // Clear the session object
            $this->_getSession()->clear();
            // Unregister 'rule_data'
            // TO DO: Why do we need to do this manually?
            Mage::unregister('rule_data');
            // Log
            Mage::log('New order created: ' . $order->getIncrementId(), Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

            // Return newly created order
            return $order;
        }
        catch (Exception $e) {
            // Unregister 'rule_data'
            // TO DO: Why do we need to do this manually?
            Mage::unregister('rule_data');
            // Log
            Mage::log('Error placing order!', Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
            Mage::log('Error message: ' . $e->getMessage(), Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
            // Rethrow exception
            throw $e;
        }
    }

    /**
     * Creates quote from info in instance vars
     */
    protected function createQuote()
    {
        // Log
        Mage::log('Setting quote data...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        $this->_getOrderCreateModel()->importPostData($this->_orderData);

        // Log
        Mage::log('Setting billing address...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        // Create and set billing address
        $customerAddress = Mage::getModel('customer/address')->load($this->_billingAddressId);
        $billingAddress = Mage::getModel('sales/quote_address');
        $billingAddress
            ->importCustomerAddress($customerAddress)
            ->setSaveInAddressBook(0)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING);
        // There is a bug in Mage_Adminhtml_Model_Sales_Order_Create::setBillingAddress
        // Work around this bug by calling setBillingAddress() directly on the quote object
        $this->_getOrderCreateModel()->getQuote()->setBillingAddress($billingAddress);

        // Log
        Mage::log('Setting shipping address...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        $customerAddress = Mage::getModel('customer/address')->load($this->_shippingAddressId);
        $shippingAddress = Mage::getModel('sales/quote_address');
        $shippingAddress
            ->importCustomerAddress($customerAddress)
            ->setSameAsBilling(0)
            ->setSaveInAddressBook(0)
            ->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING);
        $this->_getOrderCreateModel()->setShippingAddress($shippingAddress);

        // Log
        Mage::log('Adding products...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        // Just like adding products from Magento admin grid
        $this->_getOrderCreateModel()->addProducts($this->_productQtys);
        // Log
        Mage::log('Collecting shipping rates...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        $this->_getOrderCreateModel()->collectShippingRates();
        // Log
        Mage::log('Setting payment data...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        // Setting payment data
        $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($this->_paymentData);

        // Log
        Mage::log('Saving quote...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        // Init rule data and save quote
        $this->_getOrderCreateModel()
            ->initRuleData()
            ->saveQuote();

        // Log
        Mage::log('Setting payment data...', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        // Setting payment data (again)
        $this->_getOrderCreateModel()->getQuote()->getPayment()->addData($this->_paymentData);

        return $this;
    }

}
