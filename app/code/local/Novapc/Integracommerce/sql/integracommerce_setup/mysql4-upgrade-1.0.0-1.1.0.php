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
    $tableName = $tablePrefix . 'npcintegra_order';
} else {
    $tableName = 'npcintegra_order';
}

$installer->run(
    "CREATE TABLE IF NOT EXISTS `" . $tableName . "` (
      `entity_id` int(45) AUTO_INCREMENT PRIMARY KEY,
      `integra_id` VARCHAR(245)  UNIQUE NOT NULL,
      `marketplace_id` VARCHAR(245)  NULL DEFAULT NULL,
      `marketplace_name` VARCHAR(145) NOT NULL,
      `store_name` VARCHAR(145) NOT NULL,
      `updated_marketplace_status` TINYINT(1) NOT NULL,
      `estimated_delivery_date` timestamp NULL DEFAULT NULL,
      `customer_pf_cpf` VARCHAR(245) NULL DEFAULT NULL,
      `customer_pf_name` VARCHAR(145) NULL DEFAULT NULL,
      `customer_pj_cnpj` VARCHAR(245) NULL DEFAULT NULL,
      `customer_pj_ie` VARCHAR(245) NULL DEFAULT NULL,
      `customer_pj_corporate_name` VARCHAR(145) NULL DEFAULT NULL,
      `delivery_street` VARCHAR(200) NULL DEFAULT NULL,
      `delivery_additional_info` VARCHAR(180) NULL DEFAULT NULL,
      `delivery_neighborhood` VARCHAR(180) NULL DEFAULT NULL,
      `delivery_city` VARCHAR(245) NULL DEFAULT NULL,
      `delivery_reference` VARCHAR(245) NULL DEFAULT NULL,
      `delivery_state` VARCHAR(2) NULL DEFAULT NULL,
      `delivery_number` VARCHAR(10) NULL DEFAULT NULL,
      `telephone_main` VARCHAR(245) NULL DEFAULT NULL,
      `telephone_secondary` VARCHAR(245) NULL DEFAULT NULL,
      `telephone_business` VARCHAR(245) NULL DEFAULT NULL,
      `total_amount` decimal(12,4) NULL DEFAULT NULL,
      `total_freight` decimal(12,4) NULL DEFAULT NULL,
      `total_discount` decimal(12,4) NULL DEFAULT NULL,
      `customer_birthday` date NULL DEFAULT NULL,
      `order_status` VARCHAR(245) NULL DEFAULT NULL,
      `invoiced_number` VARCHAR(245) NULL DEFAULT NULL,
      `invoiced_line` TINYINT(100) NULL DEFAULT NULL,
      `invoiced_key` VARCHAR(50) NULL DEFAULT NULL,
      `invoiced_danfe_xml` VARCHAR(245) NULL DEFAULT NULL,
      `shipping_tracking_url` VARCHAR(245) NULL DEFAULT NULL,
      `shipping_tracking_protocol` VARCHAR(245) NULL DEFAULT NULL,
      `shipped_estimated_delivery` timestamp NULL DEFAULT NULL,
      `shipped_carrier_at` timestamp NULL DEFAULT NULL,
      `shipped_carrier_name` VARCHAR(145) NOT NULL,
      `shipment_exception_observation` VARCHAR(245) NULL DEFAULT NULL,
      `shipment_exception_occurrence_at` timestamp NULL DEFAULT NULL,
      `delivered_at` timestamp NULL DEFAULT NULL,
      `products_skus` VARCHAR(245) NOT NULL,  
      `magento_order_id` bigint(45) UNIQUE NULL DEFAULT NULL,
      `magento_customer_id` bigint(11) NULL DEFAULT NULL,
      `customer_email` VARCHAR(245) NULL DEFAULT NULL,
      `inserted_at` timestamp NULL DEFAULT NULL,
      `purchased_at` timestamp NULL DEFAULT NULL,
      `approved_at` timestamp NULL DEFAULT NULL,
      `updated_at` timestamp NULL DEFAULT NULL  
      ) "
);
 
$installer->endSetup();

