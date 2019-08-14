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

SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

    CREATE TABLE IF NOT EXISTS `{$this->getTable('autoship/migrated_cimProfile')}` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `cim_token` varchar(255),
      `spreedly_token` varchar(255),
      `status` varchar(25) DEFAULT '',
      `error_message` text DEFAULT '',
      PRIMARY KEY (`id`),
      UNIQUE KEY `IDX_SFC_AUTOSHIP_CIM_PR_CIM_TOKEN` (`cim_token`),
      UNIQUE KEY `IDX_SFC_AUTOSHIP_CIM_PR_SPREE_TOKEN` (`spreedly_token`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;

SQL;

// Run script
$installer->run($sql);

/**
 * Clean up installer
 */
$installer->endSetup();
