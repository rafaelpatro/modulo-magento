<?php
/**
 * Novapc Integracommerce
 * 
 * @category     Novapc
 * @package      Novapc_Integracommerce
 * @copyright    Copyright (c) 2016 NovaPC (http://www.novapc.com.br/)
 * @author       Novapc
 * @version      Release: 1.0.0 
 */

$installer = $this; 
$installer->startSetup();

$installer->run("
        

CREATE TABLE IF NOT EXISTS `npcintegra_attributes` (
  `entity_id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `nbm_origin` varchar(245) NULL DEFAULT NULL,
  `nbm_number` varchar(245) NULL DEFAULT NULL,
  `warranty` varchar(245) NULL DEFAULT NULL,
  `brand` varchar(245) NULL DEFAULT NULL,
  `height` varchar(245) NULL DEFAULT NULL,
  `width` varchar(245) NULL DEFAULT NULL,
  `length` varchar(245) NULL DEFAULT NULL,
  `weight` varchar(245) NULL DEFAULT NULL,
  `ean` varchar(245) NULL DEFAULT NULL,
  `ncm` varchar(245) NULL DEFAULT NULL,
  `isbn` varchar(245) NULL DEFAULT NULL
  )
 
    ");

$installer->endSetup();