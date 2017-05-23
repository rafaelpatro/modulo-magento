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
        

CREATE TABLE IF NOT EXISTS `npcintegra_queue` (
  `entity_id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `identificator` varchar(245) NULL DEFAULT NULL,
  `sent_json` varchar(600) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL,
  `type` varchar(100) NULL DEFAULT NULL,
  `done` int(1) NULL DEFAULT NULL
  )
 
    ");

$installer->endSetup();