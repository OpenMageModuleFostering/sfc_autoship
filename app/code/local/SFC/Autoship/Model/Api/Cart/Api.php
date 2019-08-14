<?php

class SFC_Autoship_Model_Api_Cart_Api extends Mage_Checkout_Model_Api_Resource
{

    /**
     * Customer address types
     */
    const ADDRESS_BILLING    = Mage_Sales_Model_Quote_Address::TYPE_BILLING;
    const ADDRESS_SHIPPING   = Mage_Sales_Model_Quote_Address::TYPE_SHIPPING;

    /**
     * Customer checkout types
     */
    const MODE_CUSTOMER = Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER;

    /**
     * Set customer on a quote / shopping cart.
     * The Subscribe Pro platform uses this method rather the standard Magento API method: cart_customer.set
     * We use this method to support "find existing customer by ID, email or both" semantics.
     * This method only works to set an existing customer on a quote.  This method sets the quote to "customer mode" checkout,
     * I.E. Mage_Checkout_Model_Type_Onepage::METHOD_CUSTOMER
     *
     * @param  int $quoteId
     * @param  array $customerData
     * @return int
     * @throws Mage_Api_Exception
     */
    public function setCustomer($quoteId, $customerData)
    {
        // Get quote from quote ID
        $quote = $this->_getQuote($quoteId, null);

        // Check input
        if (!is_array($customerData) || count($customerData) == 0) {
            $this->throwApiFault('sp_cart_customer_format');
        }

        // Get customer identifying fields
        $customerId = $this->getField($customerData, 'customer_id');
        $email = $this->getField($customerData, 'email');

        // Check inputs, did we get customer ID, email, both or neither?
        // Lookup customer model
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer');
        if (!strlen($customerId) && !strlen($email)) {
            $this->throwApiFault('sp_cart_customer_format');
        }
        elseif (strlen($customerId) && !strlen($email)) {
            $customer->load($customerId);
        }
        elseif (!strlen($customerId) && strlen($email)) {
            // Need to set website_id to load a customer by email
            $customer->setData('website_id', $quote->getStore()->getWebsiteId());
            $customer->loadByEmail($email);
        }
        else {
            // $customerId & $email are present
            // Load by ID first
            $customer->load($customerId);
            // Now compare email
            if (strlen($customer->getId()) && $customer->getData('email') != $email) {
                $this->throwApiFault('sp_cart_customer_not_found', "ID: $customerId  Email: $email", "Customer not found.  Email and customer ID don't match.");
            }
        }

        // Check customer model
        if (!strlen($customer->getId())) {
            $this->throwApiFault('sp_cart_customer_not_found', "ID: $customerId  Email: $email");
        }

        // Always assume customer mode
        $customer->setData('mode', self::MODE_CUSTOMER);

        // Attach customer to quote
        // This logic is duplicated from Mage_Checkout_Model_Cart_Customer_Api::set
        try {
            $quote
                ->setCustomer($customer)
                ->setCheckoutMethod($customer->getMode())
                ->setPasswordHash($customer->encryptPassword($customer->getPassword()))
                ->save();
        }
        catch (Mage_Core_Exception $e) {
            $this->throwApiFault('sp_cart_customer_not_set', "ID: $customerId  Email: $email", 'Failed to set customer on quote with error: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Set billing and / or shipping addresses on a quote / shopping cart.
     * The Subscribe Pro platform uses this method rather the standard Magento API method: cart_customer.addresses
     * We use this method to get consistent results across many Magneto editions / versions.
     *
     * @param  int $quoteId
     * @param  array $addressData
     * @return int
     */
    public function setAddresses($quoteId, $addressData)
    {
        // Get quote from quote ID
        $quote = $this->_getQuote($quoteId, null);

        // Check input
        if (!is_array($addressData) || count($addressData) == 0) {
            $this->throwApiFault('sp_cart_address_format');
        }

        // Iterate addresses
        foreach ($addressData as $addressItem) {
            // Get mode
            if (!isset($addressItem['mode'])) {
                $this->throwApiFault('sp_cart_address_format');
            }
            $addressMode = $addressItem['mode'];
            if ($addressMode != self::ADDRESS_BILLING && $addressMode != self::ADDRESS_SHIPPING) {
                $this->throwApiFault('sp_cart_address_mode', $addressMode . ' address');
            }

            // Build a quote address
            /** @var $quoteAddress Mage_Sales_Model_Quote_Address */
            $quoteAddress = Mage::getModel('sales/quote_address');

            // Map it
            $this->mapAddressDataToQuoteAddress($addressItem, $quoteAddress);

            // Process and set address on quote
            $this->magentoAddressSettingLogic($quote, $quoteAddress, $addressMode);
        }

        // Now save quote and addresses
        // This logic is duplicated from Mage_Checkout_Model_Cart_Customer_Api::setAddresses
        try {
            $quote
                ->collectTotals()
                ->save();
        }
        catch (Exception $e) {
            $this->throwApiFault('sp_cart_address_save', 'Error saving addresses for quote: ' . null, $e->getMessage());
        }

        // We made it here, setting addresses was successful
        return true;
    }

    /**
     * Map one array of address data into a Mage_Sales_Model_Quote_Address
     *
     * Must pass 'country_id' field in 2 or 3 digit ISO code format.
     * If regions are loaded in Magento directory for the given country:
     *   * The region passed in is validated and error is thrown if its an unknown region
     *   * Must pass a region name or region code in the 'region' field which matches a region in the Magento DB
     *   * The 'region' and 'region_id' fields on the Mage_Sales_Model_Quote_Address will then be populated
     * If no regions are loaded in Magento DB for the specified country:
     *   * Must pass region name in the 'region field
     *   * 'region' field in Mage_Sales_Model_Quote_Address is populated
     *   * 'region_id' field in Mage_Sales_Model_Quote_Address will be NULL
     *
     * @param array $addressItem
     * @param Mage_Sales_Model_Quote_Address $quoteAddress
     * @throws Mage_Api_Exception
     */
    protected function mapAddressDataToQuoteAddress(array $addressItem, Mage_Sales_Model_Quote_Address $quoteAddress)
    {
        // Straight field mapping
        $quoteAddress->setData('firstname', $this->getField($addressItem, 'firstname'));
        $quoteAddress->setData('lastname', $this->getField($addressItem, 'lastname'));
        $quoteAddress->setData('company', $this->getField($addressItem, 'company'));
        $quoteAddress->setData('street', $this->getField($addressItem, 'street'));
        $quoteAddress->setData('city', $this->getField($addressItem, 'city'));
        $quoteAddress->setData('postcode', $this->getField($addressItem, 'postcode'));
        $quoteAddress->setData('telephone', $this->getField($addressItem, 'telephone'));
        $quoteAddress->setData('fax', $this->getField($addressItem, 'fax'));

        //
        // Map Country
        $countryCode = $this->getField($addressItem, 'country_id');
        // Default to US if no country code specified
        if (!strlen($countryCode)) {
            $countryCode = 'US';
        }
        /** @var Mage_Directory_Model_Country $country */
        $country = Mage::getModel('directory/country');
        $country->loadByCode($countryCode);
        if (!strlen($country->getId())) {
            $this->throwApiFault('sp_cart_address_country_code', $this->getField($addressItem, 'mode') . ' address', "Country code did not match known ISO 2 or 3 digit code: $countryCode");
        }
        // Found a matching country in directory
        $quoteAddress->setData('country_id', $country->getId());

        //
        // Map region
        $regionName = $this->getField($addressItem, 'region');
        if (strlen($regionName)) {
            // Lookup by region code
            /** @var Mage_Directory_Model_Region $region */
            $region = Mage::getModel('directory/region');
            $region->loadByCode($regionName, $countryCode);
            if (!strlen($region->getId())) {
                // Lookup by region name
                $region->loadByName($regionName, $countryCode);
            }
            // If we found a region, assign it
            if (strlen($region->getId())) {
                $quoteAddress->setData('region_id', $region->getId());
                $quoteAddress->setData('region', $region->getName());
            }
            else {
                // We didn't find the region in DB, just assign it verbatim
                $quoteAddress->setData('region', $regionName);
            }
        }
    }

    /**
     * @param array $data
     * @param $fieldName
     * @return null
     */
    protected function getField(array $data, $fieldName)
    {
        if (!isset($data[$fieldName]) || !strlen($data[$fieldName])) {
            return null;
        }
        else {
            return $data[$fieldName];
        }
    }

    /**
     * Logic which gets applied after setting address on quote
     * This was taken from Mage_Checkout_Model_Cart_Customer_Api::setAddresses in Magento CE 1.9.x
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param Mage_Sales_Model_Quote_Address $address
     * @param $addressMode
     * @throws Mage_Api_Exception
     */
    protected function magentoAddressSettingLogic(Mage_Sales_Model_Quote $quote, Mage_Sales_Model_Quote_Address $address, $addressMode)
    {
        // Implode it
        $address->implodeStreetAddress();

        // Validate it
        if (($validateRes = $address->validate())!==true) {
            $this->throwApiFault('sp_cart_address_validation', $addressMode . ' address', implode(PHP_EOL, $validateRes));
        }

        // Save it to quote
        switch($addressMode) {
            case self::ADDRESS_BILLING:
                $address->setEmail($quote->getCustomer()->getEmail());

                if (!$quote->isVirtual()) {
                    $usingCase = isset($addressItem['use_for_shipping']) ? (int)$addressItem['use_for_shipping'] : 0;
                    switch($usingCase) {
                        case 0:
                            $shippingAddress = $quote->getShippingAddress();
                            $shippingAddress->setSameAsBilling(0);
                            break;
                        case 1:
                            $billingAddress = clone $address;
                            $billingAddress->unsAddressId()->unsAddressType();

                            $shippingAddress = $quote->getShippingAddress();
                            $shippingMethod = $shippingAddress->getShippingMethod();
                            $shippingAddress->addData($billingAddress->getData())
                                ->setSameAsBilling(1)
                                ->setShippingMethod($shippingMethod)
                                ->setCollectShippingRates(true);
                            break;
                    }
                }
                $quote->setBillingAddress($address);
                break;

            case self::ADDRESS_SHIPPING:
                $address->setCollectShippingRates(true)
                    ->setSameAsBilling(0);
                $quote->setShippingAddress($address);
                break;
        }

    }

    /**
     * Dispatches fault
     *
     * @param int $code
     * @param null|string $customMessage
     * @param null|string $customMessageParenthetical
     * @throws Mage_Api_Exception
     */
    protected function throwApiFault($code, $customMessageParenthetical = null, $customMessage = null)
    {
        // Process custom message
        if (strlen($customMessage)) {
            $message = $customMessage;
        }
        else {
            $faults = $this->_getConfig()->getFaults('sp_cart');
            if (!is_array($faults) || !isset($faults[$code])) {
                throw new Mage_Api_Exception('unknown');
            }
            $message = isset($faults[$code]['message']) ? $faults[$code]['message'] : '';
        }

        // Add parenthetical
        if (strlen($customMessageParenthetical)) {
            $message = "($customMessageParenthetical) " . $message;
        }

        // Add SP API message tag
        $message = Mage::helper("autoship")->__('Subscribe Pro API: %s',  $message);

        throw new Mage_Api_Exception($code, $message);
    }

    /**
     * Retrieve webservice configuration
     *
     * @return Mage_Api_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('api/config');
    }

}
