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

class SFC_Autoship_Block_Adminhtml_Customer_Paymentprofiles_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('paymentprofile_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('autoship')->__('Saved Credit Card'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label' => Mage::helper('autoship')->__('Information'),
            'title' => Mage::helper('autoship')->__('Information'),
            'content' => $this->getLayout()->createBlock('autoship/adminhtml_customer_paymentprofiles_edit_tab_form')->toHtml(),
        ));

        return parent::_beforeToHtml();
    }

}
