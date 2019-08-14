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
 * Helper class to assist with retrieving and manipulating subscriptions and associated data
 */
class SFC_Autoship_Helper_Platform extends Mage_Core_Helper_Abstract
{

    // Define const to represent payment method codes
    // These are necessary here so we aren't dependent on constants from modules which may or may not be installed.
    const PAY_METHOD_CODE_SFC_CIM_10XX = 'authnettoken';
    const PAY_METHOD_CODE_SFC_CIM = 'sfc_cim_core';
    const PAY_METHOD_CODE_SUBSCRIBE_PRO_VAULT = 'subscribe_pro';
    const PAY_METHOD_CODE_SFC_CYBERSOURCE = 'sfc_cybersource';
    const PAY_METHOD_CODE_BRAINTREE = "braintree";

    protected $_methodCodeMap = array();

    protected $_accountConfig = null;

    public function __construct()
    {
        // Map Mage payment method code to platform gateway
        $this->_methodCodeMap = array(
            self::PAY_METHOD_CODE_SFC_CIM_10XX => 'Authorize.Net CIM',
            self::PAY_METHOD_CODE_SFC_CIM => 'Authorize.Net CIM',
            self::PAY_METHOD_CODE_SUBSCRIBE_PRO_VAULT => 'Subscribe Pro Vault',
            self::PAY_METHOD_CODE_SFC_CYBERSOURCE => 'CyberSource'
        );
    }

    /**
     * Retrieve subscription product profile info from platform (eligible intervals, min & max qty, discount, etc)
     * Creates product on platform if it doesn't exist.
     *
     * @param Mage_Catalog_Model_Product $product Magento product object
     * @return SFC_Autoship_Model_Platform_Product Platform product data structure
     */
    public function getPlatformProduct(Mage_Catalog_Model_Product $product)
    {
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Lookup whether product enabled / disabled
        $isProductEnabled = Mage::helper('autoship/product')->isAvailableForSubscription($product, $apiHelper->getConfigStore(), true);
        if (!$isProductEnabled) {
            $platformProduct = Mage::getModel('autoship/platform_product');
            $platformProduct->setData('enabled', false);
            return $platformProduct;
        }

        // Do API query by SKU for product info
        $response = $apiHelper->fetchProducts(array('sku' => $product->getSku()));
        //print_r($response);die;
        // Check response
        if ($response['code'] != 200) {
            // API Error
            Mage::throwException($this->__('API error!'));
        }
        // Check that we found product info
        $result = $response['result'];
        $platformProducts = $result['products'];
        if (!is_array($platformProducts) || count($platformProducts) != 1) {
            Mage::throwException($this->__('Product not found on Subscribe Pro platform!'));
        }
        // Map platform product data to Mage model object
        $platformProductData = $platformProducts[0];
        $platformProduct = Mage::getModel('autoship/platform_product');
        $platformProduct->addData($platformProductData);
        // Force enabled = true
        // We are no longer honoring the is_subscription_enabled field from the platform
        $platformProduct->setData('enabled', true);

        // Return product
        return $platformProduct;
    }

    /**
     * Handle catalog_product_save_after Event and update product profile in DB and on platform
     *
     * @param Mage_Catalog_Model_Product $product Magento product object
     */
    public function handleOnSaveProduct(Mage_Catalog_Model_Product $product)
    {
        Mage::log('SFC_Autoship_Helper_Platform::handleOnSaveProduct', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        Mage::log('Product SKU: ' . $product->getSku(), Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        /** @var SFC_Autoship_Helper_Product $productHelper */
        $productHelper = Mage::helper('autoship/product');

        // Get website ids for websites product is assigned to
        $productWebsites = $product->getWebsiteIds();

        // Iterate all websites / stores
        $websites = Mage::app()->getWebsites(false);
        /** @var Mage_Core_Model_Website $website */
        foreach ($websites as $website) {
            Mage::log('Website ID: ' . $website->getId() . ' code: ' . $website->getCode(), Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            $store = $website->getDefaultStore();
            if (!$store instanceof Mage_Core_Model_Store) {
                Mage::log('No default store for website!', Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
                continue;
            }
            Mage::log('Website default store code: ' . $store->getCode(), Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            Mage::log('Subscription features enabled: ' . Mage::getStoreConfig('autoship_general/general/enabled', $store),
                Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            // Check configuration for this store
            if (Mage::getStoreConfig('autoship_general/general/enabled', $store) == '1') {
                // Check if product is assigned to website
                $productAssignedToWebsite = in_array($website->getId(), $productWebsites);
                Mage::log('Product is assigned to website: ' . $productAssignedToWebsite, Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
                if ($productAssignedToWebsite) {
                    // Lookup whether product enabled / disabled
                    $isProductEnabled = $productHelper->isAvailableForSubscription($product, $store, false);
                    Mage::log('Is product enabled for subscription: ' . $isProductEnabled, Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
                    // If enabled, update product on platform
                    if ($isProductEnabled) {
                        // Create / update product on platform
                        $this->updateProductOnPlatform($store, $product);
                    }
                }
            }
        }
    }

    /**
     * Update the product on the platform for a given store
     *
     * @param Mage_Core_Model_Store $store
     * @param Mage_Catalog_Model_Product $product Magento product object
     */
    protected function updateProductOnPlatform(Mage_Core_Model_Store $store, Mage_Catalog_Model_Product $product)
    {
        Mage::log('SFC_Autoship_Helper_Platform::updateProductOnPlatform', Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);

        // Get ref to core session object
        /** @var Mage_Core_Model_Session $coreSession */
        $coreSession = Mage::getSingleton('core/session');

        // Reload product for this specific store
        /** @var Mage_Catalog_Model_Product $product */
        $product = Mage::getModel('catalog/product')->setData('store_id', $store->getId())->load($product->getId());

        // Don't allow grouped product to be enabled for subscription
        // Otherwise don't check product types or check for options here
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED) {
            return;
        }

        // Update product on platform
        // Try to update platform
        try {
            // Create / update product on platform
            $this->createOrUpdateProductImpl($product, array(), $store);
        }
        catch (Exception $e) {
            Mage::log('Failed to update product on platform with error: ' . $e->getMessage());
            $coreSession->addError($this->__('Failed to update product on platform!'));
        }
    }

    /**
     * Create or update product info on the platform
     *
     * @param Mage_Catalog_Model_Product $product Magento product object
     * @param array|null $data Subscription product settings to set on the platform
     * @return int Id of newly created or updated product on platform
     */
    public function createOrUpdateProduct(Mage_Catalog_Model_Product $product, array $data = array())
    {
        // Create or Update via API
        $platformProductData = $this->createOrUpdateProductImpl($product, $data);
        // Grab id of product on platform
        $platformProductId = $platformProductData['id'];

        // Return id of updated product on platform
        return $platformProductId;
    }

    /**
     * Create or update product info on the platform
     *
     * @param Mage_Catalog_Model_Product $product Magento product object
     * @param array $data Subscription product settings to set on the platform
     * @param null $store
     * @return array Platform product data structure
     */
    protected function createOrUpdateProductImpl(Mage_Catalog_Model_Product $product, array $data = array(), $store = null)
    {
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        // Set store on api helper
        if($store != null) {
            $apiHelper->setConfigStore($store);
        }

        // Lookup whether product enabled / disabled
        $isProductEnabled = Mage::helper('autoship/product')->isAvailableForSubscription($product, $store, false);
        // Prepare new platform product info
        $newPlatformProductData = array(
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'is_subscription_enabled' => $isProductEnabled,
            'price' => $product->getPrice(),
        );
        // Save optional fields if there were pass in the profile
        if(isset($data['min_qty']) && strlen($data['min_qty'])) {
            $newPlatformProductData['min_qty'] = $data['min_qty'];
        }
        if(isset($data['max_qty']) && strlen($data['max_qty'])) {
            $newPlatformProductData['max_qty'] = $data['max_qty'];
        }
        if(isset($data['discount']) && strlen($data['discount'])) {
            $newPlatformProductData['discount'] = $data['discount'];
        }
        if(isset($data['is_discount_percentage']) && strlen($data['is_discount_percentage'])) {
            $newPlatformProductData['is_discount_percentage'] = $data['is_discount_percentage'];
        }
        // Save intervals if they were passed in the profile
        if(isset($data['product_intervals']) && strlen($data['product_intervals'])) {
            // Convert intervals to array
            $intervalsArray = explode(',', trim($data['product_intervals'], ' ,'));
            $newPlatformProductData['intervals'] = $intervalsArray;
        }

        // Do API query by SKU for product info
        $response = $apiHelper->fetchProducts(array('sku' => $product->getSku()));
        // Check response
        if ($response['code'] != 200) {
            // API Error
            Mage::throwException($this->__('API error!'));
        }
        // Check that we found product info
        $result = $response['result'];
        $platformProducts = $result['products'];
        if (is_array($platformProducts) && count($platformProducts) == 1) {
            // Found product, lets update
            $platformProduct = $platformProducts[0];
            // Merge new platform data into fetched data
            $platformProduct = array_merge($platformProduct, $newPlatformProductData);
            // Call API
            $response = $apiHelper->updateProduct($platformProduct['id'], $platformProduct);
            // Check response for HTTP status code
            if ($response['code'] != 201) {
                Mage::throwException($this->__('Failed to update product on platform!'));
            }

            // Return product
            return $platformProduct;
        }
        else {
            if (!is_array($platformProducts) || !count($platformProducts)) {
                // Didn't find product matching SKU
                // Lets add this product to API
                $response = $apiHelper->postProduct($newPlatformProductData);
                // Check response
                if ($response['code'] != 201) {
                    // API Error
                    Mage::throwException('API error!');
                }
                $result = $response['result'];
                $platformProducts = $result['products'];
                // Check that we have exactly 1 product
                if (!is_array($platformProducts) || count($platformProducts) != 1) {
                    Mage::throwException($this->__('Failed to create product on platform!'));
                }
                // Now get product data
                $platformProduct = $platformProducts[0];
                if (!is_array($platformProduct) || !isset($platformProduct['id'])) {
                    Mage::throwException($this->__('Failed to read response from platform while creating product!'));
                }

                // Return product
                return $platformProduct;
            }
            else {
                // This shouldn't happen
                Mage::throwException($this->__('Failed querying API for product SKU: ' . $product->getSku()));
            }
        }
    }

    /**
     * Create a new subscription on the platform
     *
     * @param SFC_Autoship_Model_Subscription $subscription Magento subscription model object
     * @return int Platform Subscription Id of the newly created or updated subscription record
     */
    public function createSubscription(SFC_Autoship_Model_Subscription $subscription)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        //Get subscription in platform format
        $platformSubscription = $this->convertMagentoSubscriptionToPlatform($subscription);

        // Call API
        $response = $apiHelper->postSubscription($platformSubscription);
        // Check response for HTTP status code
        if ($response['code'] != 201) {
            Mage::throwException($this->__('Failed to create subscription on platform!'));
        }
        // Parse subscription id out of response
        $result = $response['result'];
        $subscriptions = $result['subscriptions'];
        // Check that we have exactly 1 subscription
        if (!is_array($subscriptions) || count($subscriptions) != 1) {
            Mage::throwException($this->__('Failed to create subscription on platform!'));
        }
        // Now get customer_id of created subscription
        $subscription = $subscriptions[0];
        if (!is_array($subscription) || !isset($subscription['id'])) {
            Mage::throwException($this->__('Failed to read response from platform while creating subscription!'));
        }

        // Return customer id
        return $subscription['id'];
    }

    /**
     * Create or update a customer on the platform
     *
     * @param Mage_Customer_Model_Customer @customer Magento customer - method will create or update this customer record on the platform
     * @return int Platform Customer Id of the newly created or updated customer record
     */
    public function createOrUpdateCustomer(Mage_Customer_Model_Customer $customer)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Query for current customer
        $response = $apiHelper->fetchCustomers(
            array(
                'email' => $customer->getData('email'),
            ));
        // Check response for HTTP status code
        if ($response['code'] != 200) {
            Mage::throwException('Failed to find ');
        }
        // Parse customer id out of response
        $result = $response['result'];
        $platformCustomers = $result['customers'];
        // Check that we have found exactly 1 customer
        if (!is_array($platformCustomers)) {
            Mage::throwException($this->__('Failed to read response from platform while querying for customer!'));
        }
        if (count($platformCustomers) > 1) {
            Mage::throwException($this->__('Found more than 1 matching customer on platform!'));
        }
        if (count($platformCustomers) == 1) {
            // Found customer, now lets update him
            // Get id of found customer
            $platformCustomer = $platformCustomers[0];
            if (!is_array($platformCustomer) || !isset($platformCustomer['id'])) {
                Mage::throwException($this->__('Failed to read response from platform while querying for customer!'));
            }

            // Update this customer
            return $this->updateCustomer($platformCustomer['id'], $customer);
        }
        else {
            // Didn't find existing customer, lets create him
            return $this->createCustomer($customer);
        }
    }

    /**
     * Get a customer by email, return false if the customer doesn't exist
     * @param $email
     * @return mixed bool|array
     * @throws Mage_Core_Exception
     */
    public function getCustomer($email)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Query for current customer
        $response = $apiHelper->fetchCustomers(
            array(
                'email' => $email
            ));
        // Check response for HTTP status code
        if ($response['code'] != 200) {
            Mage::log('SFC_Autoship_Helper_Platform::getCustomer Customer does not exist on platform with email: ' . $email, Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            return false;
        }
        // Parse customer id out of response
        $result = $response['result'];
        $platformCustomers = $result['customers'];
        // Check that we have found exactly 1 customer
        if (!is_array($platformCustomers)) {
            Mage::throwException($this->__('Failed to read response from platform while querying for customer!'));
        }
        if (count($platformCustomers) > 1) {
            Mage::throwException($this->__('Found more than 1 matching customer on platform!'));
        }
        if (count($platformCustomers) == 1) {
            Mage::log('SFC_Autoship_Helper_Platform::getCustomer Customer exists on platform with email: ' . $email, Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            // Found customer, now lets update him
            // Get id of found customer
            $platformCustomer = $platformCustomers[0];
            if (!is_array($platformCustomer) || !isset($platformCustomer['id'])) {
                Mage::throwException($this->__('Failed to read response from platform while querying for customer!'));
            }
            return $platformCustomer;
        }
        return false;
    }

    /**
     * Create a new customer on the platform
     *
     * @param Mage_Customer_Model_Customer @customer Magento customer - method will create this customer record on the platform
     * @return int Platform Customer Id of the newly created customer record
     */
    public function createCustomer(Mage_Customer_Model_Customer $customer)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Build an array of data representing the customer
        // Get platform customer data structure
        $platformCustomer = $this->convertMagentoCustomerToPlatform($customer);
        // Call API
        $response = $apiHelper->postCustomer($platformCustomer);
        // Check response for HTTP status code
        if ($response['code'] != 201) {
            Mage::throwException($this->__('Failed to create customer on platform!'));
        }
        // Parse customer id out of response
        $result = $response['result'];
        $platformCustomers = $result['customers'];
        // Check that we have exactly 1 customer
        if (!is_array($platformCustomers) || count($platformCustomers) != 1) {
            Mage::throwException($this->__('Failed to create customer on platform!'));
        }
        // Now get customer_id of created customer
        $platformCustomer = $platformCustomers[0];
        if (!is_array($platformCustomer) || !isset($platformCustomer['id'])) {
            Mage::throwException($this->__('Failed to read response from platform while creating customer!'));
        }

        // Return customer id
        return $platformCustomer['id'];
    }

    /**
     * Update an existing customer on the platform
     *
     * @param int $platformCustomerId Id of the current customer record on the platform
     * @param Mage_Customer_Model_Customer $customer Magento customer - this method will update the customer record on the platform with this data
     * @return int Platform Customer Id of the update customer record
     */
    public function updateCustomer($platformCustomerId, Mage_Customer_Model_Customer $customer)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Get platform customer data structure
        $platformCustomer = $this->convertMagentoCustomerToPlatform($customer);
        // Call API
        $response = $apiHelper->updateCustomer($platformCustomerId, $platformCustomer);
        // Check response for HTTP status code
        if ($response['code'] != 201) {
            Mage::throwException($this->__('Failed to update customer on platform!'));
        }
        // Parse customer id out of response
        $result = $response['result'];
        $platformCustomer = $result['customer'];
        // Now get customer_id of created customer
        if (!is_array($platformCustomer) || !isset($platformCustomer['id'])) {
            Mage::throwException($this->__('Failed to read response from platform while updating customer!'));
        }

        // Return customer id
        return $platformCustomer['id'];
    }

    protected function convertMagentoCustomerToPlatform(Mage_Customer_Model_Customer $customer)
    {
        // Build an array of data representing the customer
        $platformCustomer = array(
            'magento_customer_id' => $customer->getId(),
            'email' => $customer->getData('email'),
            'first_name' => $customer->getData('firstname'),
            'middle_name' => $customer->getData('middlename'),
            'last_name' => $customer->getData('lastname'),
        );
        try {
            if (class_exists('SFC_AuthnetToken_Model_Cim') || class_exists('SFC_CimCore_Model_Cim')) {
                if (strlen($customer->getData('cim_customer_profile_id')) > 0) {
                    $platformCustomer['external_vault_customer_token'] = $customer->getData('cim_customer_profile_id');
                }
            }
        } Catch (Exception $e) {
            //When in dev mode 'class_exists' will throw an exception if the class can't be found
        }

        return $platformCustomer;
    }

    /**
     * Gets subscriptions from the platform, into an array, filtered by $params
     *
     * @param Mage_Customer_Model_Customer $customer Customer for which to get subscriptions
     * @param array $params Array of parameter => value pairs to filter the collection of subscriptions
     * @return array Returns an array of SFC_Autoship_Model_Subscription model objects
     */
    public function getSubscriptions(Mage_Customer_Model_Customer $customer, $params = array())
    {
        // Check params is an array
        if (!is_array($params)) {
            Mage::throwException('Invalid parameters specified!');
        }
        // Add customer filter to params
        $params['magento_customer_id'] = $customer->getId();

        // Call All method
        return $this->getAllSubscriptions($params);
    }

    /**
     * Gets subscriptions from the platform, into an array, filtered by $params
     *
     * @param array $params Array of parameter => value pairs to filter the collection of subscriptions
     * @return array Returns an array of SFC_Autoship_Model_Subscription model objects
     */
    public function getAllSubscriptions($params = array())
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        // Check params is an array
        if (!is_array($params)) {
            Mage::throwException('Invalid parameters specified!');
        }
        // Get subscriptions using API
        $response = $apiHelper->fetchSubscriptions($params);
        // Check response for HTTP status code
        if ($response['code'] != 200) {
            Mage::throwException($this->__('Failed to query for subscriptions!'));
        }
        // Parse subscriptions out of response
        $result = $response['result'];
        $platformSubscriptions = $result['subscriptions'];
        // Iterate subscriptions and build subscription model objects
        $subscriptions = array();
        foreach ($platformSubscriptions as $platformSubscription) {
            // Convert platform subscription to Magento model and save in array
            $subscription = $this->convertPlatformSubscriptionToMagento($platformSubscription);
            if(isset($params['magento_customer_id'])) {
                $subscription['customer_id'] = $params['magento_customer_id'];
            }
            $subscriptions[] = $subscription;
        }

        // Return array of subscriptions
        return $subscriptions;
    }

    /**
     * Get a subscription by id
     *
     * @param int $subscriptionId Unique id of subscription on platform
     * @return \SFC_Autoship_Model_Subscription Return the subscription model
     */
    public function getSubscription($subscriptionId)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        // Call delete on API
        $response = $apiHelper->fetchSubscription($subscriptionId);
        // Check response for HTTP status code
        if ($response['code'] != 200) {
            Mage::throwException($this->__('Failed to GET subscription!'));
        }
        // Parse subscription from result
        $result = $response['result'];
        if (!isset($result['subscription']) || !is_array($result['subscription'])) {
            Mage::throwException($this->__('Failed to parse GET subscription response!'));
        }
        $platformSubscription = $result['subscription'];
        // Assume subscription is for current customer
        /** @var Mage_Customer_Model_Session $customerSession */
        $customerSession = Mage::getSingleton('customer/session');
        $customer = $customerSession->getCustomer();
        // Convert subscription to Mage model
        $subscription = $this->convertPlatformSubscriptionToMagento($platformSubscription, $customer);

        // Return subscription
        return $subscription;
    }

    /**
     * Update an existing subscription on the platform
     *
     * @param int $platformSubscriptionId Id of the current subscription record on the platform
     * @param SFC_Autoship_Model_Subscription $subscription Mage subscription model object
     * @return int Platform subscription Id of the update subscription record
     */
    public function updateSubscription($platformSubscriptionId, SFC_Autoship_Model_Subscription $subscription)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        //Get subscription in platform format
        $platformSubscription = $this->convertMagentoSubscriptionToPlatform($subscription, $platformSubscriptionId);

        // Call API
        $response = $apiHelper->updateSubscription($platformSubscriptionId, $platformSubscription);
        // Check response for HTTP status code
        if ($response['code'] != 201) {
            Mage::throwException($this->__('Failed to update subscription on platform!'));
        }
        // Parse customer id out of response
        $result = $response['result'];
        $platformSubscription = $result['subscription'];
        // Now get customer_id of created subscription
        if (!is_array($platformSubscription) || !isset($platformSubscription['id'])) {
            Mage::throwException($this->__('Failed to read response from platform while updating subscription!'));
        }

        // Return customer id
        return $platformSubscription['id'];
    }

    /**
     * Transform a Subscription model into an array that can be read by the platform
     * @param SFC_Autoship_Model_Subscription $subscription
     * @param null $platformSubscriptionId
     * @return array
     */
    public function convertMagentoSubscriptionToPlatform(SFC_Autoship_Model_Subscription $subscription, $platformSubscriptionId = null)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        if (!$subscription->getData('platform_customer_id')) {
            // Get customer
            $customer = $subscription->getCustomer();
            // Create or update the customer
            $platformCustomerId = $this->createOrUpdateCustomer($customer);
            $subscription->setData('platform_customer_id', $platformCustomerId);
        }

        if (!$subscription->getData('product_sku')) {
            $sku = Mage::getModel('catalog/product')->load($subscription->getData('product_id'))->getSku();
            $subscription->setData('product_sku', $sku);
        }

        $platformSubscription = array(
            'customer_id' => $subscription->getData('platform_customer_id'),
            'subscription_products' => array(
                array(
                    'product_sku' => $subscription->getData('product_sku'),
                    'qty' => $subscription->getData('qty')
                )
            ),
            'next_order_date' => substr($subscription->getData('next_order_date'), 0, 10),
            'interval' => $subscription->getData('interval'),
            'magento_shipping_address_id' => $subscription->getData('shipping_address_id'),
            'magento_shipping_method_code' => $subscription->getData('shipping_method'),
            'coupon_code' => $subscription->getData('coupon_code'),
            'skip_first_order' => $subscription->getData('skip_first_order'),
            'send_customer_notification' => $subscription->getData('send_customer_notification'),
            'magento_store_code' => $subscription->getData('magento_store_code')
        );

        // If expiration date set, set on platform
        if($subscription->hasData('expiration_date')) {
            $platformSubscription['expiration_date'] = $subscription->getData('expiration_date');
        }

        //If subscription is using fixed price
        if ($subscription->hasData('use_fixed_price')) {
            $platformSubscription['use_fixed_price'] = $subscription->getData('use_fixed_price');
        }

        //Set fixed price
        if ($subscription->hasData('fixed_price')) {
            $platformSubscription['fixed_price'] = $subscription->getData('fixed_price');
        }

        // If status is set, update
        if($subscription->hasData('status')) {
            $platformSubscription['status'] = $subscription->getData('status');
        }

        $options = $subscription->getData('magento_product_options');
        if (is_array($options) && count($options)) {
            $platformSubscription['subscription_products'][0]['magento_product_options'] = $options;
        }

        $additionalOptions = $subscription->getData('magento_additional_options');
        if (is_array($additionalOptions) && count($additionalOptions)) {
            $platformSubscription['subscription_products'][0]['magento_additional_options'] = $additionalOptions;
        }

        // Add payment details
        // New, multi-gateway aware method
        $platformSubscription['payment_profile'] = array(
            'billing_address' => array(
                'magento_address_id' => $subscription->getData('billing_address_id'),
                'first_name' => $subscription->getData('billing_first_name'),
                'last_name' => $subscription->getData('billing_last_name'),
            ),
            'gateway' => array(
                'name' => $this->_methodCodeMap[$subscription->getData('payment_method_code')],
            ),
            'payment_token' => $subscription->getData('payment_token'),
            'creditcard_last_digits' => substr($subscription->getData('customer_cardnumber'), -4),
        );

        return $platformSubscription;
    }

    /**
     * Delete a subscription
     *
     * @param int $subscriptionId Unique id of subscription on platform
     */
    public function deleteSubscription($subscriptionId)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        // Call delete on API
        $response = $apiHelper->deleteSubscription($subscriptionId);
        // Check response for HTTP status code
        if ($response['code'] != 204) {
            Mage::throwException($this->__('Failed to delete subscription!'));
        }
    }

    /**
     * Cancel a subscription
     *
     * @param int $subscriptionId Unique id of subscription on platform
     */
    public function cancelSubscription($subscriptionId)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        // Call delete on API
        $response = $apiHelper->cancelSubscription($subscriptionId);
        // Check response for HTTP status code
        if ($response['code'] != 204) {
            Mage::throwException($this->__('Failed to cancel subscription!'));
        }
    }

    /**
     * Pause a subscription
     *
     * @param int $subscriptionId Unique id of subscription on platform
     */
    public function pauseSubscription($subscriptionId)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        // Call delete on API
        $response = $apiHelper->pauseSubscription($subscriptionId);
        // Check response for HTTP status code
        if ($response['code'] != 204) {
            Mage::throwException($this->__('Failed to pause subscription!'));
        }
    }

    /**
     * Restart a subscription
     *
     * @param int $subscriptionId Unique id of subscription on platform
     */
    public function restartSubscription($subscriptionId)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        // Call delete on API
        $response = $apiHelper->restartSubscription($subscriptionId);
        // Check response for HTTP status code
        if ($response['code'] != 204) {
            Mage::throwException($this->__('Failed to restart subscription!'));
        }
    }

    /**
     * Skip next delivery on a subscription
     *
     * @param int $subscriptionId Unique id of subscription on platform
     */
    public function skipSubscription($subscriptionId)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');
        // Call delete on API
        $response = $apiHelper->skipSubscription($subscriptionId);
        // Check response for HTTP status code
        if ($response['code'] != 204) {
            Mage::throwException($this->__('Failed to skip next delivery on subscription!'));
        }
    }

    public function getAccountConfig()
    {
        if($this->_accountConfig == null) {
            // Get API Helper
            /** @var SFC_Autoship_Helper_Api $apiHelper */
            $apiHelper = Mage::helper('autoship/api');

            // Query for current customer
            $response = $apiHelper->fetchConfig();
            // Check response for HTTP status code
            if ($response['code'] != 200) {
                Mage::throwException($this->__('Failed to find account configuration!'));
            }
            // Get config data out of response structure
            $config = $response['result']['config'];
            $this->_accountConfig = $config;
        }

        return $this->_accountConfig;
    }

    public function getConfiguredPaymentMethodCode()
    {
        // Get account configuration from platform
        $accountConfig = $this->getAccountConfig();
        if (isset($accountConfig['magento_payment_method'])) {
            return $accountConfig['magento_payment_method'];
        }
        else {
            return '';
        }
    }

    public function getConfiguredGateway()
    {
        // Get account configuration from platform
        $accountConfig = $this->getAccountConfig();
        if (isset($accountConfig['payment_gateway'])) {
            return $accountConfig['payment_gateway'];
        }
        else {
            return '';
        }
    }

    /**
     * Converts subscription data from platform to Magento model object
     *
     * @param array $platformSubscription Subscription data returned from platform
     * @param Mage_Customer_Model_Customer|null $customer
     * @return SFC_Autoship_Model_Subscription Magento subscription model object
     */
    protected function convertPlatformSubscriptionToMagento(array $platformSubscription, Mage_Customer_Model_Customer $customer = null)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        /** @var SFC_Autoship_Model_Subscription $subscription */
        $subscription = Mage::getModel('autoship/subscription');

        //First set the subscription id
        $subscription->setData('subscription_id', $platformSubscription['id']);

        //Iterate over all scalar (top-level) values returned by the platform helper, and set them on the subscription
        //Exclude values where the subscription key is not the magento model key
        $_mappedFields = array('id', 'customer_id', 'magento_shipping_address_id', 'magento_shipping_method_code');
        foreach($platformSubscription as $k => $v) {
            if (is_scalar($v) && !in_array($k, $_mappedFields)) {
                $subscription->setData($k, $v);
            }
        }

        $platformProducts = $platformSubscription['subscription_products'];
        $platformProduct = $platformProducts[0];
        /** @var Mage_Catalog_Model_Product $productModel */
        $productModel = Mage::getModel('catalog/product');
        $productId = $productModel->getIdBySku($platformProduct['product_sku']);
        if (isset($platformProduct['id'])) {
            $subscription->setData('platform_product_id', $platformProduct['id']);
        } else {
            $subscription->setData('platform_product_id', null);
        }
        $subscription->setData('product_sku', $platformProduct['product_sku']);
        $subscription->setData('product_id', $productId);
        $subscription->setData('qty', $platformProduct['qty']);

        $subscription->setData('platform_customer_id', $platformSubscription['customer_id']);
        if($customer != null) {
            $subscription->setData('customer_id', $customer->getId());
        }

        if (isset($platformSubscription['payment_profile']['billing_address']['magento_address_id'])) {
            $subscription->setData('billing_address_id', $platformSubscription['payment_profile']['billing_address']['magento_address_id']);
        }
        $subscription->setData('billing_first_name', $platformSubscription['payment_profile']['billing_address']['first_name']);
        $subscription->setData('billing_last_name', $platformSubscription['payment_profile']['billing_address']['last_name']);
        if (isset($platformSubscription['magento_shipping_address_id'])) {
            $subscription->setData('shipping_address_id', $platformSubscription['magento_shipping_address_id']);
        }
        $subscription->setData('shipping_method', $platformSubscription['magento_shipping_method_code']);

        // Save generic pay profile
        $subscription->setData('payment_profile', $platformSubscription['payment_profile']);
        // Handle payment gateway specific setting
        $subscription->setData('payment_method_code', $this->getConfiguredPaymentMethodCode());
        $subscription->setData('payment_token', $platformSubscription['payment_profile']['payment_token']);
        $subscription->setData('creditcard_last_digits', $platformSubscription['payment_profile']['creditcard_last_digits']);
        $subscription->setData('customer_cardnumber', $platformSubscription['payment_profile']['creditcard_last_digits']);

        return $subscription;
    }

    /**
     * @param $code
     */
    public function getReport($code)
    {
        // Get API Helper
        /** @var SFC_Autoship_Helper_Api $apiHelper */
        $apiHelper = Mage::helper('autoship/api');

        $response = $apiHelper->fetchReport($code);

        if ($response['code'] !== 200) {
            Mage::log('SFC_Autoship_Helper_Platform::getReport Report does not exist on platform with code: ' . $code, Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
        }

        $result = $response['result'];

        if (!$result) {
            Mage::log('SFC_Autoship_Helper_Platform::getReport Report has no result, with code: ' . $code, Zend_Log::INFO, SFC_Autoship_Helper_Data::LOG_FILE);
            Mage::throwException($this->__('Failed to read result for report with code %s', $code));
        }

        return $result;
    }

}
