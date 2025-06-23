<?php
/**
 * Frisbii Pay extension for Magento
 *
 * @author      Radarsofthouse Team <info@radarsofthouse.dk>
 * @category    Radarsofthouse
 * @package     Radarsofthouse_Reepay
 * @copyright   Radarsofthouse (https://www.radarsofthouse.dk/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
 
$installer = $this;
$installer->startSetup();

$installer->run("
CREATE TABLE IF NOT EXISTS {$this->getTable('reepay_order_status')}(
	id 				int(32) 	NOT NULL auto_increment,
	order_id 			varchar(32) default NULL,
	status 			varchar(64)	default NULL,
	first_name 		varchar(128) default NULL,
	last_name 		varchar(128) default NULL,
	email 			varchar(128) default NULL,
	token 			varchar(64) default NULL,
	masked_card_number 			varchar(32) default NULL,
	fingerprint 		varchar(64) default NULL,
	card_type 		varchar(64) default NULL,
	PRIMARY KEY  (id),
	KEY order_id (order_id)
);
");

$installer->endSetup();
