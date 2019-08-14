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

$sql = <<<SQL

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Table structure for table `sfc_autoship_migrated_subscription`
--

CREATE TABLE IF NOT EXISTS `{$this->getTable('autoship/migrated_subscription')}` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `subscription_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'subscription_id',
  `aw_subscription_id` int(11) NOT NULL COMMENT 'aw_subscription_id',
  `migration_status` varchar(16) NOT NULL COMMENT 'migration_status',
  `error_message` varchar(255) NULL COMMENT 'error_message',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Migrated Subscriptions Table' AUTO_INCREMENT=1 ;

SQL;

// Run script
$installer->run($sql);

/**
 * Clean up installer
 */
$installer->endSetup();

