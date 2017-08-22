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

$tablePrefix = Mage::getConfig()->getTablePrefix();
if (!empty($tablePrefix)) {
    $tableName = $tablePrefix . 'npcintegra_product_queue';
} else {
    $tableName = 'npcintegra_product_queue';
}

$installer->run(
    "CREATE TABLE IF NOT EXISTS `". $tableName . "` (
      `entity_id` int(11) AUTO_INCREMENT PRIMARY KEY,
      `product_id` int(11) NULL DEFAULT NULL,
      `product_body` varchar(1000) NULL DEFAULT NULL,
      `product_error` varchar(1000) NULL DEFAULT NULL,
      `sku_body` varchar(1000) NULL DEFAULT NULL,
      `sku_error` varchar(1000) NULL DEFAULT NULL,  
      `price_body` varchar(1000) NULL DEFAULT NULL,
      `price_error` varchar(1000) NULL DEFAULT NULL,  
      `stock_body` varchar(1000) NULL DEFAULT NULL,
      `stock_error` varchar(1000) NULL DEFAULT NULL
      );"
);

$installer->endSetup();