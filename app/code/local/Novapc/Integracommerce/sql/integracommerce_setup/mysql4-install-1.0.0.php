<?php
/**
 * Novapc Integracommerce
 * 
 * @category     Novapc
 * @package      Novapc_Integracommerce 
 * @copyright    Copyright (c) 2016 Novapc (http://www.novapc.com.br/)
 * @author       Novapc
 * @version      Release: 1.0.0 
 */
 
$installer = $this;
 
$installer->startSetup();
 
$installer->run("
		

CREATE TABLE IF NOT EXISTS `npcintegra_integration` (
  `entity_id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `integra_model` varchar(245) NULL DEFAULT NULL,
  `status` int(1) NULL DEFAULT NULL
  )
 
    ");
 
$installer->endSetup();

