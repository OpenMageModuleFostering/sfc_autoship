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
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @author    Garth Brantley <garth@subscribepro.com>
 * @copyright 2009-2014 SUBSCRIBE PRO INC. All Rights Reserved.
 * @license   http://www.subscribepro.com/terms-of-service/ Subscribe Pro Terms of Service
 * @link      http://www.subscribepro.com/
 *
 */

define('MAGENTO_ROOT', dirname(dirname(__FILE__)));

require (MAGENTO_ROOT . '/app/Mage.php');

if (!Mage::isInstalled()) {
    die("Application is not installed yet, please complete install wizard first.\n");
}



// Only for urls
// Don't remove this
$_SERVER['SCRIPT_NAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_NAME']);
$_SERVER['SCRIPT_FILENAME'] = str_replace(basename(__FILE__), 'index.php', $_SERVER['SCRIPT_FILENAME']);

Mage::app('admin')->setUseSessionInUrl(false);

umask(0);

$valid_codes = array('daily_subscriptions',
    'complete_subscriptions',
    'subscription_history',
    'expired_credit_card',
    'customer_activity',
    'failed_subscriptions',
    'subscription_order_history',
    'complete_sales_orders',
    'complete_transaction',
    'products'
);

$args = getopt("", array("code:"));

if (!sizeof($args) || !isset($args['code'])) {
    eLog("No code provided, please provide a valid report code, code options are:");
    eLog($valid_codes);
    exit;
}

$code = $args['code'];

if (!in_array($code, $valid_codes)) {
    eLog("Invalid code provided, code options are:");
    eLog($valid_codes);
    exit;
}

$report_dir = MAGENTO_ROOT . '/var/autoship_report_archive/' . $code;

if (!file_exists(($report_dir))) {
    //0775 in case apache/php is running as a different user in same group
    mkdir($report_dir, 0775, true);
}

$file_name = $report_dir . '/' . $code . '_' . date("Y-m-d_H:i:s") . '.csv';

if (!is_writeable($report_dir)) {
    eLog("Unable to write to report archive: " . $file_name);
    exit;
}

/** @var SFC_Autoship_Helper_Platform $platformHelper */
$platformHelper = Mage::helper('autoship/platform');

$report = $platformHelper->getReport($code);

file_put_contents($file_name, $report);

eLog("Success!");
eLog("Downloaded report to: " . $file_name);


function eLog($message)
{
    if (is_string($message)) {
        echo $message . "\n";
    }
    else {
        print_r($message);
    }
}
