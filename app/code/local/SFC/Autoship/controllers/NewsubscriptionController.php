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
 * Controller to handle the New Subscription page and subscription creation
 *
 * Save the subscription object which is being built in customer session with code like this:
 *      <code>Mage::getSingleton('customer/session')->setNewSubscription($subscription);</code>
 *
 */
class SFC_Autoship_NewsubscriptionController extends Mage_Core_Controller_Front_Action
{
    /**
     * Authenticate customer for all pages and ajax routes supported by this controller
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
     * Render the Create New Subscription page
     *
     * Accept query parameters for product id / sku, quantity and interval
     */
    public function indexAction()
    {
        // Check if extension enabled
        // Check if New Sub page enabled
        if (Mage::getStoreConfig('autoship_subscription/subscription/use_new_subscription_page') != '1') {
            // Send customer to 404 page
            $this->_forward('defaultNoRoute');
            return;
        }
        try {
            // Get query params
            $processedParams = $this->processQueryParameters();

            // Create subscription model
            $subscription = $this->initNewSubscription($processedParams);

            // Save subscription object in session
            Mage::getSingleton('customer/session')->setNewSubscription($subscription);

            // Load layout from XML
            $this->loadLayout();

            // Set page title for this page
            $this->getLayout()->getBlock('head')->setTitle($this->__('Create New Product Subscription'));

            // Render the layout
            $this->renderLayout();

        }
        catch (Exception $e) {
            // Send customer to 404 page
            $this->_forward('defaultNoRoute');
        }

    }

    /**
     * Grab query parameters, escape and validate them
     *
     * Parameters Accepted:
     * ---------------------
     *      product_id  - One of product_id or product_sku is required to locate product
     *      product_sku - One of product_id or product_sku is required to locate product
     *      qty         - Quantity of product for subscription
     *      interval    - Subscription interval
     *      coupon      - Coupon code to be applied to subscription
     */
    protected function processQueryParameters()
    {
        // Build array to return
        $processedParameters = array();
        // Get params from request
        $params = $this->getRequest()->getParams();

        // Handle product, either by id or sku
        if (strlen($params['product_id'])) {
            $product = Mage::getModel('catalog/product')->load($params['product_id']);
        }
        else {
            if (strlen($params['product_sku'])) {
                $product = Mage::getModel('catalog/product')->load($params['product_sku'], 'sku');
            }
            else {
                // No product identifying parameter passed
                Mage::throwException($this->__('No product_id or product_sku parameter passed!'));
            }
        }
        // If there is no valid product, error out
        if (!strlen($product->getId())) {
            Mage::throwException($this->__('Invalid product requested!'));
        }
        $processedParameters['product'] = $product;
        $processedParameters['product_id'] = $product->getId();
        $processedParameters['product_sku'] = $product->getSku();

        // Handle quantity
        $qty = $params['qty'];
        // Handle qty param <= 0
        // If qty is not supplied or is less than 1, default to 1
        if ($qty <= 0) {
            $qty = 1;
        }
        $processedParameters['qty'] = $qty;

        // Handle interval            
        $interval = $params['interval'];
        if (!strlen($interval)) {
            $interval = '';
        }
        $processedParameters['interval'] = $interval;

        // Coupon code
        if(isset($params['coupon_code'])) {
            $processedParameters['coupon_code'] = $params['coupon_code'];
        }
        else {
            $processedParameters['coupon_code'] = '';
        }

        return $processedParameters;
    }

    /**
     * Build a new subscription model object based on the (already processed) query parameters
     *
     * @param array $processedQueryParams Array of already validated and processed query parameters
     */
    protected function initNewSubscription(array $processedQueryParams)
    {
        // Get current customer from session
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        // Lookup saved credit cards for customer
        $paymentProfile = Mage::getModel('authnettoken/cim_payment_profile')->getCollection()
            ->addFieldToFilter('customer_id', $customer->getId())
            ->getFirstItem();
        // Create default subscription
        $subscription = Mage::getModel('autoship/subscription');
        $subscription->setNextOrderDate(date('Y-m-d', strtotime('+2 days')));
        $subscription->setProductId($processedQueryParams['product_id']);
        // $subscription->setProduct($processedQueryParams['product']);
        $subscription->setCustomerId($customer->getId());
        $subscription->setBillingAddressId($customer->getDefaultBilling());
        $subscription->setShippingAddressId($customer->getDefaultShipping());
        $subscription->setShippingMethod(Mage::getStoreConfig('autoship_subscription/subscription/shipping_method'));
        $subscription->setQty($processedQueryParams['qty']);
        $subscription->setCimPaymentProfileId($paymentProfile->getData('cim_payment_profile_id'));
        $subscription->setInterval($processedQueryParams['interval']);

        // Check min / max qty
        $product = Mage::getModel('catalog/product')->load($subscription->getData('product_id'));
        $platformProduct = Mage::helper('autoship/platform')->getPlatformProduct($product);
        if($subscription->getData('qty') < $platformProduct->getData('min_qty')) {
            $subscription->setData('qty', $platformProduct->getData('min_qty'));
            Mage::getSingleton('customer/session')->addError($this->__('Minimum quantity for subscription is %s', $platformProduct->getData('min_qty')));
        }
        if($subscription->getData('qty') > $platformProduct->getData('max_qty')) {
            $subscription->setData('qty', $platformProduct->getData('max_qty'));
            Mage::getSingleton('customer/session')->addError($this->__('Maximum quantity for subscription is %s', $platformProduct->getData('max_qty')));
        }

        // Return the new subscription model
        return $subscription;
    }

    /**
     * Save payment method action
     */
    public function paymentsaveAction()
    {
        try {
            // Get POST data
            $data = $this->getRequest()->getParam('payment');
            // Get cur customer and subscription models from session
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $subscription = Mage::getSingleton('customer/session')->getNewSubscription();
            // switched saved card
            if (strpos($data['method'], '_cc4_') !== false) {
                // Update selection of payment profile
                $paymentProfile = Mage::getModel('authnettoken/cim_payment_profile')->load($data['payment_profile_id'][$data['method']]);
                $subscription->setCimPaymentProfileId($paymentProfile->getData('cim_payment_profile_id'));
            }
            else {
                // Create and save new profile to CIM and DB
                $paymentProfile = $this->createNewPaymentProfile($subscription, $data);
                // Now set profile id on subscription
                $subscription->setCimPaymentProfileId($paymentProfile->getData('cim_payment_profile_id'));
            }
            // Save updated subscription model in customer session
            Mage::getSingleton('customer/session')->setNewSubscription($subscription);

            // Load and render layout to output updated summary block
            $this->loadLayout(false);
            $this->renderLayout();
        }
        catch (Exception $e) {
            Mage::log($e->getMessage());
            Mage::logException($e);
            echo '<li class="error">' . $this->__($e->getMessage()) . '</li>';

            return;
        }
    }

    /**
     * Create a new Auth.Net CIM payment profile in Magento DB and on Auth.net CIM
     *
     * @param SFC_Autoship_Model_Subscription $subscription Subscription for which to create payment profile
     * @param array $data Array of profile details from POST
     * @return SFC_AuthnetToken_Model_Cim_Payment_Profile Newly created payment profile
     */
    protected function createNewPaymentProfile(SFC_Autoship_Model_Subscription $subscription, array $data)
    {
        // Get customer
        $customer = Mage::getSingleton('customer/session')->getCustomer();
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
        catch (SFC_AuthnetToken_Helper_Cim_Exception $eCim) {
            Mage::throwException($this->addErrorFromCimException($eCim));
        }
        catch (Exception $e) {
            Mage::throwException($this->__('Failed to save credit card!'));
        }

        // Return new model
        return $model;
    }

    /**
     * Update CIM payment profile with billing address
     *
     * @param SFC_Autoship_Model_Subscription $subscription Subscription for which to update payment profile
     * @return SFC_AuthnetToken_Model_Cim_Payment_Profile Updated payment profile
     */
    protected function updateCimPaymentProfileBillingAddress(SFC_Autoship_Model_Subscription $subscription)
    {
        // Check that we have a payment profile and billing address selected
        if (!strlen($subscription->getCimPaymentProfileId()) || !strlen($subscription->getBillingAddressId())) {
            return;
        }
        $paymentProfile = $subscription->getPaymentProfile();
        $paymentProfile->retrieveCimProfileData();
        $billingAddress = $subscription->getBillingAddress();
        $paymentProfile->setBillingAddressFields($billingAddress);
        $paymentProfile->save();
        $paymentProfile->saveCimProfileData();

        // Return new profile
        return $paymentProfile;
    }

    /**
     * Save billing address action
     */
    public function billingsaveAction()
    {
        try {
            // Get POST data
            $data = $this->getRequest()->getParams();
            // Get current subscription
            $subscription = Mage::getSingleton('customer/session')->getNewSubscription();
            if (!empty($data['billing_address_id'])) {
                // change saved address
                $addressId = $data['billing_address_id'];
            }
            else {
                // add new address
                $addressId = $this->saveAddress($data['billing']);
            }
            if (!is_numeric($addressId)) {
                Mage::throwException($this->__('Failed to save address!'));
            }
            // Set billing address on subscription
            $subscription->setBillingAddressId($addressId);
            Mage::getSingleton('customer/session')->setNewSubscription($subscription);
            // Check if customer already has default address
            // If not, set this as default
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if (strlen($customer->getDefaultBilling()) == 0) {
                $customer->setDefaultBilling($addressId);
                $customer->save();
            }
            // Check if there is no shipping address set
            if (!strlen($subscription->getShippingAddressId())) {
                // Set shipping to same address as billing
                $subscription->setShippingAddressId($addressId);
                // Check if there was no default shipping address set on customer
                // If not, set this as default
                if (strlen($customer->getDefaultShipping()) == 0) {
                    $customer->setDefaultShipping($addressId);
                    $customer->save();
                }
            }
        }
        catch (Exception $e) {
            Mage::log($e->getMessage());
            Mage::logException($e);
            echo '<li class="error">' . $this->__($e->getMessage()) . '</li>';

            return;
        }

        // Load and render layout
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Save shipping address action
     */
    public function shippingsaveAction()
    {
        try {
            // Get POST data
            $data = $this->getRequest()->getParams();
            // Get current subscription
            $subscription = Mage::getSingleton('customer/session')->getNewSubscription();
            if (!empty($data['shipping_address_id'])) {
                // change saved address
                $addressId = $data['shipping_address_id'];
            }
            else {
                // add new address
                $addressId = $this->saveAddress($data['shipping']);
            }
            if (!is_numeric($addressId)) {
                Mage::throwException($this->__('Failed to save address!'));
            }
            // Set shipping address on subscription
            $subscription->setShippingAddressId($addressId);
            Mage::getSingleton('customer/session')->setNewSubscription($subscription);
            // Check if customer already has default address
            // If not, set this as default
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            if (strlen($customer->getDefaultShipping()) == 0) {
                $customer->setDefaultShipping($addressId);
                $customer->save();
            }
            // Check if there is no billing address set
            if (!strlen($subscription->getBillingAddressId())) {
                // Set billing to same address as shipping
                $subscription->setBillingAddressId($addressId);
                // Check if there was no default billing address set on customer
                // If not, set this as default
                if (strlen($customer->getDefaultBilling()) == 0) {
                    $customer->setDefaultBilling($addressId);
                    $customer->save();
                }
            }
        }
        catch (Exception $e) {
            Mage::log($e->getMessage());
            Mage::logException($e);
            echo '<li class="error">' . $this->__($e->getMessage()) . '</li>';

            return;
        }

        // Load and render layout
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Update subscription params action
     */
    public function updateAction()
    {
        $data = $this->getRequest()->getPost();
        try {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $subscription = Mage::getSingleton('customer/session')->getNewSubscription();
            $subscription
                ->setQty($data['qty'])
                ->setInterval($data['interval']);
            Mage::getSingleton('customer/session')->setNewSubscription($subscription);
        }
        catch (Exception $e) {
            Mage::log($e->getMessage() . '\n' . $e->getTraceAsString());
            echo '<li class="error">' . $this->__($e->getMessage()) . '</li>';
        }
        // Load and render layout
        $this->loadLayout(false);
        $this->renderLayout();
    }

    /**
     * Create subscription action
     */
    public function createPostAction()
    {
        if (!$this->getRequest()->isPost()) {
            return;
        }

        try {
            // Get post data
            $postData = $this->getRequest()->getPost();

            // Update subscription from post data
            $subscription = Mage::getSingleton('customer/session')->getNewSubscription();
            /*
             * TODO: add ajax action to set next order date on subscription object in customer session when it is changed,
             *       so we don't have to stuff it in the create POST
             */
            $subscription->setNextOrderDate(date_format(date_create_from_format('m/d/y', $postData['delivery_date']),
                'Y-m-d 00:00:00'));
            // TODO: Use ajax to update and save coupon code as / when it is entered so we don't have to stuff it in postData
            if(isset($postData['coupon_code'])) {
                $subscription->setCouponCode($postData['coupon_code']);
            }

            // Update billing address on payment profile, if one exists already
            $this->updateCimPaymentProfileBillingAddress($subscription);
            // Set last 4 digits of cc number on subscription
            $subscription->setData('customer_cardnumber', $subscription->getPaymentProfile()->getData('customer_cardnumber'));
            // Create subscription via API
            Mage::helper('autoship/platform')->createSubscription($subscription);
            // Clear subscription in session
            Mage::getSingleton('customer/session')->setNewSubscription(null);

            // Return URL to redirect customer on successful sub creation            
            $successUrl = Mage::getUrl('*/mysubscriptions/index', array('_secure' => true));
            echo $successUrl;
        }
        catch (Exception $e) {
            Mage::log($e->getMessage());
            Mage::logException($e);
            if (strpos($e->getMessage(), '1062 Duplicate entry')) {
                echo '<li class="error">' . $this->__("You already have a subscription to this product.") . '</li>';
            }
            else {
                echo '<li class="error">' . $this->__($e->getMessage()) . '</li>';
            }
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
