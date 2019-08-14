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

class SFC_Autoship_Model_System_Config_Source_Nextorderdatemode
{

    const MODE_SINGLE_DATE = 0;
    const MODE_MULTIPLE_DATES = 1;

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::MODE_SINGLE_DATE, 'label'=>Mage::helper('adminhtml')->__('Force Single Date')),
            array('value' => self::MODE_MULTIPLE_DATES, 'label'=>Mage::helper('adminhtml')->__('Allow Multiple Dates')),
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $valueArray = array();
        foreach($this->toOptionArray() as $curElement) {
            $valueArray[$curElement['value']] = $curElement['label'];
        }
        return $valueArray;
    }

}
