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

class SFC_Autoship_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Constants
     */
    // Log file name
    const LOG_FILE = 'autoship.log';
    const API_LOG_FILE = 'autoship_platform_api.log';
    const MIGRATION_LOG_FILE = 'autoship_migration.log';

    /**
     * Example of how logging should be done in this extension:
     *     Mage::log($message, Zend_Log::ERR, SFC_Autoship_Helper_Data::LOG_FILE);
     */

    /**
     * Output the current call stack to module log file
     */
    public function logCallStack()
    {
        $exception = new Exception;
        Mage::log("Current call stack:\n" . $exception->getTraceAsString(), Zend_Log::INFO, self::LOG_FILE);
    }

}
