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

class SFC_Autoship_Model_Payment_Profile_Collection extends Varien_Data_Collection
{

    public function _construct()
    {
        $this->setItemObjectClass(Mage::getConfig()->getModelClassName('autoship/payment_profile'));
    }

    /**
     * Load data
     *
     * @param bool $printQuery
     * @param bool $logQuery
     * @throws Exception
     * @return  Varien_Data_Collection
     */
    public function loadData($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }

        /** @var SFC_Autoship_Helper_Vault $vaultHelper */
        $vaultHelper = Mage::helper('autoship/vault');

        // Get customer email filter
        $emailFilter = $this->getFilter('customer_email');
        // This collection only usable when filtered by customer email address
        if ($emailFilter != null) {
            // Retrieve profiles from SP Vault API
            $profiles = $vaultHelper->getPaymentProfilesForCustomer($emailFilter['value']);

            // Sort profiles
            $this->sortProfiles($profiles);

            // Add profiles to collection
            foreach ($profiles as $profile) {
                $this->addItem($profile);
            }
        }


        $this->_setIsLoaded();

        return $this;
    }

    protected function sortProfiles(array &$profiles)
    {
        // Get orders in local
        $orders = $this->_orders;
        // Do sort, with closure sort function
        usort(
            $profiles,
            function(Mage_Core_Model_Abstract $a, Mage_Core_Model_Abstract $b) use($orders) {
                foreach ($orders as $field => $dir) {
                    if ($a->getData($field) < $b->getData($field)) {
                        if ($dir == 'ASC') {
                            return -1;
                        }
                        else {
                            return 1;
                        }
                    }
                    else if ($a->getData($field) > $b->getData($field)) {
                        if ($dir == 'ASC') {
                            return 1;
                        }
                        else {
                            return -1;
                        }
                    }
                }

                return 0;
            }
        );
    }

}
