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

ALTER TABLE  `npcintegra_integration` ADD  `requested_hour` int( 11 ) NULL DEFAULT 0;
ALTER TABLE  `npcintegra_integration` ADD  `requested_day` int( 11 ) NULL DEFAULT 0;
ALTER TABLE  `npcintegra_integration` ADD  `requested_week` int( 11 ) NULL DEFAULT 0;
ALTER TABLE  `npcintegra_integration` ADD  `available` int( 1 ) NULL DEFAULT 1;

");

$installer->endSetup();