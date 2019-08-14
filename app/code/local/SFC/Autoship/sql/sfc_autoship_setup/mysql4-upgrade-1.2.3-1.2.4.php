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
        'global' => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
        'visible' => true,
        'required' => false,
        'apply_to' => 'simple,bundle,configurable',
        'user_defined' => false,
        'searchable' => false,
        'filterable' => false,
        'comparable' => false,
        'visible_on_front' => false,
        'visible_in_advanced_search' => false,
        'unique' => false
    ));
}

$sql = <<<SQL

INSERT INTO `{$this->getTable('catalog_product_entity_int')}`
(entity_type_id, attribute_id, store_id, entity_id, value)
  SELECT
    (SELECT entity_type_id FROM `{$this->getTable('eav/entity_type')}` WHERE entity_type_code = 'catalog_product'),
    (SELECT attribute_id FROM `{$this->getTable('eav/attribute')}` WHERE attribute_code = 'subscription_enabled'),
    (SELECT store_id FROM `{$this->getTable('core/store')}` WHERE code = 'admin'),
    product_id entity_id,
    enabled `value`
  FROM `{$this->getTable('autoship/legacy_product_profile')}`
;

DROP TABLE `{$this->getTable('autoship/legacy_product_profile')}`;

SQL;

// Run script
$installer->run($sql);

/**
 * Clean up installer
 */
$installer->endSetup();

