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
 * Installer
 */
$installer = $this;
$installer->startSetup();

// Update subscription_enabled product attribute to move it to website scope
$installer->updateAttribute(
    'catalog_product',
    'subscription_enabled',
    'is_global',
    Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE
);


/**
 * Clean up installer
 */
$installer->endSetup();
