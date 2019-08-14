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

$sql = <<<SQL

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

ALTER TABLE `{$this->getTable('autoship/migrated_subscription')}` DROP COLUMN `subscription_id`;

--
-- Table structure for table `sfc_autoship_migrated_subscription_item`
--
CREATE TABLE IF NOT EXISTS `{$this->getTable('autoship/migrated_subscription_item')}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `migrated_subscription_id` int(10) unsigned NOT NULL COMMENT 'migrated_subscription_id',
  `aw_subscription_item_id` int(11) NOT NULL COMMENT 'aw_subscription_item_id',
  `platform_subscription_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'platform_subscription_id',
  `migration_status` varchar(16) NOT NULL COMMENT 'migration_status',
  `error_message` varchar(255) NULL COMMENT 'error_message',
  INDEX (migrated_subscription_id),
  INDEX (aw_subscription_item_id),
  PRIMARY KEY (`id`),
  CONSTRAINT `FK_SFC_AUTOSHIP_MIGRATED_SUB_ITEM` FOREIGN KEY (`migrated_subscription_id`)
    REFERENCES `{$this->getTable('autoship/migrated_subscription')}` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Migrated Subscriptions Items Table' AUTO_INCREMENT=1 ;

SQL;

// Run script
$installer->run($sql);

/**
 * Clean up installer
 */
$installer->endSetup();

