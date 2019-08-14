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
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */

class SFC_Autoship_Model_Payment_Method extends Mage_Payment_Model_Method_Cc
{
    const METHOD_CODE = 'subscribe_pro';
    const METHOD_CODE_KEY_TOKEN = '_token_';

    /**
     * Payment method code
     */
    protected $_code = self::METHOD_CODE;

    /**
     * Form block type
     */
    protected $_formBlockType = 'autoship/payment_form_cc';
    protected $_formBlockTypeSaved = 'autoship/payment_form_cc_saved';

    /**
     * Info block type
     */
    protected $_infoBlockType = 'autoship/payment_info_cc';

    /**
     * Availability options
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;
    protected $_canFetchTransactionInfo = true;

    /**
     * Members to hold saved card instance data     *
     */
    protected $_savedPaymentProfile = null;

    /**
     * Turn this method instance into a method representing once particular saved card / profile
     */
    public function setSavedPaymentProfile(SFC_Autoship_Model_Payment_Profile $paymentProfile)
    {
        $this->_savedPaymentProfile = $paymentProfile;
    }

    /**
     * @return SFC_Autoship_Model_Payment_Profile
     */
    public function getSavedPaymentProfile()
    {
        return $this->_savedPaymentProfile;
    }

    /**
     * Retrieve payment method title
     *
     * @return string
     */
    public function getTitle()
    {
        // If this is a saved card instance, title is saved card last digits
        if ($this->_savedPaymentProfile instanceof SFC_Autoship_Model_Payment_Profile) {
            return Mage::helper('autoship')->__('Use my Saved Credit Card (%s)', $this->_savedPaymentProfile->getData('creditcard_last_digits'));
        }
        else {
            return $this->getConfigData('title');
        }
    }

    /**
     * Retrieve payment method code
     *
     * @return string
     */
    public function getCode()
    {
        if (empty($this->_code)) {
            Mage::throwException(Mage::helper('payment')->__('Cannot retrieve the payment method code.'));
        }
        if ($this->_savedPaymentProfile instanceof SFC_Autoship_Model_Payment_Profile) {
            return self::METHOD_CODE . self::METHOD_CODE_KEY_TOKEN . $this->_savedPaymentProfile->getData('payment_token');
        }
        else {
            return $this->_code;
        }
    }

    /**
     * Retrieve block type for method form generation
     *
     * @return string
     */
    public function getFormBlockType()
    {
        if ($this->_savedPaymentProfile instanceof SFC_Autoship_Model_Payment_Profile) {
            return $this->_formBlockTypeSaved;
        }
        else {
            return $this->_formBlockType;
        }
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param int|string|null|Mage_Core_Model_Store $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if (null === $storeId) {
            $storeId = $this->getStore();
        }
        $path = 'payment/' . self::METHOD_CODE . '/' . $field;

        return Mage::getStoreConfig($path, $storeId);
    }

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  $this
     */
    public function assignData($data)
    {
        // Get Mage_Payment_Model_Info instance from quote
        $info = $this->getInfoInstance();

        //Clear out additional information that may have been set previously in the session
        $info->setAdditionalInformation(array());

        // Call parent assignData
        parent::assignData($data);

        // Check config and cc number
        if ($this->getConfigData('use_transparent_redirect') != '1' && isset($data['cc_number']) && strlen($data['cc_number']) && strpos($data['cc_number'], 'XXXX') === false) {
            // Get quote
            /** @var Mage_Sales_Model_Quote $quote */
            $quote = $info->getQuote();
            // In this case, we aren't using transparent redirect, nor using an existing token, someone has passed in full CC number, create new token
            $this->createNewPaymentToken($quote, $data);
        }

        // Customer entering new card
        // Save basic fields in additional info
        if (isset($data['save_card'])) {
            $info->setAdditionalInformation('save_card', $data['save_card']);
        }
        if (isset($data['is_new_card'])) {
            $info->setAdditionalInformation('is_new_card', $data['is_new_card']);
        }
        if (isset($data['payment_token'])) {
            $info->setAdditionalInformation('payment_token', $data['payment_token']);
        }
        if (isset($data['cc_number'])) {
            $info->setAdditionalInformation('obscured_cc_number', $data['cc_number']);
        }
        if (isset($data['third_party_token'])) {
            $info->setAdditionalInformation('is_third_party', true);
        }

        return $this;
    }

    /**
     * Check whether payment method can be used
     *
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        // If $quote object not populated, call parent method
        if ($quote == null) {
            // Call parent
            return parent::isAvailable($quote);
        }
        // Check the checkout method selected and look for guest checkout
        if ($quote != null && $quote->getCheckoutMethod() == Mage_Checkout_Model_Type_Onepage::METHOD_GUEST) {
            // Check configuration settings
            if (Mage::getStoreConfig('payment/' . self::METHOD_CODE . '/allow_guest_checkout', $quote->getStore()) == '1') {
                // Guest checkout option is enabled, call parent method to see if payment method is available
                return parent::isAvailable($quote);
            }
            else {
                // return No for guest checkout situation when guest checkout option disabled
                return false;
            }
        }

        // If all else fails, call the parent method
        return parent::isAvailable($quote);
    }

    /**
     * Check whether payment method is applicable to quote
     * Purposed to allow use in controllers some logic that was implemented in blocks only before
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param int|null $checksBitMask
     * @return bool
     */
    public function isApplicableToQuote($quote, $checksBitMask)
    {
        /** @var SFC_Autoship_Helper_Quote $quoteHelper */
        $quoteHelper = Mage::helper('autoship/quote');

        if ($quoteHelper->hasProductsToCreateNewSubscription()) {
            //Remove the check for zero dollar checkout on this method, as its code is not 'free' but it does support zero dollar checkout
            if ($checksBitMask) {
                $checksBitMask = $checksBitMask & ~self::CHECK_ZERO_TOTAL;
            }
        } else {
            if ($this->getConfigData('active_non_subscription') != '1') {
                return false;
            }
        }
        return parent::isApplicableToQuote($quote, $checksBitMask);
    }

    public function hasVerification()
    {
        // Always ignore verification code in admin ordering
        if (Mage::app()->getStore()->isAdmin()
            || ($this->getSavedPaymentProfile() instanceof SFC_Autoship_Model_Payment_Profile
                && $this->getSavedPaymentProfile()->isThirdParty())) {
            return false;
        }
        else {
            return parent::hasVerification();
        }
    }

    /**
     * Validate payment method information object
     * @return $this
     * @throws SFC_Autoship_Helper_PaymentError_Exception
     */
    public function validate()
    {
        //Don't validate if we're in the API
        if (Mage::app()->getFrontController()->getRequest()->getModuleName() == 'api') {
            return true;
        }

        foreach($this->_getValidateFields($this->getInfoInstance()) as $field) {
            if (!$this->_validateField($field, $this->getInfoInstance())) {
                throw new SFC_Autoship_Helper_PaymentError_Exception(Mage::helper("autoship")->__('Payment form field: \'' . $field . '\' is missing from POST!'));
            }
        }
        //We are letting vault do any additional validation
        return true;
    }

    /**
     * Determine which fields should be validated
     * @param Mage_Payment_Model_Info $infoInstance
     * @return array
     */
    protected function _getValidateFields(Mage_Payment_Model_Info $infoInstance)
    {
        if ($infoInstance->getAdditionalInformation('is_third_party')) {
            //If third party we'll only be validating the cc & token
            $_required_keys = array('cc_number', 'additional_information/payment_token');
        } else {
            //Otherwise validate all fields provided
            $_required_keys = array('cc_number', 'cc_exp_month', 'cc_exp_year', 'additional_information/payment_token');
            if ($this->hasVerification()) {
                $_required_keys[] = 'cc_cid';
            }
        }
        return $_required_keys;
    }

    /**
     * Validate a field on the info instance.
     * @param $field
     * @param Mage_Payment_Model_Info $infoInstance
     * @return mixed
     */
    protected function _validateField($field, Mage_Payment_Model_Info $infoInstance)
    {
        $method = 'getData';
        if (preg_match('/^additional_information\//', $field)) {
            $method = 'getAdditionalInformation';
            $field = preg_replace('/^additional_information\//', '', $field);
        }
        return $infoInstance->{$method}($field);
    }

    /**
     * Send authorize request to gateway
     *
     * @param Mage_Payment_Model_Info|\Varien_Object $payment
     * @param  float $amount
     * @return $this
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        // Log
        Mage::log('====== SFC_Autoship_Model_Payment_Method::authorize called ======', Zend_Log::INFO,
            SFC_Autoship_Helper_Data::LOG_FILE);

        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Get order, etc from $payment
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $order->getCustomer();

        // Set website / store for config on API helper
        $store = Mage::app()->getWebsite($customer->getData('website_id'))->getDefaultStore();
        $apiHelper->setConfigStore($store);
        // Check if we should do profile transaction or one-time transaction
        if ($payment->getAdditionalInformation('save_card') == '1' || $payment->getAdditionalInformation('is_new_card') != '1') {
            // We are using existing pay profile or storing new one
            // Get payment profile
            $paymentProfile = $this->_createOrFetchPaymentProfile(
                $payment,
                $order->getBillingAddress(),
                $payment->getData('cc_exp_month'),
                $payment->getData('cc_exp_year'));
            // Create transaction
            if ($amount > 0.00) {
                //Authorize if positive amount
                $platformTransaction = $vaultHelper->authorize(
                    $paymentProfile,
                    $amount,
                    $order->getData('base_currency_code'),
                    array(
                        'email' => $order->getCustomerEmail(),
                        'order_id' => $order->getIncrementId(),
                        'ip' => $order->getRemoteIp(),
                    )
                );
            } else {
                //Otherwise verify
                Mage::log('====== SFC_Autoship_Model_Payment_Method::authorize called, but only verifying due to $0.00! ======', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
                $platformTransaction = $vaultHelper->verify(
                    $paymentProfile,
                    $order->getData('base_currency_code'),
                    array(
                        'email' => $order->getCustomerEmail(),
                        'order_id' => $order->getIncrementId(),
                        'ip' => $order->getRemoteIp(),
                    )
                );
            }
        }
        else {

            //Don't allow $0.00 for a one-time purchase, should use the 'free' method
            if ($amount <= 0) {
                Mage::throwException(Mage::helper('autoship')->__('Invalid amount for authorization.'));
            }

            // Create one-time transaction
            $platformTransaction = $vaultHelper->authorizeOneTime(
                $payment->getAdditionalInformation('payment_token'),
                $amount,
                array(
                    'email' => $order->getCustomerEmail(),
                    'order_id' => $order->getIncrementId(),
                    'ip' => $order->getRemoteIp(),
                ),
                $order->getData('base_currency_code'),
                $order->getBillingAddress(),
                $payment->getData('cc_exp_month'),
                $payment->getData('cc_exp_year')
            );
            // Save cc type in $payment
            $ccType = $vaultHelper->mapSubscribeProCardTypeToMagento($platformTransaction['creditcard_type'], false);
            if (strlen($ccType)) {
                $payment->setCcType($ccType);
            }
        }

        // Save transaction details in $payment
        $payment
            ->setIsTransactionClosed(0)
            ->setCcTransId($platformTransaction['id'])
            ->setTransactionId($platformTransaction['id'])
            ->setAdditionalInformation('transaction_token', $platformTransaction['token'])
            ->setAdditionalInformation('gateway_transaction_id', $platformTransaction['gateway_transaction_id'])
            ->setAdditionalInformation('transaction_type', $platformTransaction['type'])
        ;
        // Save AVS and CVV results when available
        if ($payment instanceof Mage_Sales_Model_Order_Payment) {
            if (isset($platformTransaction['avs_code'])) {
                $payment->setCcAvsStatus($platformTransaction['avs_code']);
            }
            if (isset($platformTransaction['cvv_code'])) {
                $payment->setCcCidStatus($platformTransaction['cvv_code']);
            }
        }
        /*
        // Create transaction
        // Magento seems to be creating this transaction already
        // $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
        */

        return $this;
    }

    /**
     * Send capture request to gateway
     *
     * @param Mage_Payment_Model_Info|\Varien_Object $payment
     * @param float $amount
     * @return $this
     */
    public function capture(Varien_Object $payment, $amount)
    {
        // Log
        Mage::log('====== SFC_Autoship_Model_Payment_Method::capture called ======', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Check if we're doing an auth n capture transaction or if we are just capturing and already auth'd transaction
        // Look for a value in cc_trans_id and also that the saved payment_action was 'authorize'
        if (strlen($payment->getData('cc_trans_id')) > 0 && $payment->getAdditionalInformation('transaction_type') == 'Authorization') {

            if ($amount <= 0) {
                //Don't allow prior auth capture for $0.00 transaction?
                Mage::throwException(Mage::helper('autoship')->__('Invalid amount for capture.'));
            }

            /*
             * We are doing PriorAuthCapture here...
             */

            // Get order, etc from $payment
            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();

            // Set website / store for config on API helper
            $store = Mage::app()->getStore($payment->getOrder()->getStoreId());
            $apiHelper->setConfigStore($store);

            // Use API to create a new prior auth-capture transaction
            $platformTransaction = $vaultHelper->capture($payment->getCcTransId(), $amount, $order->getData('base_currency_code'));

            // Save transaction details in $payment
            $payment
                ->setIsTransactionClosed(0)
                ->setParentTransactionId($payment->getData('cc_trans_id'))
                ->setTransactionId($platformTransaction['id'])
//                ->setParentTransactionId($payment->getData('cc_trans_id'))
//                ->setCcTransId($platformTransaction['id'])
//                ->setTransactionId($platformTransaction['id'])
//                ->setAdditionalInformation('transaction_token', $platformTransaction['token'])
//                ->setAdditionalInformation('gateway_transaction_id', $platformTransaction['gateway_transaction_id'])
//                ->setAdditionalInformation('transaction_type', $platformTransaction['type'])
            ;
            // Save AVS and CVV results when available
            if ($payment instanceof Mage_Sales_Model_Order_Payment) {
                if (isset($platformTransaction['avs_code'])) {
                    $payment->setCcAvsStatus($platformTransaction['avs_code']);
                }
                if (isset($platformTransaction['cvv_code'])) {
                    $payment->setCcCidStatus($platformTransaction['cvv_code']);
                }
            }
            // Create transaction
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);
        }
        else {
            /*
             * We are doing AuthCapture (also known as purchase) transaction here...
             */

            // Get order, etc from $payment
            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = $order->getCustomer();

            // Set website / store for config on API helper
            $store = Mage::app()->getWebsite($customer->getData('website_id'))->getDefaultStore();
            $apiHelper->setConfigStore($store);
            // Check if we should do profile transaction or one-time transaction
            if ($payment->getAdditionalInformation('save_card') == '1' || $payment->getAdditionalInformation('is_new_card') != '1') {
                // We are using existing pay profile or storing new one
                // Get payment profile
                $paymentProfile = $this->_createOrFetchPaymentProfile(
                    $payment,
                    $order->getBillingAddress(),
                    $payment->getData('cc_exp_month'),
                    $payment->getData('cc_exp_year'));
                // Create transaction
                if ($amount > 0) {
                    //Capture if positive amount
                    $platformTransaction = $vaultHelper->purchase(
                        $paymentProfile,
                        $amount,
                        $order->getData('base_currency_code'),
                        array(
                            'email' => $order->getCustomerEmail(),
                            'order_id' => $order->getIncrementId(),
                            'ip' => $order->getRemoteIp(),
                        )
                    );
                } else {
                    //Otherwise just verify the card
                    Mage::log('====== SFC_Autoship_Model_Payment_Method::capture called, but only verifying due to $0.00! ======', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
                    $platformTransaction = $vaultHelper->verify(
                        $paymentProfile,
                        $order->getData('base_currency_code'),
                        array(
                            'email' => $order->getCustomerEmail(),
                            'order_id' => $order->getIncrementId(),
                            'ip' => $order->getRemoteIp(),
                        )
                    );
                }
            }
            else {
                if ($amount <= 0) {
                    //Don't allow $0.00 auth for one-time purchase, should use 'free' method
                    Mage::throwException(Mage::helper('autoship')->__('Invalid amount for capture.'));
                }
                // Create one-time purchase transaction
                $platformTransaction = $vaultHelper->purchaseOneTime(
                    $payment->getAdditionalInformation('payment_token'),
                    $amount,
                    $order->getData('base_currency_code'),
                    array(
                        'email' => $order->getCustomerEmail(),
                        'order_id' => $order->getIncrementId(),
                        'ip' => $order->getRemoteIp(),
                    ),
                    $order->getBillingAddress(),
                    $payment->getData('cc_exp_month'),
                    $payment->getData('cc_exp_year')
                );
                // Save cc type in $payment
                $ccType = $vaultHelper->mapSubscribeProCardTypeToMagento($platformTransaction['creditcard_type'], false);
                if (strlen($ccType)) {
                    $payment->setCcType($ccType);
                }
            }

            // Save transaction details in $payment
            $payment
                ->setIsTransactionClosed(0)
                ->setCcTransId($platformTransaction['id'])
                ->setTransactionId($platformTransaction['id'])
                ->setAdditionalInformation('transaction_token', $platformTransaction['token'])
                ->setAdditionalInformation('gateway_transaction_id', $platformTransaction['gateway_transaction_id'])
                ->setAdditionalInformation('transaction_type', $platformTransaction['type'])
            ;
            // Save AVS and CVV results when available
            if ($payment instanceof Mage_Sales_Model_Order_Payment) {
                if (isset($platformTransaction['avs_code'])) {
                    $payment->setCcAvsStatus($platformTransaction['avs_code']);
                }
                if (isset($platformTransaction['cvv_code'])) {
                    $payment->setCcCidStatus($platformTransaction['cvv_code']);
                }
            }
            // Create transaction
            $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);

        }

        return $this;
    }

    /**
     * Void the payment through gateway
     *
     * @param Mage_Payment_Model_Info|\Varien_Object $payment
     * @return $this
     */
    public function void(Varien_Object $payment)
    {
        // Log
        Mage::log('====== SFC_Autoship_Model_Payment_Method::void called ======', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Set website / store for config on API helper
        $store = Mage::app()->getStore($payment->getOrder()->getStoreId());
        $apiHelper->setConfigStore($store);

        // Use API to create a new prior auth-capture transaction
        $platformTransaction = $vaultHelper->void($payment->getCcTransId());

        // Save transaction details in $payment
        // Field cc_trans_id in payment should hold the single authorize trans id and then the single capture trans id
        // (or just the single auth n capture trans id)
        $payment
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1)
            ->setParentTransactionId($payment->getData('cc_trans_id'))
            ->setTransactionId($platformTransaction['id']);
        // Create transaction
        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_VOID);
        // This seems to be necessary to keep 1.5.1.x and 1.10.1.x from duplicating the void transaction
        $payment->setSkipTransactionCreation(true);

        return $this;
    }

    /**
     * Cancel the payment through gateway
     *
     * @param Mage_Payment_Model_Info|\Varien_Object $payment
     * @return $this
     */
    public function cancel(Varien_Object $payment)
    {
        // Log
        Mage::log('====== SFC_Autoship_Model_Payment_Method::cancel called ======', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        return $this->void($payment);
    }

    /**
     * Refund the amount with transaction id
     *
     * @param Mage_Payment_Model_Info|\Varien_Object $payment
     * @param float $requestedAmount
     * @return $this
     */
    public function refund(Varien_Object $payment, $requestedAmount)
    {
        // Log
        Mage::log('====== SFC_Autoship_Model_Payment_Method::refund called ======', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Set website / store for config on API helper
        $store = Mage::app()->getStore($payment->getOrder()->getStoreId());
        $apiHelper->setConfigStore($store);

        // Get order, etc from $payment
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();

        // Use API to create a new prior auth-capture transaction
        $platformTransaction = $vaultHelper->credit($payment->getCcTransId(), $requestedAmount, $order->getData('base_currency_code'));

        /**
         * Duplicate logic from standard Authorize.net payment method:
         * This means that we should close the parent transaction if we have refunded the full amount of original transaction
         */
        $shouldCloseRefundTransaction = 0;
        if ($this->_formatAmount($payment->getAmountPaid() - $payment->getAmountRefunded()) == $this->_formatAmount($requestedAmount)) {
            $shouldCloseRefundTransaction = 1;
        }

        // Save transaction details in $payment
        // Field cc_trans_id in payment should hold the single original (purchase or capture) trans id
        // (or just the single auth n capture trans id)
        $payment
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction($shouldCloseRefundTransaction)
            ->setParentTransactionId($payment->getData('cc_trans_id'))
            ->setTransactionId($platformTransaction['id']);
        // This seems to be necessary to keep 1.5.1.x and 1.10.1.x from causing "transaction already closed" error on 1.5.1.x and 1.10.1.x
        $payment->setSkipTransactionCreation(true);
        // Create transaction
        $payment->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);

        return $this;
    }


    protected function createNewPaymentToken(Mage_Sales_Model_Quote $quote, $data)
    {

        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        // Set website / store for config on API helper
        $apiHelper->setConfigStore($quote->getStore());
        // Call api to create new payment token
        $paymentToken = $vaultHelper->createPaymentToken(
            $quote->getBillingAddress(),
            $data['cc_number'],
            $data['cc_exp_month'],
            $data['cc_exp_year'],
            $data['cc_cid']
        );
        // Now obfuscate card number and store token
        $data['cc_number'] = $paymentToken['first_six_digits'] . 'XXXXXX' . $paymentToken['last_four_digits'];
        $data['cc_cid'] = 'XXX';
        $data['payment_token'] = $paymentToken['token'];
    }

    /**
     * @param Varien_Object $payment
     * @param \Mage_Customer_Model_Address_Abstract $billingAddress
     * @param $expMonth
     * @param $expYear
     * @return array|SFC_Autoship_Model_Payment_Profile
     * @throws Exception
     * @throws Mage_Core_Exception
     * @throws SFC_Autoship_Helper_PaymentError_Exception
     */
    protected function _createOrFetchPaymentProfile(Varien_Object $payment, Mage_Customer_Model_Address_Abstract $billingAddress, $expMonth, $expYear)
    {
        // Log
        Mage::log('_createOrFetchPaymentProfile()', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');

        // Get order, etc from $payment
        /** @var Mage_Sales_Model_Order $order */
        $order = $payment->getOrder();
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $order->getCustomer();

        try {
            // Get payment token
            $paymentToken = $payment->getAdditionalInformation('payment_token');
            // Check if this is a new card
            $isNewCard = $payment->getAdditionalInformation('is_new_card') == '1';
            // Get token details
            $paymentTokenDetails = $vaultHelper->getPaymentTokenDetails($paymentToken);
            $paymentTokenStatus = $paymentTokenDetails['storage_state'];
            // Check status of token and new card flag
            if ($isNewCard) {
                // This is a new card
                if ($paymentTokenStatus == 'cached') {
                    // This is a new card, its only cache at this point, store it
                    // Create or update customer on platform
                    $platformHelper->createOrUpdateCustomer($customer);
                    // Store token as new pay profile
                    $paymentProfile = $vaultHelper->storeToken($customer->getId(), $paymentToken, $billingAddress, $expMonth, $expYear);
                }
                else if ($paymentTokenStatus = 'retained') {
                    // This is a new card, must have already been stored in a failed checkout type
                    // Just try to fetch it
                    /** @var SFC_Autoship_Model_Payment_Profile $paymentProfile */
                    $paymentProfile = $vaultHelper->getPaymentProfileByToken($paymentToken);
                    // Now update billing address & exp date
                    $this->updatePaymentProfile($paymentProfile, $billingAddress, $expMonth, $expYear);
                }
                else {
                    throw new SFC_Autoship_Helper_PaymentError_Exception(Mage::helper('autoship')->__('Credit card information has expired!  Please reenter card details.'));
                }
            }
            else {
                // This is an existing card, just fetch it
                // Just try to fetch it
                $paymentProfile = $vaultHelper->getPaymentProfileByToken($paymentToken);
                if (!$paymentProfile->isThirdParty()) {
                    // Third party profiles can't be updated
                    // Now update billing address & exp date
                    $this->updatePaymentProfile($paymentProfile, $billingAddress, $expMonth, $expYear);
                }
            }
            if (!$paymentProfile->isThirdParty()) {
                // Third party profiles won't necessarily have the card type set
                // Update CC card type on payment record
                // Save cc type in $payment
                $ccType = $vaultHelper->mapSubscribeProCardTypeToMagento($paymentProfile->getData('creditcard_type'), false);
                if (strlen($ccType)) {
                    $payment->setCcType($ccType);
                }
            }

            // Save payment profile id in additional info
            $payment->setAdditionalInformation('payment_profile_id', $paymentProfile->getId());

            // Finally return the profile
            return $paymentProfile;
        }
        catch (SFC_Autoship_Helper_PaymentError_Exception $e) {
            // Rethrow these exceptions as the message will be customer friendly
            throw $e;
        }
        catch (Exception $e) {
            // Throw new exception with generic message
            Mage::throwException(Mage::helper('autoship')->__('Failed to store credit card for payment transaction!'));
        }
    }

    protected function updatePaymentProfile(SFC_Autoship_Model_Payment_Profile $paymentProfile, Mage_Customer_Model_Address_Abstract $billingAddress, $expMonth, $expYear)
    {
        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');

        // Now update billing address & exp date
        /** @var SFC_Autoship_Model_Payment_Profile_Address $paymentProfileBillingAddress */
        $paymentProfileBillingAddress = $paymentProfile->getData('billing_address');
        $paymentProfileBillingAddress->updateFromCustomerAddress($billingAddress);
        $paymentProfile->setData('creditcard_month', $expMonth);
        $paymentProfile->setData('creditcard_year', $expYear);
        $vaultHelper->updatePaymentProfile($paymentProfile);
    }

    /**
     * Round up and cast specified amount to float or string
     *
     * @param string|float $amount
     * @param bool $asFloat
     * @return string|float
     */
    protected function _formatAmount($amount, $asFloat = false)
    {
        $amount = sprintf('%.2F', $amount); // 'f' depends on locale, 'F' doesn't
        return $asFloat ? (float)$amount : $amount;
    }

}
