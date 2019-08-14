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
 * Uninstall Script
 *   You can run the below PHP and SQL code to cleanup after the module is removed.
 */

/**
 * Installer
 */
$installer = $this;
$installer->startSetup();

$installer->removeAttribute('catalog_product', 'subscription_enabled');

$sql = <<<SQL

ALTER TABLE sales_flat_quote_item DROP new_subscription_interval;
ALTER TABLE sales_flat_quote_item DROP create_new_subscription_at_checkout;
ALTER TABLE sales_flat_quote_item DROP subscription_id;
ALTER TABLE sales_flat_quote_item DROP item_fulfils_subscription;

ALTER TABLE sales_flat_order_item DROP subscription_id;
ALTER TABLE sales_flat_order_item DROP item_fulfils_subscription;

DELETE FROM core_resource WHERE code = 'sfc_autoship_setup';

SQL;

$installer->run($sql);

/**
 * Clean up installer
 */
$installer->endSetup();
