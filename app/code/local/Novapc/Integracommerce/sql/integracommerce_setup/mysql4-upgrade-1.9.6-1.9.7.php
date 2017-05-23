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
        
CREATE TABLE IF NOT EXISTS `npcintegra_sku_attributes` (
  `entity_id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `category` varchar(245) NULL DEFAULT NULL,
  `attribute` varchar(245) NULL DEFAULT NULL
  );
 
    ");

$installer->endSetup();