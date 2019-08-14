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

/**
 * Add product attributes to track subscription products
 */
if (!$installer->getAttributeId('catalog_product', 'subscription_enabled')) {
    $installer->addAttribute('catalog_product', 'subscription_enabled', array(
        'group' => 'Subscribe Pro',
        'label' => 'Subscription Enabled',
        'type' => 'int',
        'input' => 'select',
        'default' => '0',
        'class' => '',
        'backend' => '',
        'frontend' => '',
        'source' => 'eav/entity_attribute_source_boolean',
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE,
        'visible' => true,
        'required' => false,
        'apply_to' => 'simple,bundle,configurable,virtual,downloadable',
        'user_defined' => false,
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
        'visible_on_front' => false,
        'visible_in_advanced_search' => false,
        'unique' => false
    ));
}

/**
 * Add attributes to quote and order items
 */
$installer->addAttribute('order_item', 'item_fulfils_subscription', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'visible'  => true,
    'required' => false
));
$installer->addAttribute('order_item', 'subscription_id', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'visible'  => true,
    'required' => false
));
$installer->addAttribute('order_item', 'subscription_interval', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'visible'  => true,
    'required' => false
));
$installer->addAttribute('quote_item', 'item_fulfils_subscription', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'visible'  => true,
    'required' => false
));
$installer->addAttribute('quote_item', 'subscription_id', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'visible'  => true,
    'required' => false
));
$installer->addAttribute('quote_item', 'subscription_interval', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'visible'  => true,
    'required' => false
));

$installer->addAttribute('quote_item', 'create_new_subscription_at_checkout', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'visible'  => true,
    'required' => false
));
$installer->addAttribute('quote_item', 'new_subscription_interval', array(
    'type'     => Varien_Db_Ddl_Table::TYPE_VARCHAR,
    'visible'  => true,
    'required' => false
));

/**
 * Clean up installer
 */
$installer->endSetup();

