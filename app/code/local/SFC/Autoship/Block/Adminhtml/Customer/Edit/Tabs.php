<?php
/**
 * @author Alan Barber <alan@cadence-labs.com>
 */ 
class SFC_Autoship_Block_Adminhtml_Customer_Edit_Tabs extends Mage_Adminhtml_Block_Customer_Edit_Tabs
{
    /**
     * Add Payments Profile tab if allowed
     * @return $this
     * @throws Exception
     */
    public function addPaymentProfilesTab()
    {
        if (Mage::getSingleton('admin/session')->isAllowed('customer/paymentprofiles')) {
            $this->addTab('paymentprofile', 'autoship/adminhtml_customer_paymentprofiles_paymentprofile');
        }
        return $this;
    }
}