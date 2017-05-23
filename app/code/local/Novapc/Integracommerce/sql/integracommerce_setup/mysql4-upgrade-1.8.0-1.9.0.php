<?php
/**
 * Novapc Integracommerce
 * 
 * @category     Novapc
 * @package      Novapc_Integracommerce 
 * @copyright    Copyright (c) 2016 Novapc (http://www.novapc.com.br/)
 * @author       Novapc
 * @version      Release: 0.1.0 
 */

$installer = $this; 
$installer->startSetup();

$installer->run("
        

CREATE TABLE IF NOT EXISTS `npcintegra_product_queue` (
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
  );
 
    ");

$installer->endSetup();