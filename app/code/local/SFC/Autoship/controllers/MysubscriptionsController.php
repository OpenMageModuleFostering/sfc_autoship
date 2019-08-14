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
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2009-2014 SUBSCRIBE PRO INC. All Rights Reserved.
 * @license   http://www.subscribepro.com/terms-of-service/ Subscribe Pro Terms of Service
 * @link      http://www.subscribepro.com/
 *
 */

/**
 * Controller to handle the My Subscriptions page in the customer account dashboard section of the frontend
 */
class SFC_Autoship_MysubscriptionsController extends Mage_Core_Controller_Front_Action
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
        // Check if extension enabled
        if (Mage::getStoreConfig('autoship_general/general/enabled') != '1') {
            // Send customer to 404 page
            $this->_forward('defaultNoRoute');
            return;
        }
    }

    /**
     * Customer Dashboard - My Product Subscriptions page
     */
    public function indexAction()
    {
        // Load layout from XML
        $this->loadLayout();

        // Set page title for this page
        $this->getLayout()->getBlock('head')->setTitle($this->__('My Subscriptions'));

        // Render the layout
        $this->renderLayout();
    }

    /**
     * Update interval, qty or next order date action
     */
    public function changeAction()
    {
        try {
            // Get POST data
            $postData = $this->validateChangePostData();

            // Get customer
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            // Call platform to get subscription(s)
            if($postData['subscription_id'] == 'all_active') {
                $subscriptions = Mage::helper('autoship/platform')->getSubscriptions(
                    $customer,
                    array(
                        'status' => 'Active',
                    ));
                $firstSubscription = $subscriptions[0];
            }
            else {
                $firstSubscription = Mage::helper('autoship/platform')->getSubscription($postData['subscription_id']);
                $subscriptions[] = $firstSubscription;
                // Modify qty, interval
                if (isset($postData['interval'])) {
                    $firstSubscription->setInterval($postData['interval']);
                }
                if (isset($postData['qty'])) {
                    $firstSubscription->setQty($postData['qty']);
                }
            }

            // Modify next order date
            if (isset($postData['delivery_date'])) {
                foreach($subscriptions as $subscription) {
                    $subscription->setNextOrderDate($postData['delivery_date']);
                }
            }
            // Send changes to platform
            foreach($subscriptions as $subscription) {
                $newSubscriptionId = Mage::helper('autoship/platform')->updateSubscription($subscription->getSubscriptionId(), $subscription);
            }
            // Return the rendered html for this new subscription state
            if($postData['subscription_id'] == 'all_active') {
                echo $this->getLayout()->createBlock('autoship/mysubscriptions')
                    ->setTemplate('autoship/mysubscriptions.phtml')
                    ->toHtml();
            }
            else {
                echo $this->getLayout()->createBlock('autoship/mysubscriptions_subscription')
                    ->setTemplate('autoship/mysubscriptions/subscription.phtml')
                    ->setSubscription($firstSubscription)
                    ->toHtml();
            }
        }
        catch (Exception $e) {
            $this->handleAjaxException($e);
        }
    }

    public function changeSkuAction()
    {
        try {
            $data = $this->getRequest()->getPost();

            if (isset($data['product_sku']) && strlen($data['product_sku'])) {
                //Ensure we have a valid product that is active for subscriptions
                $product = Mage::getModel("catalog/product")->loadByAttribute('sku', $data['product_sku']);
                if ($product->getId() && $product->getData('subscription_enabled')) {
                    $data['product_id'] = $product->getId();
                } else {
                    Mage::throwException($this->__("Please supply a valid product sku enabled for subscriptions."));
                }
            }

            if (isset($data['id'])) {
                $data['subscription_id'] = $data['id'];
            }

            if (!isset($data['subscription_id'])) {
                Mage::throwException($this->__("Please supply a valid subscription id."));
            }
            $subscription = Mage::helper("autoship/platform")->getSubscription($data['subscription_id']);

            //Update the SKU data
            $subscription
                ->setProductSku($data['product_sku'])
                ->setProductId($data['product_id']);

            //Update the subscription
            Mage::helper('autoship/platform')->updateSubscription($subscription->getSubscriptionId(), $subscription);

            echo $this->getLayout()->createBlock('autoship/mysubscriptions_subscription')
                ->setTemplate('autoship/mysubscriptions/subscription.phtml')
                ->setSubscription($subscription)
                ->toHtml();
        } Catch (Exception $e) {
            $this->handleAjaxException($e);
        }
    }


    /**
     * Retrieve post data for changeAction and validate it
     */
    protected function validateChangePostData()
    {
        $data = $this->getRequest()->getPost();
        if (!is_array($data)) {
            Mage::throwException('Failed to process POST data!');
        }
        // Validate POST data and return in array
        $validatedPostData = array();
        if (isset($data['id']) && strlen($data['id'])) {
            $validatedPostData['subscription_id'] = $data['id'];
        }
        if (isset($data['delivery_qty']) && strlen($data['delivery_qty'])) {
            if (!is_numeric($data['delivery_qty'])) {
                Mage::throwException('Please specify a numeric value for subscription quantity!');
            }
            $validatedPostData['qty'] = $data['delivery_qty'];
        }
        if (isset($data['delivery_interval']) && strlen($data['delivery_interval'])) {
            $validatedPostData['interval'] = $data['delivery_interval'];
        }
        if (isset($data['delivery_date']) && strlen($data['delivery_date'])) {
            $validatedPostData['delivery_date'] = date('Y-m-d', strtotime($data['delivery_date']));
        }
        if (isset($data['shipping_address_id']) && strlen($data['shipping_address_id'])) {
            if (is_numeric($data['shipping_address_id'])) {
                $validatedPostData['shipping_address_id'] = $data['shipping_address_id'];
            }
        }
        if (isset($data['billing_address_id']) && strlen($data['billing_address_id'])) {
            if (is_numeric($data['billing_address_id'])) {
                $validatedPostData['billing_address_id'] = $data['billing_address_id'];
            }
        }
        if (isset($data['payment']) && is_array($data['payment'])) {
            $validatedPostData['payment'] = $data['payment'];
        }
        if (isset($data['shipping']) && is_array($data['shipping'])) {
            $validatedPostData['shipping'] = $data['shipping'];
        }
        if (isset($data['billing']) && is_array($data['billing'])) {
            $validatedPostData['billing'] = $data['billing'];
        }

        // Return validate POST data
        return $validatedPostData;
    }

    /**
     * Save payment method action
     */
    public function paymentsaveAction()
    {
        try {
            /** @var SFC_Autoship_Helper_Platform $platformHelper */
            $platformHelper = Mage::helper('autoship/platform');
            // Get config setting
            $nextOrderDateMode = Mage::getStoreConfig('autoship_subscription/options/next_order_date_mode');
            // Get customer
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            // Get POST data
            $postData = $this->validateChangePostData();
            if($nextOrderDateMode != SFC_Autoship_Model_System_Config_Source_Nextorderdatemode::MODE_MULTIPLE_DATES) {
                $subscriptions = $platformHelper->getSubscriptions(
                    $customer,
                    array(
                        'status' => 'Active',
                    ));
                $firstSubscription = $subscriptions[0];
            }
            else {
                $firstSubscription = $platformHelper->getSubscription($postData['subscription_id']);
                $subscriptions[] = $firstSubscription;
            }

            // Get payment POST data
            $data = $postData['payment'];
            // switched saved card
            if ($data['method'] != 'new_cc') {
                $paymentToken = $data['method'];
                $creditcardLastDigits = $data['creditcard_last_digits'][$data['method']];
            }
            else {
                // Create and save new profile to CIM and DB
                $result = $this->createNewPaymentProfile($firstSubscription, $data);
                $paymentToken = $result['payment_token'];
                $creditcardLastDigits = $result['creditcard_last_digits'];
            }
            // Now set profile id on subscription
            // Set shipping address on subscriptions
            foreach($subscriptions as $subscription) {
                $subscription->setData('payment_method_code', $platformHelper->getConfiguredPaymentMethodCode());
                $subscription->setData('payment_token', $paymentToken);
                $subscription->setData('customer_cardnumber', $creditcardLastDigits);
                $subscription->setData('creditcard_last_digits', $creditcardLastDigits);
            }

            // Send changes to platform
            foreach($subscriptions as $subscription) {
                $newSubscriptionId = $platformHelper->updateSubscription($subscription->getSubscriptionId(), $subscription);
            }
            // Return the rendered html for this new subscription state
            if($nextOrderDateMode != SFC_Autoship_Model_System_Config_Source_Nextorderdatemode::MODE_MULTIPLE_DATES) {
                echo $this->getLayout()->createBlock('autoship/mysubscriptions')
                    ->setTemplate('autoship/mysubscriptions.phtml')
                    ->toHtml();
            }
            else {
                // Re-fetch subscription from platform
                $updatedSubscription = $platformHelper->getSubscription($newSubscriptionId);
                echo $this->getLayout()->createBlock('autoship/mysubscriptions_subscription')
                    ->setTemplate('autoship/mysubscriptions/subscription.phtml')
                    ->setSubscription($updatedSubscription)
                    ->toHtml();
            }
        }
        catch (Exception $e) {
            $this->handleAjaxException($e);
        }
    }

    /**
     * Save shipping address action
     */
    public function shippingsaveAction()
    {
        try {
            /** @var SFC_Autoship_Helper_Platform $platformHelper */
            $platformHelper = Mage::helper('autoship/platform');
            // Get config setting
            $nextOrderDateMode = Mage::getStoreConfig('autoship_subscription/options/next_order_date_mode');
            // Get customer
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            // Get POST data
            $postData = $this->validateChangePostData();
            if($nextOrderDateMode != SFC_Autoship_Model_System_Config_Source_Nextorderdatemode::MODE_MULTIPLE_DATES) {
                $subscriptions = $platformHelper->getSubscriptions(
                    $customer,
                    array(
                        'status' => 'Active',
                    ));
                $firstSubscription = $subscriptions[0];
            }
            else {
                $firstSubscription = $platformHelper->getSubscription($postData['subscription_id']);
                $subscriptions[] = $firstSubscription;
            }
            
            if (!empty($postData['shipping_address_id'])) {
                // change saved address
                $addressId = $postData['shipping_address_id'];
            }
            else {
                // add new address
                $addressId = $this->saveAddress($postData['shipping']);
            }
            if (!is_numeric($addressId)) {
                Mage::throwException('Failed to save address!');
            }
            // Set shipping address on subscriptions
            foreach($subscriptions as $subscription) {
                $subscription->setShippingAddressId($addressId);
            }

            // Send changes to platform
            foreach($subscriptions as $subscription) {
                $newSubscriptionId = Mage::helper('autoship/platform')->updateSubscription($subscription->getSubscriptionId(), $subscription);
            }
            // Return the rendered html for this new subscription state
            if($nextOrderDateMode != SFC_Autoship_Model_System_Config_Source_Nextorderdatemode::MODE_MULTIPLE_DATES) {
                echo $this->getLayout()->createBlock('autoship/mysubscriptions')
                    ->setTemplate('autoship/mysubscriptions.phtml')
                    ->toHtml();
            }
            else {
                // Re-fetch subscription from platform
                $updatedSubscription = $platformHelper->getSubscription($newSubscriptionId);
                echo $this->getLayout()->createBlock('autoship/mysubscriptions_subscription')
                    ->setTemplate('autoship/mysubscriptions/subscription.phtml')
                    ->setSubscription($updatedSubscription)
                    ->toHtml();
            }
        }
        catch (Exception $e) {
            $this->handleAjaxException($e);
        }
    }

    /**
     * Save billing address action
     */
    public function billingsaveAction()
    {
        try {
            /** @var SFC_Autoship_Helper_Platform $platformHelper */
            $platformHelper = Mage::helper('autoship/platform');
            // Get config setting
            $nextOrderDateMode = Mage::getStoreConfig('autoship_subscription/options/next_order_date_mode');
            // Get customer
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            // Get POST data
            $postData = $this->validateChangePostData();
            if($nextOrderDateMode != SFC_Autoship_Model_System_Config_Source_Nextorderdatemode::MODE_MULTIPLE_DATES) {
                $subscriptions = $platformHelper->getSubscriptions(
                    $customer,
                    array(
                        'status' => 'Active',
                    ));
                $firstSubscription = $subscriptions[0];
            }
            else {
                $firstSubscription = $platformHelper->getSubscription($postData['subscription_id']);
                $subscriptions[] = $firstSubscription;
            }

            if (!empty($postData['billing_address_id'])) {
                // change saved address
                $addressId = $postData['billing_address_id'];
            }
            else {
                // add new address
                $addressId = $this->saveAddress($postData['billing']);
            }
            if (!is_numeric($addressId)) {
                Mage::throwException('Failed to save address!');
            }
            // Set billing_address_id address on subscriptions
            foreach($subscriptions as $subscription) {
                $subscription->setBillingAddressId($addressId);
            }

            // Send changes to platform
            foreach($subscriptions as $subscription) {
                $newSubscriptionId = $platformHelper->updateSubscription($subscription->getSubscriptionId(), $subscription);
            }
            // Return the rendered html for this new subscription state
            if($nextOrderDateMode != SFC_Autoship_Model_System_Config_Source_Nextorderdatemode::MODE_MULTIPLE_DATES) {
                echo $this->getLayout()->createBlock('autoship/mysubscriptions')
                    ->setTemplate('autoship/mysubscriptions.phtml')
                    ->toHtml();
            }
            else {
                // Re-fetch subscription from platform
                $updatedSubscription = $platformHelper->getSubscription($newSubscriptionId);
                // Output subscription block for this subscription only
                echo $this->getLayout()->createBlock('autoship/mysubscriptions_subscription')
                    ->setTemplate('autoship/mysubscriptions/subscription.phtml')
                    ->setSubscription($updatedSubscription)
                    ->toHtml();
            }

        }
        catch (Exception $e) {
            $this->handleAjaxException($e);
        }
    }

    /**
     * Skip the next delivery action
     */
    public function skipAction()
    {
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
        try {
            // Get subscription id from request
            $subscriptionId = $this->getRequest()->getParam('id');
            // Call API to delete subscription
            $platformHelper->skipSubscription($subscriptionId);
            // Now call platform to get subscription again
            $subscription = $platformHelper->getSubscription($subscriptionId);
            // Return the rendered html for this new subscription state
            echo $this->getLayout()->createBlock('autoship/mysubscriptions_subscription')
                ->setTemplate('autoship/mysubscriptions/subscription.phtml')
                ->setSubscription($subscription)
                ->toHtml();
        }
        catch (Exception $e) {
            $this->handleAjaxException($e);
        }
    }

    /**
     * Cancel subscription action
     *
     */
    public function cancelAction()
    {
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
        try {
            // Get subscription id from request
            $subscriptionId = $this->getRequest()->getParam('id');
            // Call API to delete subscription
            $platformHelper->cancelSubscription($subscriptionId);
            // Now call platform to get subscription again
            $subscription = $platformHelper->getSubscription($subscriptionId);
            // Return the rendered html for this new subscription state
            echo $this->getLayout()->createBlock('autoship/mysubscriptions_subscription')
                ->setTemplate('autoship/mysubscriptions/subscription.phtml')
                ->setSubscription($subscription)
                ->toHtml();
        }
        catch (Exception $e) {
            $this->handleAjaxException($e);
        }
    }

    /**
     * Pause subscription action
     *
     */
    public function pauseAction()
    {
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
        try {
            // Get subscription id from request
            $subscriptionId = $this->getRequest()->getParam('id');
            // Call API to delete subscription
            $platformHelper->pauseSubscription($subscriptionId);
            // Now call platform to get subscription again
            $subscription = $platformHelper->getSubscription($subscriptionId);
            // Return the rendered html for this new subscription state
            echo $this->getLayout()->createBlock('autoship/mysubscriptions_subscription')
                ->setTemplate('autoship/mysubscriptions/subscription.phtml')
                ->setSubscription($subscription)
                ->toHtml();
        }
        catch (Exception $e) {
            $this->handleAjaxException($e);
        }
    }

    /**
     * Restart subscription action
     *
     */
    public function restartAction()
    {
        /** @var SFC_Autoship_Helper_Platform $platformHelper */
        $platformHelper = Mage::helper('autoship/platform');
        try {
            // Get subscription id from request
            $subscriptionId = $this->getRequest()->getParam('id');
            // Call API to delete subscription
            $platformHelper->restartSubscription($subscriptionId);
            // Now call platform to get subscription again
            $subscription = $platformHelper->getSubscription($subscriptionId);
            // Return the rendered html for this new subscription state
            echo $this->getLayout()->createBlock('autoship/mysubscriptions_subscription')
                ->setTemplate('autoship/mysubscriptions/subscription.phtml')
                ->setSubscription($subscription)
                ->toHtml();
        }
        catch (Exception $e) {
            $this->handleAjaxException($e);
        }
    }

    /**
     * Create a new gateway payment profile in Magento DB and on gateway
     *
     * @param SFC_Autoship_Model_Subscription $subscription Subscription for which to create payment profile
     * @param array $data Array of profile details from POST
     * @return SFC_AuthnetToken_Model_Cim_Payment_Profile Newly created payment profile
     */
    protected function createNewPaymentProfile(SFC_Autoship_Model_Subscription $subscription, array $data)
    {
        // Get helpers
        /** @var SFC_Autoship_Helper_Platform $helperPlatform */
        $helperPlatform = Mage::helper('autoship/platform');

        // Get customer
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        // Check the pay method
        switch($helperPlatform->getConfiguredPaymentMethodCode())
        {
            default:
            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_BRAINTREE:
            return array(
                'payment_token' => null,
                'creditcard_last_digits' => null,
            );

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CIM:
                // Create new payment profile model
                $model = Mage::getModel('sfc_cim_core/cim_payment_profile');
                // Init profile with customer defaults
                $model->initCimProfileWithCustomerDefault($customer->getId());
                // Adjust the exp fields to the proper format
                $data['exp_date'] = $data['cc_exp_year'] . '-' . sprintf('%02d', $data['cc_exp_month']);
                // Put other fields into proper field names for savCimProfileData method
                $data['customer_cardnumber'] = $data['cc_number'];
                // If customer has selected billing address, add billing address data to new CIM profile
                if (strlen($subscription->getBillingAddressId())) {
                    $billingAddress = Mage::getModel('customer/address')->load($subscription->getBillingAddressId());
                    $model->setBillingAddressFields($billingAddress);
                }
                // Set attributes that can be saved in our DB & Authorize.Net CIM
                $model->addData($data);
                try {
                    // Now try to save payment profile to Auth.net CIM
                    $model->saveCimProfileData(true);
                    // Save our profile model to DB
                    $model->save();
                }
                catch (Exception $e) {
                    Mage::throwException($this->__('Failed to save credit card!'));
                }

                // Return new model
                return array(
                    'payment_token' => $model->getData('cim_payment_profile_id'),
                    'creditcard_last_digits' => $model->getData('customer_cardnumber'),
                );

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CIM_10XX:
                // Create new payment profile model
                $model = Mage::getModel('authnettoken/cim_payment_profile');
                // Init profile with customer defaults
                $model->initCimProfileWithCustomerDefault($customer->getId());
                // Adjust the exp fields to the proper format
                $data['exp_date'] = $data['cc_exp_year'] . '-' . sprintf('%02d', $data['cc_exp_month']);
                // Put other fields into proper field names for savCimProfileData method
                $data['customer_cardnumber'] = $data['cc_number'];
                // If customer has selected billing address, add billing address data to new CIM profile
                if (strlen($subscription->getBillingAddressId())) {
                    $billingAddress = Mage::getModel('customer/address')->load($subscription->getBillingAddressId());
                    $model->setBillingAddressFields($billingAddress);
                }
                // Set attributes that can be saved in our DB & Authorize.Net CIM
                $model->addData($data);
                try {
                    // Now try to save payment profile to Auth.net CIM
                    $model->saveCimProfileData(true);
                    // Save our profile model to DB
                    $model->save();
                }
                catch (Exception $e) {
                    Mage::throwException($this->__('Failed to save credit card!'));
                }

                // Return new model
                return array(
                    'payment_token' => $model->getData('cim_payment_profile_id'),
                    'creditcard_last_digits' => $model->getData('customer_cardnumber'),
                );

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SUBSCRIBE_PRO_VAULT:
                // TO DO: Implement this
                // Return new details
                return array(
                    'payment_token' => 'XXXXXX',
                    'creditcard_last_digits' => 'XXXX',
                );

            case SFC_Autoship_Helper_Platform::PAY_METHOD_CODE_SFC_CYBERSOURCE:
                // Create new payment profile model
                $model = Mage::getModel('sfc_cybersource/payment_profile');
                // Init profile with customer defaults
                $model->initProfileWithCustomerDefault($customer->getId());
                // Put fields into proper field names for save method
                $data['customer_cardnumber'] = $data['cc_number'];
                // If customer has selected billing address, add billing address data to new profile
                if (strlen($subscription->getBillingAddressId())) {
                    $billingAddress = Mage::getModel('customer/address')->load($subscription->getBillingAddressId());
                    $model->setBillingAddressFields($billingAddress);
                }
                // Set attributes that can be saved in our DB & Gateway
                $model->addData($data);
                try {
                    // Now try to save payment profile to Gateway
                    $model->saveProfileData();
                    // Save our profile model to DB
                    $model->save();
                }
                catch (Exception $e) {
                    Mage::throwException($this->__('Failed to save credit card!'));
                }

                // Return new model
                return array(
                    'payment_token' => $model->getData('payment_token'),
                    'creditcard_last_digits' => $model->getData('customer_cardnumber'),
                );
        }
    }

    /**
     * Save a customer address to customer address book in Magento DB
     * @param array $addressData
     * @return mixed|string
     */
    protected function saveAddress(array $addressData)
    {
        // Array to hold errors
        $errors = array();
        // Get customer from session
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        /* @var $address Mage_Customer_Model_Address */
        $address = Mage::getModel('customer/address');
        /* @var $addressForm Mage_Customer_Model_Form */
        $addressForm = Mage::getModel('customer/form');
        $addressForm->setFormCode('customer_address_edit')
            ->setEntity($address);
        $addressErrors = $addressForm->validateData($addressData);
        if ($addressErrors !== true) {
            $errors = $addressErrors;
        }
        // Compact address data and set customer id
        $addressForm->compactData($addressData);
        $address->setCustomerId($customer->getId());
        // Validate address and collect errors
        $addressErrors = $address->validate();
        if ($addressErrors !== true && is_array($addressErrors)) {
            $errors = array_merge($errors, $addressErrors);
        }
        // Check error count
        // If no errors, save address, otherwise return errors
        if (count($errors) === 0) {
            // Save address
            $address->save();
            $addressId = $address->getId();

            // Now return the id of created address
            return $addressId;
        }
        else {
            $html = '';
            foreach ($errors as $error) {
                $html .= '<li class="error">' . $this->__($error) . '</li>';
            }

            return $html;
        }

    }

    /**
     * Method logs exception and outputs message for display to customer
     *
     */
    protected function handleAjaxException(Exception $e)
    {
        // Log exception
        Mage::log($e->getMessage() . "\n" . $e->getTraceAsString());
        Mage::logException($e);
        // Output error message formatted for display
        echo '<li class="error">' . $this->__($e->getMessage()) . '</li>';
    }

    /**
     * Get customer facing error message text based on error code in Authorize.Net CIM exception
     */
    protected function addErrorFromCimException(SFC_AuthnetToken_Helper_Cim_Exception $eCim)
    {
        switch ($eCim->getResponse()->getMessageCode()) {
            case 'E00014':
                return $this->__('A required field was not entered for credit card!');
                break;
            case 'E00039':
                return $this->__('Credit card number is already saved in your account!');
                break;
            case 'E00042':
                return $this->__('You have already saved the maximum number of credit cards!');
                break;
            default:
                return $this->__('Failed to save credit card with gateway!');
                break;
        }
    }

}
