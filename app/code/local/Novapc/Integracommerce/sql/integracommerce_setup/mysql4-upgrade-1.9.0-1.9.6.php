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
        
TRUNCATE TABLE `npcintegra_integration`;

ALTER TABLE `npcintegra_integration` MODIFY `status` timestamp NULL DEFAULT NULL;

INSERT INTO `npcintegra_integration` (`integra_model`, `status`) VALUES ('Category', NULL);
INSERT INTO `npcintegra_integration` (`integra_model`, `status`) VALUES ('Product Insert', NULL);
INSERT INTO `npcintegra_integration` (`integra_model`, `status`) VALUES ('Product Update', NULL);
 
    ");

$installer->endSetup();