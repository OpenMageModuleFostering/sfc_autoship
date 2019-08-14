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

require('OAuth2/Client.php');
require('OAuth2/GrantType/IGrantType.php');
require('OAuth2/GrantType/Password.php');
require('OAuth2/GrantType/ClientCredentials.php');

class SFC_Autoship_Helper_Api extends Mage_Core_Helper_Abstract
{
    // Log file name
    const PLATFORM_API_PATH         = '/services/v1';
    const PLATFORM_API_PATH_20      = '/services/v2';
    const PLATFORM_API_PATH_TOKEN   = '/oauth/v2/token';

    // Cache Type Tags
    const CACHE_TYPE_CONFIG         = 'SP_CONFIG';
    const CACHE_TYPE_PRODUCTS       = 'SP_PRODUCTS';

    const CACHE_TOKEN_TAG           = 'autoship_api_access_token';

    /** @var Mage_Core_Model_Store|null */
    private $_store = null;

    public function __construct()
    {
    }

    /**
     * Set the primary key id of the store to use for all configuration scope
     *
     * @param int|Mage_Core_Model_Store $store Primary key id of the store to use
     */
    public function setConfigStore($store)
    {
        if ($store instanceof Mage_Core_Model_Store) {
            $this->_store = $store;
        }
        else {
            $this->_store = Mage::app()->getStore($store);
        }
    }

    /**
     * Return the store to use for pulling configuration settings
     *
     * @return Mage_Core_Model_Store
     */
    public function getConfigStore()
    {
        if ($this->_store == null) {
            $this->_store = Mage::app()->getStore();
        }
        return $this->_store;
    }

    protected function getServicesUrl()
    {
        return 'https://' . Mage::getStoreConfig('autoship_general/platform_api/platform_host', $this->getConfigStore()) . self::PLATFORM_API_PATH;
    }

    protected function getServicesUrlV2()
    {
        return 'https://' . Mage::getStoreConfig('autoship_general/platform_api/platform_host', $this->getConfigStore()) . self::PLATFORM_API_PATH_20;
    }

    protected function getOAuthTokenUrl()
    {
        return 'https://' . Mage::getStoreConfig('autoship_general/platform_api/platform_host', $this->getConfigStore()) . self::PLATFORM_API_PATH_TOKEN;
    }

    public function fetchCustomers($params = array())
    {
        // Request customers
        $customers = $this->makeRequest(
            $this->getServicesUrl() . '/customers.json',
            $params,
            OAuth2\Client::HTTP_METHOD_GET);

        // Return customers
        return $customers;
    }

    public function fetchCustomer($customerId)
    {
        // Request customer
        $customer = $this->makeRequest(
            $this->getServicesUrl() . '/customers/' . $customerId . '.json',
            array(),
            OAuth2\Client::HTTP_METHOD_GET);

        // Return customer
        return $customer;
    }

    public function updateCustomer($customerId, $customer)
    {
        // Request customer
        $customer = $this->makeRequest(
            $this->getServicesUrl() . '/customers/' . $customerId . '.json',
            json_encode($customer),
            OAuth2\Client::HTTP_METHOD_PUT);

        // Return customer
        return $customer;
    }

    public function deleteCustomer($customerId)
    {
        // Request customer
        $this->makeRequest(
            $this->getServicesUrl() . '/customers/' . $customerId . '.json',
            array(),
            OAuth2\Client::HTTP_METHOD_DELETE);
    }

    public function postCustomer($customer)
    {
        // Request customer
        $result = $this->makeRequest(
            $this->getServicesUrl() . '/customers.json',
            json_encode($customer),
            OAuth2\Client::HTTP_METHOD_POST);

        // Check result
        // Return result for now
        return $result;
    }

    public function fetchCustomerAddresses($customerId, $params = array())
    {
        // Request
        $addresses = $this->makeRequest(
            $this->getServicesUrl() . '/customers/' . $customerId . '/addresses.json',
            $params,
            OAuth2\Client::HTTP_METHOD_GET);

        // Return
        return $addresses;
    }

    public function fetchCustomerAddress($customerId, $addressId)
    {
        // Request
        $address = $this->makeRequest(
            $this->getServicesUrl() . '/customers/' . $customerId . '/addresses/' . $addressId . '.json',
            array(),
            OAuth2\Client::HTTP_METHOD_GET);

        // Return
        return $address;
    }

    public function updateCustomerAddress($customerId, $addressId, $address)
    {
        // Request
        $address = $this->makeRequest(
            $this->getServicesUrl() . '/customers/' . $customerId . '/addresses/' . $addressId . '.json',
            json_encode($address),
            OAuth2\Client::HTTP_METHOD_PUT);

        // Return customer
        return $address;
    }

    public function deleteCustomerAddress($customerId, $addressId)
    {
        // Request
        $this->makeRequest(
            $this->getServicesUrl() . '/customers/' . $customerId . '/addresses/' . $addressId . '.json',
            array(),
            OAuth2\Client::HTTP_METHOD_DELETE);
    }

    public function postCustomerAddress($customerId, $address)
    {
        // Request 
        $result = $this->makeRequest(
            $this->getServicesUrl() . '/customers/' . $customerId . '/addresses.json',
            json_encode($address),
            OAuth2\Client::HTTP_METHOD_POST);

        // Check result
        // Return result for now
        return $result;
    }

    public function fetchSubscriptions($params = array())
    {
        // Request subscriptions
        $subscriptions = $this->makeRequest(
            $this->getServicesUrl() . '/subscriptions.json',
            $params,
            OAuth2\Client::HTTP_METHOD_GET);

        // Return subscriptions
        return $subscriptions;
    }

    public function fetchSubscription($subscriptionId)
    {
        // Request subscription
        $subscription = $this->makeRequest(
            $this->getServicesUrl() . '/subscriptions/' . $subscriptionId . '.json',
            array(),
            OAuth2\Client::HTTP_METHOD_GET);

        // Return subscription
        return $subscription;
    }

    public function updateSubscription($subscriptionId, $subscription)
    {
        // Request subscription
        $subscription = $this->makeRequest(
            $this->getServicesUrl() . '/subscriptions/' . $subscriptionId . '.json',
            json_encode($subscription),
            OAuth2\Client::HTTP_METHOD_PUT);

        // Return subscription
        return $subscription;
    }

    public function cancelSubscription($subscriptionId)
    {
        // Request subscription
        $response = $this->makeRequest(
            $this->getServicesUrlV2() . '/subscriptions/' . $subscriptionId . '/cancel.json',
            array(),
            OAuth2\Client::HTTP_METHOD_POST);

        // Return response
        return $response;
    }

    public function pauseSubscription($subscriptionId)
    {
        // Request subscription
        $response = $this->makeRequest(
            $this->getServicesUrlV2() . '/subscriptions/' . $subscriptionId . '/pause.json',
            array(),
            OAuth2\Client::HTTP_METHOD_POST);

        // Return response
        return $response;
    }

    public function restartSubscription($subscriptionId)
    {
        // Request subscription
        $response = $this->makeRequest(
            $this->getServicesUrlV2() . '/subscriptions/' . $subscriptionId . '/restart.json',
            array(),
            OAuth2\Client::HTTP_METHOD_POST);

        // Return response
        return $response;
    }

    public function skipSubscription($subscriptionId)
    {
        // Request subscription
        $response = $this->makeRequest(
            $this->getServicesUrlV2() . '/subscriptions/' . $subscriptionId . '/skip.json',
            array(),
            OAuth2\Client::HTTP_METHOD_POST);

        // Return response
        return $response;
    }

    public function postSubscription($subscription)
    {
        // Request subscription
        $result = $this->makeRequest(
            $this->getServicesUrl() . '/subscriptions.json',
            json_encode($subscription),
            OAuth2\Client::HTTP_METHOD_POST);

        // Check result
        // Return result for now
        return $result;
    }

    public function fetchSubscriptionProducts($subscriptionId, $params = array())
    {
        // Request
        $products = $this->makeRequest(
            $this->getServicesUrl() . '/subscriptions/' . $subscriptionId . '/products.json',
            $params,
            OAuth2\Client::HTTP_METHOD_GET);

        // Return
        return $products;
    }

    public function fetchSubscriptionProduct($subscriptionId, $productId)
    {
        // Request
        $product = $this->makeRequest(
            $this->getServicesUrl() . '/subscriptions/' . $subscriptionId . '/products/' . $productId . '.json',
            array(),
            OAuth2\Client::HTTP_METHOD_GET);

        // Return
        return $product;
    }

    public function updateSubscriptionProduct($subscriptionId, $productId, $product)
    {
        // Request
        $product = $this->makeRequest(
            $this->getServicesUrl() . '/subscriptions/' . $subscriptionId . '/products/' . $productId . '.json',
            json_encode($product),
            OAuth2\Client::HTTP_METHOD_PUT);

        // Return customer
        return $product;
    }

    public function deleteSubscriptionProduct($subscriptionId, $productId)
    {
        // Request
        $this->makeRequest(
            $this->getServicesUrl() . '/subscriptions/' . $subscriptionId . '/products/' . $productId . '.json',
            array(),
            OAuth2\Client::HTTP_METHOD_DELETE);
    }

    public function postSubscriptionProduct($subscriptionId, $product)
    {
        // Request 
        $result = $this->makeRequest(
            $this->getServicesUrl() . '/subscriptions/' . $subscriptionId . '/products.json',
            json_encode($product),
            OAuth2\Client::HTTP_METHOD_POST);

        // Check result
        // Return result for now
        return $result;
    }

    public function fetchProducts(array $params = array())
    {
        // Lookup products in cache
        $products = $this->loadCache($this->getProductsCacheKey($params), self::CACHE_TYPE_PRODUCTS);
        // Check if products found in cache
        if ($products == null) {
            // Request products from API
            $products = $this->makeRequest(
                $this->getServicesUrl() . '/products.json',
                $params,
                OAuth2\Client::HTTP_METHOD_GET);
            // Save products in cache
            $this->saveCache($products, $this->getProductsCacheKey($params), self::CACHE_TYPE_PRODUCTS);
        }

        // Return products
        return $products;
    }

    protected function getProductsCacheKey(array $params)
    {
        $cacheKey = 'autoship_api_products';
        foreach ($params as $param => $value) {
            $cacheKey .= '_param_' . $param . '_value_' . $value;
        }

        return $cacheKey;
    }

    public function fetchProduct($productId)
    {
        // Lookup product in cache
        $product = $this->loadCache($this->getProductCacheKey($productId), self::CACHE_TYPE_PRODUCTS);
        // Check if product found in cache
        if ($product == null) {
            // Product not found in cache
            // Request product from API
            $product = $this->makeRequest(
                $this->getServicesUrl() . '/products/' . $productId . '.json',
                array(),
                OAuth2\Client::HTTP_METHOD_GET);
            // Save product in cache
            $this->saveCache($product, $this->getProductCacheKey($productId), self::CACHE_TYPE_PRODUCTS);
        }

        // Return product
        return $product;
    }

    protected function getProductCacheKey($productId)
    {
        return 'autoship_api_product_' . $productId;
    }

    public function updateProduct($productId, $product)
    {
        // Request product
        $product = $this->makeRequest(
            $this->getServicesUrl() . '/products/' . $productId . '.json',
            json_encode($product),
            OAuth2\Client::HTTP_METHOD_PUT);

        // Return product
        return $product;
    }

    public function deleteProduct($productId)
    {
        // Request product
        $this->makeRequest(
            $this->getServicesUrl() . '/products/' . $productId . '.json',
            array(),
            OAuth2\Client::HTTP_METHOD_DELETE);
    }

    public function postProduct($product)
    {
        // Request product
        $result = $this->makeRequest(
            $this->getServicesUrl() . '/products.json',
            json_encode($product),
            OAuth2\Client::HTTP_METHOD_POST);

        // Check result
        // Return result for now
        return $result;
    }

    /**
     * @param $code
     * @return array
     */
    public function fetchReport($code)
    {
        //Request report
        //Pass 'allow_result_string' so API helper doesn't throw an error just because the platform returned a CSV
        $result = $this->makeRequest(
            $this->getServicesUrlV2() . '/reports/' . $code,
            array('allow_result_string' => true),
            OAuth2\Client::HTTP_METHOD_GET
        );

        //Return result for now
        return $result;
    }

    public function fetchConfig()
    {
        // Lookup config in cache
        $config = $this->loadCache('autoship_api_config', self::CACHE_TYPE_CONFIG);
        // Check if product found in cache
        if ($config == null) {
            // Product not found in cache
            // Request $config from API
            $config = $this->makeRequest(
                $this->getServicesUrl() . '/accountconfig.json',
                array(),
                OAuth2\Client::HTTP_METHOD_GET);
            // Save config in cache
            $this->saveCache($config, 'autoship_api_config', self::CACHE_TYPE_CONFIG);
        }

        // Return $config
        return $config;
    }

    public function makeVaultRequest($resource, $parameters = array(), $httpMethod = OAuth2\Client::HTTP_METHOD_GET)
    {
        return $this->makeRequest(
            $this->getServicesUrl() . '/vault/' . $resource,
            $parameters,
            $httpMethod);
    }

    protected function makeRequest($url, $parameters = array(), $httpMethod = OAuth2\Client::HTTP_METHOD_GET)
    {
        // Make request
        $response = $this->makeSingleRequestImpl($url, $parameters, $httpMethod);
        // If token cache is enabled, check result for token issue
        if (isset($response['code']) && $response['code'] == 401 && isset($response['result']['error']) && $response['result']['error'] == 'invalid_grant') {
            if ($this->useCache(self::CACHE_TYPE_CONFIG)) {
                // Request failed because of issue with access token.  Try clearing cache & getting new token
                $this->removeTokenFromCache();
                $response = $this->makeSingleRequestImpl($url, $parameters, $httpMethod);
            }
        }

        return $response;
    }

    protected function makeSingleRequestImpl($url, $parameters = array(), $httpMethod)
    {
        // Log
        Mage::log('Platform API | Making request.', Zend_Log::INFO, SFC_Autoship_Helper_Data::API_LOG_FILE);
        Mage::log('Request URL: ' . $url, Zend_Log::INFO, SFC_Autoship_Helper_Data::API_LOG_FILE);
        Mage::log('Request Method: ' . $httpMethod, Zend_Log::INFO, SFC_Autoship_Helper_Data::API_LOG_FILE);
        // Get config setting re: logging request
        $bLogRequest = Mage::getStoreConfig('autoship_general/platform_api/log_request', $this->getConfigStore()) == '1';
        if($bLogRequest) {
            Mage::log($this->json_format($parameters), Zend_Log::INFO, SFC_Autoship_Helper_Data::API_LOG_FILE);
        }
        // Create client
        $client = new OAuth2\Client(null, null);
        // Turn curl logging on for client
        if ($bLogRequest) {
            // Get full path to log file
            $logFilePath = Mage::getBaseDir('var') . DS . 'log' . DS . SFC_Autoship_Helper_Data::API_LOG_FILE;
            $client->setLogFilePath($logFilePath);
        }
        // Grab and set token
        $token = $this->getTokenForRequest();
        $client->setAccessTokenType(OAuth2\Client::ACCESS_TOKEN_BEARER);
        $client->setAccessToken($token);
        // Make request
        $response = $client->fetch($url, $parameters, $httpMethod, array('Accept' => 'application/json'),
            OAuth2\Client::HTTP_FORM_CONTENT_JSON);
        // Log response
        Mage::log('Platform API | Response: ', Zend_Log::DEBUG, SFC_Autoship_Helper_Data::API_LOG_FILE);
        Mage::log($response, Zend_Log::DEBUG, SFC_Autoship_Helper_Data::API_LOG_FILE);
        // Check that we can parse response
        if (!is_array($response) || !isset($response['result']) || !isset($response['code']) || !isset($response['content_type'])) {
            Mage::log('Platform API | Parse error reading HTTP response!', Zend_Log::ERR, SFC_Autoship_Helper_Data::API_LOG_FILE);
            Mage::throwException('Parse error reading HTTP response!');
        }
        $result = $response['result'];
        // Check that we have contents of result, except for code 204
        if (!is_array($result) && $response['code'] != 204 && (!isset($parameters['allow_result_string']) || !$parameters['allow_result_string'])) {
            Mage::log('Platform API | Parse error reading HTTP response!', Zend_Log::ERR, SFC_Autoship_Helper_Data::API_LOG_FILE);
            Mage::throwException('Parse error reading HTTP response!');
        }
        // Parse response
        if ($response['code'] >= 400) {
            // Some type of error response, lets log details
            Mage::log('Platform API | Error response received, HTTP response code: ' . $response['code'], Zend_Log::ERR,
                SFC_Autoship_Helper_Data::API_LOG_FILE);
        }
        else {
            // Lets log all responses at DEBUG level
            Mage::log('Platform API | Response received, HTTP response code: ' . $response['code'], Zend_Log::INFO,
                SFC_Autoship_Helper_Data::API_LOG_FILE);
        }

        // Return response
        return $response;
    }

    /**
     * @param $jsonString
     * @return mixed
     */
    protected function sanitizeJsonString($jsonString)
    {
        $jsonString = preg_replace('/"creditcard_number"\s*\:\s"[0-9]+"/', '"creditcard_number":"XXXXXXXXXXXXXXXX"', $jsonString);
        $jsonString = preg_replace('/"creditcard_verification_value"\s*\:\s*"[0-9]+"/', '"creditcard_verification_value":"XXX"', $jsonString);
        return $jsonString;
    }

    protected function getTokenForRequest()
    {
        // Check cache
        $token = $this->retrieveTokenFromCache();
        // If token not found in cache, lets get a new one from server
        if ($token === false) {
            // Log
            Mage::log('Platform API | Requesting new access token from platform.', Zend_Log::INFO, SFC_Autoship_Helper_Data::API_LOG_FILE);
            // Get config setting re: logging request
            $bLogRequest = Mage::getStoreConfig('autoship_general/platform_api/log_request', $this->getConfigStore()) == '1';
            // Lookup credentials
            $clientId = Mage::helper('core')->decrypt(Mage::getStoreConfig('autoship_general/platform_api/client_id', $this->getConfigStore()));
            $clientSecret = Mage::helper('core')->decrypt(Mage::getStoreConfig('autoship_general/platform_api/client_secret', $this->getConfigStore()));
            // Create client
            $client = new OAuth2\Client($clientId, $clientSecret);
            // Turn curl logging on for client
            if ($bLogRequest) {
                // Get full path to log file
                $logFilePath = Mage::getBaseDir('var') . DS . 'log' . DS . SFC_Autoship_Helper_Data::API_LOG_FILE;
                $client->setLogFilePath($logFilePath);
            }
            // Build parameters
            $params = array(
                'redirect_uri' => '',
            );
            // Get Token
            $response = $client->getAccessToken(
                $this->getOAuthTokenUrl(),
                OAuth2\Client::GRANT_TYPE_CLIENT_CREDENTIALS,
                $params);
            // Log response
            Mage::log('Platform API | Response to getAccessToken: ', Zend_Log::DEBUG, SFC_Autoship_Helper_Data::API_LOG_FILE);
            Mage::log($response, Zend_Log::DEBUG, SFC_Autoship_Helper_Data::API_LOG_FILE);
            // Check that we can parse response
            if (!is_array($response) || !isset($response['result']) || !isset($response['code']) || !isset($response['content_type'])) {
                Mage::log('Platform API | Parse error reading HTTP response!', Zend_Log::ERR, SFC_Autoship_Helper_Data::API_LOG_FILE);
                Mage::throwException($this->__('Parse error reading HTTP response!'));
            }
            $result = $response['result'];
            // Check that we can parse response for token and lifetime
            if (!is_array($result) || !isset($result['access_token']) || !isset($result['expires_in'])) {
                Mage::log('Platform API | Parse error reading getAccessToken HTTP response!', Zend_Log::ERR,
                    SFC_Autoship_Helper_Data::API_LOG_FILE);
                Mage::throwException($this->__('Parse error reading getAccessToken HTTP response!'));
            }
            // Get access token and lifetime
            $accessToken = $result['access_token'];
            $lifeTime = $result['expires_in'];
            // This is now our token
            $token = $accessToken;
            // Save it in cache
            $this->saveTokenToCache($token, $lifeTime);
        }

        // Return token
        return $token;
    }

    protected function saveTokenToCache($accessToken, $lifeTime = 3600)
    {
        $this->saveCache($accessToken, self::CACHE_TOKEN_TAG, self::CACHE_TYPE_CONFIG, $lifeTime - 60);
    }

    protected function retrieveTokenFromCache()
    {
        return $this->loadCache(self::CACHE_TOKEN_TAG, self::CACHE_TYPE_CONFIG);
    }

    protected function removeTokenFromCache()
    {
        $this->removeCache(self::CACHE_TOKEN_TAG, self::CACHE_TYPE_CONFIG);
    }

    protected function getCacheLifetime()
    {
        $lifetime = Mage::getStoreConfig('autoship_general/advanced/cache_lifetime', $this->getConfigStore());
        if ($lifetime <= 0) {
            $lifetime = 300;
        }

        return $lifetime;
    }

    protected function useCache($type)
    {
        if ($type == self::CACHE_TYPE_CONFIG) {
            $useCache = Mage::app()->useCache('subscribe_pro_config');
        }
        else if ($type == self::CACHE_TYPE_PRODUCTS) {
            $useCache = Mage::app()->useCache('subscribe_pro_products');
        }
        else {
            $useCache = false;
        }

        return $useCache;
    }

    protected function saveCache($data, $key, $type, $lifetime = 0)
    {
        // Lookup lifetime if not passed as param
        if ($lifetime <= 0) {
            $lifetime = $this->getCacheLifetime();
        }
        // Add store id to key
        $key .= '_store_' . $this->getConfigStore()->getId();
        // Check use cache setting
        if ($this->useCache($type)) {
            // Save json encoded data to cache
            Mage::app()->saveCache(json_encode($data), $key, array($type), $lifetime);
        }
    }

    protected function loadCache($key, $type)
    {
        // Check use cache setting
        if ($this->useCache($type)) {
            // Add store id to key
            $key .= '_store_' . $this->getConfigStore()->getId();
            // Lookup data in cache
            $data = Mage::app()->loadCache($key);
            if ($data === false) {
                return false;
            }
            else {
                // JSON decode it and return
                return json_decode($data, true);
            }
        }
        else {
            return false;
        }
    }

    protected function removeCache($key, $type)
    {
        // Check use cache setting
        if ($this->useCache($type)) {
            // Add store id to key
            $key .= '_store_' . $this->getConfigStore()->getId();
            Mage::app()->removeCache($key);
        }
    }

    /**
     * Format a flat JSON string to make it more human-readable
     * This function is taken from: https://github.com/GerHobbelt/nicejson-php/blob/master/nicejson.php
     *
     * @param string $json The original JSON string to process
     *        When the input is not a string it is assumed the input is RAW
     *        and should be converted to JSON first of all.
     * @return string Indented version of the original JSON string
     */
    protected function json_format($json) {
        if (!is_string($json)) {
            if (phpversion() && phpversion() >= 5.4) {
                return json_encode($json, JSON_PRETTY_PRINT);
            }
            $json = json_encode($json);
        }
        $result      = '';
        $pos         = 0;               // indentation level
        $strLen      = strlen($json);
        $indentStr   = "\t";
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;

        for ($i = 0; $i < $strLen; $i++) {
            // Grab the next character in the string
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;
            }
            // If this character is the end of an element,
            // output a new line and indent the next line
            else if (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos--;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            // eat all non-essential whitespace in the input as we do our own here and it would only mess up our process
            else if ($outOfQuotes && false !== strpos(" \t\r\n", $char)) {
                continue;
            }

            // Add the character to the result string
            $result .= $char;
            // always add a space after a field colon:
            if ($char == ':' && $outOfQuotes) {
                $result .= ' ';
            }

            // If the last character was the beginning of an element,
            // output a new line and indent the next line
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos++;
                }
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }
            $prevChar = $char;
        }

        return $this->sanitizeJsonString($result);
    }

}
