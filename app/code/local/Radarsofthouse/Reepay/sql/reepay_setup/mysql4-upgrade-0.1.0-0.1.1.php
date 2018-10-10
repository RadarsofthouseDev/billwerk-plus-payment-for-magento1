<?php
/**
 * Reepay payment extension for Magento
 *
 * @author      Radarsofthouse Team <info@radarsofthouse.dk>
 * @category    Radarsofthouse
 * @package     Radarsofthouse_Reepay
 * @copyright   Radarsofthouse (https://www.radarsofthouse.dk/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */

$this->startSetup();
$this->getConnection()->addColumn($this->getTable('reepay_order_status'), 'error', 'varchar(128) default NULL');
$this->getConnection()->addColumn($this->getTable('reepay_order_status'), 'error_state', 'varchar(128) default NULL');
$this->endSetup();
