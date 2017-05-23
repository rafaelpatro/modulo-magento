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
        

INSERT INTO `npcintegra_attributes` (`nbm_origin`, `nbm_number`, `warranty`, `brand`, `height`, `width`, `length`, `weight`, `ean`, `ncm`, `isbn`) VALUES (NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
 
    ");

$installer->endSetup();