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
 * Remove all legacy tables
 */
$sql = <<<SQL

DROP TABLE IF EXISTS `{$this->getTable('autoship/migrated_subscription_item')}`;
DROP TABLE IF EXISTS `{$this->getTable('autoship/migrated_subscription')}`;
DROP TABLE IF EXISTS `{$this->getTable('autoship/migrated_cimProfile')}`;
DROP TABLE IF EXISTS `{$this->getTable('autoship/adapter_ordergroove_row')}`;
DROP TABLE IF EXISTS `{$this->getTable('autoship/subscription_order')}`;
DROP TABLE IF EXISTS `{$this->getTable('autoship/legacy_product_profile')}`;

SQL;

$installer->run($sql);

/**
 * Clean up installer
 */
$installer->endSetup();
