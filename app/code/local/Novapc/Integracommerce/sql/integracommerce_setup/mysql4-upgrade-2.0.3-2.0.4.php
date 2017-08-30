<?php
/**
 * PHP version 5
 * Novapc Integracommerce
 *
 * @category  Magento
 * @package   Novapc_Integracommerce
 * @author    Novapc <novapc@novapc.com.br>
 * @copyright 2017 Integracommerce
 * @license   https://opensource.org/licenses/osl-3.0.php PHP License 3.0
 * @version   GIT: 1.0
 * @link      https://github.com/integracommerce/modulo-magento
 */

$installer = $this;
$installer->startSetup();

$quoteTable = $installer->getTable('sales/quote_payment');
$orderTable = $installer->getTable('sales/order_payment');

if (!$installer->getConnection()->tableColumnExists($quoteTable, 'integracommerce_name')) {
    $installer->run("ALTER TABLE `{$quoteTable}` ADD `integracommerce_name` VARCHAR(255) NULL DEFAULT NULL;");
}

if (!$installer->getConnection()->tableColumnExists($orderTable, 'integracommerce_name')) {
    $installer->run("ALTER TABLE `{$orderTable}` ADD `integracommerce_name` VARCHAR(255) NULL DEFAULT NULL;");
}

if (!$installer->getConnection()->tableColumnExists($quoteTable, 'integracommerce_installments')) {
    $installer->run("ALTER TABLE `{$quoteTable}` ADD `integracommerce_installments` int(11) NULL DEFAULT NULL;");
}

if (!$installer->getConnection()->tableColumnExists($orderTable, 'integracommerce_installments')) {
    $installer->run("ALTER TABLE `{$orderTable}` ADD `integracommerce_installments` int(11) NULL DEFAULT NULL;");
}

$installer->endSetup();