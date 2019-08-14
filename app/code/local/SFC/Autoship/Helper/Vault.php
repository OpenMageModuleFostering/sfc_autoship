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
 * Helper class to access Subscribe Pro Vault API
 */
class SFC_Autoship_Helper_Vault extends Mage_Core_Helper_Abstract
{

    /**
     * @param $customerEmail
     * @return array
     */
    public function getPaymentProfilesForCustomer($customerEmail)
    {
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'paymentprofiles',
            array('customer_email' => $customerEmail),
            'GET');
        // Check response
        if ($response['code'] != 200) {
            Mage::throwException($this->__('Failed to retrieve payment profiles!'));
        }

        // Return payment profile array from response
        return $this->parsePaymentProfileArrayFromResponse($response);
    }

    public function createPaymentProfile(SFC_Autoship_Model_Payment_Profile $profile)
    {
        // Build request
        $billingAddress = $profile->getData('billing_address');
        $requestData = array(
            'payment_profile' => array(
                'magento_customer_id' => $profile->getData('magento_customer_id'),
                'customer_email' => $profile->getData('customer_email'),
                'creditcard_type' => $profile->getData('creditcard_type'),
                'creditcard_number' => $profile->getData('creditcard_number'),
                'creditcard_month' => $profile->getData('creditcard_month'),
                'creditcard_year' => $profile->getData('creditcard_year'),
                'billing_address' => $billingAddress->getData(),
            ),
        );
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'paymentprofile',
            json_encode($requestData),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to create payment profile!');
        }

        // Return payment profile from response
        return $this->parsePaymentProfileFromResponse($response);
    }

    public function createPaymentToken(Mage_Customer_Model_Address_Abstract $billingAddress, $cardNumber, $expMonth, $expYear, $cvv = null)
    {
        // Get billing address
        $billingAddressModel = Mage::getModel('autoship/payment_profile_address')
            ->updateFromCustomerAddress($billingAddress);
        // Build request data
        $requestData = array(
            'token' => array(
                'creditcard_number' => $cardNumber,
                'creditcard_month' => $expMonth,
                'creditcard_year' => $expYear,
                'billing_address' => $billingAddressModel->getData(),
            ),
        );
        // Optionally add CVV
        if ($cvv != null && strlen($cvv)) {
            $requestData['token']['creditcard_verification_value'] = $cvv;
        }
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'token',
            json_encode($requestData),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to create payment token!');
        }

        // Return payment profile from response
        return $this->parseTokenFromResponse($response);
    }

    /**
     * @param $token
     * @return mixed
     */
    public function getPaymentTokenDetails($token)
    {
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'tokens/' . $token,
            array(),
            'GET');
        // Check response
        if ($response['code'] != 200) {
            $this->throwErrorResponseException($response, 'Failed to retrieve payment token details!');
        }

        // Return payment profile from response
        return $this->parseTokenFromResponse($response);
    }

    /**
     * @param $paymentProfileId
     * @return SFC_Autoship_Model_Payment_Profile
     */
    public function getPaymentProfile($paymentProfileId)
    {
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'paymentprofiles/' . $paymentProfileId,
            array(),
            'GET');
        // Check response
        if ($response['code'] != 200) {
            $this->throwErrorResponseException($response, 'Failed to retrieve payment profile!');
        }

        // Return payment profile from response
        return $this->parsePaymentProfileFromResponse($response);
    }

    /**
     * @param $paymentProfileId
     * @return SFC_Autoship_Model_Payment_Profile
     */
    public function getPaymentProfileByToken($paymentToken)
    {
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'tokens/' . $paymentToken . '/paymentprofile',
            array(),
            'GET');
        // Check response
        if ($response['code'] != 200) {
            $this->throwErrorResponseException($response, 'Failed to retrieve payment profile!');
        }

        // Return payment profile from response
        return $this->parsePaymentProfileFromResponse($response);
    }

    /**
     * @param string $magentoCustomerId
     * @param string $paymentToken
     * @param Mage_Customer_Model_Address_Abstract|null $billingAddress
     * @param null|string $expMonth
     * @param null|string $expYear
     * @return SFC_Autoship_Model_Payment_Profile
     */
    public function storeToken($magentoCustomerId, $paymentToken, Mage_Customer_Model_Address_Abstract $billingAddress = null, $expMonth = null, $expYear = null)
    {
        // Build request data
        $requestData = array(
            'payment_profile' => array(
                'magento_customer_id' => $magentoCustomerId,
            ),
        );
        // Optionally add billing address
        if ($billingAddress != null) {
            $billingAddressModel = Mage::getModel('autoship/payment_profile_address')
                ->updateFromCustomerAddress($billingAddress);
            $requestData['payment_profile']['billing_address'] = $billingAddressModel->getData();
        }
        // Optionally add expiration date
        if (strlen($expMonth) && strlen($expYear)) {
            $requestData['payment_profile']['creditcard_month'] = $expMonth;
            $requestData['payment_profile']['creditcard_year'] = $expYear;
        }
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'tokens/' . $paymentToken . '/store',
            json_encode($requestData),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to store payment card!');
        }

        // Return payment profile from response
        return $this->parsePaymentProfileFromResponse($response);
    }

    /**
     * @param string $magentoCustomerId
     * @param string $paymentToken
     * @param Mage_Customer_Model_Address_Abstract|null $billingAddress
     * @param null|string $expMonth
     * @param null|string $expYear
     * @return SFC_Autoship_Model_Payment_Profile
     */
    public function storeAndVerifyToken($magentoCustomerId, $paymentToken, Mage_Customer_Model_Address_Abstract $billingAddress = null, $expMonth = null, $expYear = null)
    {
        // Build request data
        $requestData = array(
            'payment_profile' => array(
                'magento_customer_id' => $magentoCustomerId,
            ),
        );
        // Optionally add billing address
        if ($billingAddress != null) {
            $billingAddressModel = Mage::getModel('autoship/payment_profile_address')
                ->updateFromCustomerAddress($billingAddress);
            $requestData['payment_profile']['billing_address'] = $billingAddressModel->getData();
        }
        // Optionally add expiration date
        if (strlen($expMonth) && strlen($expYear)) {
            $requestData['payment_profile']['creditcard_month'] = $expMonth;
            $requestData['payment_profile']['creditcard_year'] = $expYear;
        }
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'tokens/' . $paymentToken . '/verifyandstore',
            json_encode($requestData),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to verify and store payment card!');
        }

        // Return payment profile from response
        return $this->parsePaymentProfileFromResponse($response);
    }

    /**
     * @param SFC_Autoship_Model_Payment_Profile $paymentProfile
     * @return SFC_Autoship_Model_Payment_Profile
     */
    public function updatePaymentProfile(SFC_Autoship_Model_Payment_Profile $paymentProfile)
    {
        // Build request data
        // Only fields we can update are billing addy, cc expiration date
        $requestData = array(
            'payment_profile' => array(
                'creditcard_month' => $paymentProfile->getData('creditcard_month'),
                'creditcard_year' => $paymentProfile->getData('creditcard_year'),
                'billing_address' => $paymentProfile->getData('billing_address')->getData(),
            ),
        );
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'paymentprofiles/' . $paymentProfile->getData('id'),
            json_encode($requestData),
            'PUT');
        // Check response
        if ($response['code'] != 200) {
            $this->throwErrorResponseException($response, 'Failed to update payment profile!');
        }

        // Return payment profile from response
        return $this->parsePaymentProfileFromResponse($response);
    }

    /**
     * @param SFC_Autoship_Model_Payment_Profile $paymentProfile
     */
    public function redactPaymentProfile(SFC_Autoship_Model_Payment_Profile $paymentProfile)
    {
        return $this->redactPaymentProfileId($paymentProfile->getData('id'));
    }

    /**
     * @param string $paymentProfileId
     */
    public function redactPaymentProfileId($paymentProfileId)
    {
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'paymentprofiles/' . $paymentProfileId . '/redact',
            json_encode(array()),
            'PUT');
        // Check response
        if ($response['code'] != 200) {
            $this->throwErrorResponseException($response, 'Failed to redact payment profile!');
        }
    }

    public function purchase(SFC_Autoship_Model_Payment_Profile $paymentProfile, $amount, $currencyCode, $additionalFields = array())
    {
        // Build request data
        $requestData = array(
            'transaction' => $additionalFields,
        );
        $requestData['transaction']['amount'] = number_format(100 * $amount, 0, "", "");
        $requestData['transaction']['currency_code'] = $currencyCode;

        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'paymentprofiles/' . $paymentProfile->getData('id') . '/purchase',
            json_encode($requestData),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to purchase!');
        }
        // Parse response
        $platformTransaction = $this->parseTransactionFromResponse($response);
        // Check transaction state in response
        if ($platformTransaction['state'] != 'succeeded') {
            $this->throwTransactionErrorException($platformTransaction, 'Failed to purchase!');
        }

        return $platformTransaction;
    }

    public function purchaseOneTime($paymentToken, $amount, $currencyCode, $additionalFields = array(), Mage_Customer_Model_Address_Abstract $billingAddress = null, $expMonth = null, $expYear = null)
    {
        // Build request data
        $requestData = array(
            'transaction' => $additionalFields,
        );
        $requestData['transaction']['amount'] = number_format(100 * $amount, 0, "", "");
        $requestData['transaction']['currency_code'] = $currencyCode;
        // Optionally add billing address
        if ($billingAddress != null) {
            $billingAddressModel = Mage::getModel('autoship/payment_profile_address')
                ->updateFromCustomerAddress($billingAddress);
            $requestData['transaction']['billing_address'] = $billingAddressModel->getData();
        }
        // Optionally add expiration date
        if (strlen($expMonth) && strlen($expYear)) {
            $requestData['transaction']['creditcard_month'] = $expMonth;
            $requestData['transaction']['creditcard_year'] = $expYear;
        }
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'tokens/' . $paymentToken . '/purchase',
            json_encode($requestData),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to purchase!');
        }
        // Parse response
        $platformTransaction = $this->parseTransactionFromResponse($response);
        // Check transaction state in response
        if ($platformTransaction['state'] != 'succeeded') {
            $this->throwTransactionErrorException($platformTransaction, 'Failed to purchase!');
        }

        return $platformTransaction;
    }

    public function authorize(SFC_Autoship_Model_Payment_Profile $paymentProfile, $amount, $currencyCode, $additionalFields = array())
    {
        // Build request data
        $requestData = array(
            'transaction' => $additionalFields,
        );
        $requestData['transaction']['amount'] = number_format(100 * $amount, 0, "", "");
        $requestData['transaction']['currency_code'] = $currencyCode;
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'paymentprofiles/' . $paymentProfile->getData('id') . '/authorize',
            json_encode($requestData),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to authorize!');
        }
        // Parse response
        $platformTransaction = $this->parseTransactionFromResponse($response);
        // Check transaction state in response
        if ($platformTransaction['state'] != 'succeeded') {
            $this->throwTransactionErrorException($platformTransaction, 'Failed to authorize!');
        }

        return $platformTransaction;
    }

    public function authorizeOneTime($paymentToken, $amount, $additionalFields = array(), $currencyCode, Mage_Customer_Model_Address_Abstract $billingAddress = null, $expMonth = null, $expYear = null)
    {
        // Build request data
        $requestData = array(
            'transaction' => $additionalFields,
        );
        $requestData['transaction']['amount'] = number_format(100 * $amount, 0, "", "");
        $requestData['transaction']['currency_code'] = $currencyCode;
        // Optionally add billing address
        if ($billingAddress != null) {
            $billingAddressModel = Mage::getModel('autoship/payment_profile_address')
                ->updateFromCustomerAddress($billingAddress);
            $requestData['transaction']['billing_address'] = $billingAddressModel->getData();
        }
        // Optionally add expiration date
        if (strlen($expMonth) && strlen($expYear)) {
            $requestData['transaction']['creditcard_month'] = $expMonth;
            $requestData['transaction']['creditcard_year'] = $expYear;
        }
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'tokens/' . $paymentToken . '/authorize',
            json_encode($requestData),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to authorize!');
        }
        // Parse response
        $platformTransaction = $this->parseTransactionFromResponse($response);
        // Check transaction state in response
        if ($platformTransaction['state'] != 'succeeded') {
            $this->throwTransactionErrorException($platformTransaction, 'Failed to authorize!');
        }

        return $platformTransaction;
    }

    /**
     * @param $transactionId
     * @param int $amount Amount for partial capture, 0 for full capture
     * @param string $currencyCode
     * @return array
     */
    public function capture($transactionId, $amount = 0, $currencyCode = 'USD')
    {
        // Build request data
        if ($amount > 0.0) {
            $requestData = array(
                'transaction' => array(
                    'amount' => number_format(100 * $amount, 0, "", ""),
                    'currency_code' => $currencyCode,
                ),
            );
        }
        else {
            $requestData = array();
        }
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'transactions/' . $transactionId . '/capture',
            json_encode($requestData),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to capture!');
        }
        // Parse response
        $platformTransaction = $this->parseTransactionFromResponse($response);
        // Check transaction state in response
        if ($platformTransaction['state'] != 'succeeded') {
            $this->throwTransactionErrorException($platformTransaction, 'Failed to capture!');
        }

        return $platformTransaction;
    }

    public function verify(SFC_Autoship_Model_Payment_Profile $paymentProfile, $currencyCode, $additionalFields = array())
    {
        // Build request data
        $requestData = array(
            'transaction' => $additionalFields,
        );
        $requestData['transaction']['currency_code'] = $currencyCode;
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'paymentprofiles/' . $paymentProfile->getData('id') . '/verify',
            json_encode($requestData),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to verify!');
        }
        // Parse response
        $platformTransaction = $this->parseTransactionFromResponse($response);
        // Check transaction state in response
        if ($platformTransaction['state'] != 'succeeded') {
            $this->throwTransactionErrorException($platformTransaction, 'Failed to verify!');
        }

        return $platformTransaction;
    }

    /**
     * @param $transactionId
     * @param int $amount Amount for partial capture, 0 for full capture
     * @param string $currencyCode
     * @return array
     */
    public function credit($transactionId, $amount = 0, $currencyCode = 'USD')
    {
        // Build request data
        if ($amount > 0.0) {
            $requestData = array(
                'transaction' => array(
                    'amount' => number_format(100 * $amount, 0, "", ""),
                    'currency_code' => $currencyCode,
                ),
            );
        }
        else {
            $requestData = array();
        }
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'transactions/' . $transactionId . '/credit',
            json_encode($requestData),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to credit!');
        }
        // Parse response
        $platformTransaction = $this->parseTransactionFromResponse($response);
        // Check transaction state in response
        if ($platformTransaction['state'] != 'succeeded') {
            $this->throwTransactionErrorException($platformTransaction, 'Failed to credit!');
        }

        return $platformTransaction;
    }

    /**
     * @param $transactionId
     * @return array
     */
    public function void($transactionId)
    {
        // Make request
        $response = $this->getApiHelper()->makeVaultRequest(
            'transactions/' . $transactionId . '/void',
            json_encode(array()),
            'POST');
        // Check response
        if ($response['code'] != 201) {
            $this->throwErrorResponseException($response, 'Failed to void!');
        }
        // Parse response
        $platformTransaction = $this->parseTransactionFromResponse($response);
        // Check transaction state in response
        if ($platformTransaction['state'] != 'succeeded') {
            $this->throwTransactionErrorException($platformTransaction, 'Failed to void!');
        }

        return $platformTransaction;
    }

    protected function getAllCardTypeMappings()
    {
        // Map of card types
        // Subscribe Pro / Spreedly type => Magento type
        $cardTypes = array(
            'visa' => 'VI',
            'master' => 'MC',
            'american_express' => 'AE',
            'discover' => 'DI',
            'jcb' => 'JCB',
        );

        return $cardTypes;
    }

    public function mapMagentoCardTypeToSubscribePro($type, $throwExceptionOnTypeNotFound = true)
    {
        // Map of card types
        $cardTypes = $this->getAllCardTypeMappings();
        $cardTypes = array_flip($cardTypes);

        if (isset($cardTypes[$type])) {
            return $cardTypes[$type];
        }
        else {
            if ($throwExceptionOnTypeNotFound) {
                Mage::throwException($this->__('Invalid credit card type: %s', $type));
            }
            else {
                return null;
            }
        }
    }

    public function mapSubscribeProCardTypeToMagento($type, $throwExceptionOnTypeNotFound = true)
    {
        // Map of card types
        $cardTypes = $this->getAllCardTypeMappings();

        if (isset($cardTypes[$type])) {
            return $cardTypes[$type];
        }
        else {
            if ($throwExceptionOnTypeNotFound) {
                Mage::throwException($this->__('Invalid credit card type: ' . $type));
            }
            else {
                return null;
            }
        }
    }

    /**
     * @return SFC_Autoship_Helper_Api
     */
    protected function getApiHelper()
    {
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        return $apiHelper;
    }

    /**
     * @param array $response
     * @return SFC_Autoship_Model_Payment_Profile
     */
    protected function parsePaymentProfileFromResponse(array $response)
    {
        // Check that response is well formed
        if (!isset($response['result']) ||
            !isset($response['result']['payment_profile']) ||
            !is_array($response['result']['payment_profile'])
        ) {
            Mage::throwException($this->__('API response format error for payment profile!'));
        }
        $result = $response['result'];
        $platformPaymentProfile = $result['payment_profile'];
        // Convert response into a model
        $paymentProfile = SFC_Autoship_Model_Payment_Profile::makePaymentProfileFromVaultData($platformPaymentProfile);

        return $paymentProfile;
    }

    protected function parsePaymentProfileArrayFromResponse(array $response)
    {
        // Check that response is well formed
        if (!isset($response['result']) ||
            !isset($response['result']['payment_profiles']) ||
            !is_array($response['result']['payment_profiles'])
        ) {
            Mage::throwException('API response format error for payment profiles!');
        }
        $result = $response['result'];
        $platformPaymentProfiles = $result['payment_profiles'];
        // Iterate through payment profiles, convert each
        $paymentProfiles = array();
        foreach ($platformPaymentProfiles as $platformPaymentProfile) {
            // Convert response into a model
            $paymentProfile = SFC_Autoship_Model_Payment_Profile::makePaymentProfileFromVaultData($platformPaymentProfile);
            // Add model to array
            $paymentProfiles[] = $paymentProfile;
        }

        return $paymentProfiles;
    }

    /**
     * @param array $response
     * @return SFC_Autoship_Model_Payment_Profile
     */
    protected function parseTransactionFromResponse(array $response)
    {
        // Check that response is well formed
        if (!isset($response['result']) ||
            !isset($response['result']['transaction']) ||
            !is_array($response['result']['transaction'])
        ) {
            Mage::throwException($this->__('API response format error for transaction!'));
        }
        $result = $response['result'];
        $platformTransaction = $result['transaction'];

        if (isset($platformTransaction['state']) && $platformTransaction['state'] != 'succeeded') {
            // Build error detail string
            $errorDetailString = '';
            if (isset($platformTransaction['id'])) {
                $errorDetailString .= 'Transaction ID: ' . $platformTransaction['id'] . "\n";
            }
            if (isset($platformTransaction['response_message'])) {
                $errorDetailString .= 'Response Message: ' . $platformTransaction['response_message'];
            }
            // Save error details in registry, so we can return it later
            Mage::unregister('subscribepro_transaction_error_detail');
            Mage::register('subscribepro_transaction_error_detail', $errorDetailString);
        }

        return $platformTransaction;
    }

    /**
     * @param array $response
     * @return SFC_Autoship_Model_Payment_Profile
     */
    protected function parseTokenFromResponse(array $response)
    {
        // Check that response is well formed
        if (!isset($response['result']) ||
            !isset($response['result']['token']) ||
            !is_array($response['result']['token'])
        ) {
            Mage::throwException($this->__('API response format error for token!'));
        }
        $result = $response['result'];
        $token = $result['token'];

        return $token;
    }

    /**
     * @param array $response
     * @param string $defaultMessage
     * @throws Mage_Core_Exception
     * @throws SFC_Autoship_Helper_PaymentError_Exception
     */
    protected function throwErrorResponseException(array $response, $defaultMessage)
    {
        /** @var SFC_Autoship_Helper_PaymentError $errorHelper */
        $errorHelper = Mage::helper('autoship/paymentError');
        // Check that response is well formed
        if (isset($response['result']) &&
            isset($response['result']['errors']) &&
            is_array($response['result']['errors'])
        ) {
            // Get 1st error
            $error = $response['result']['errors'][0];
            if (isset($error['attribute']) && $error['key']) {
                // Get attribute & key
                $attribute = $error['attribute'];
                $key = $error['key'];
                // If attribute and key translate to an error message, throw it in exception
                $message = $errorHelper->getCreditCardErrorMessage($attribute, $key);
                if (strlen($message)) {
                    throw new SFC_Autoship_Helper_PaymentError_Exception($message);
                }
            }
        }

        // Otherwise throw default message
        Mage::throwException($this->__($defaultMessage));
    }

    protected function throwTransactionErrorException(array $platformTransaction, $defaultMessage)
    {
        /** @var SFC_Autoship_Helper_PaymentError $errorHelper */
        $errorHelper = Mage::helper('autoship/paymentError');
        // Check that response is well formed and has error type
        if (isset($platformTransaction['subscribe_pro_error_type'])) {
            // Map error type
            $message = $errorHelper->getGatewayErrorMessage($platformTransaction['subscribe_pro_error_type']);
            if (strlen($message)) {
                throw new SFC_Autoship_Helper_PaymentError_Exception($message);
            }
        }

        // Otherwise throw default message
        Mage::throwException($this->__($defaultMessage));
    }

}
