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

$configValue = Mage::getStoreConfig('catalog/frontend/flat_catalog_product');

if ($configValue == 1) {
    $indexer = Mage::getModel('index/indexer')->getProcessByCode('catalog_product_flat');
    $indexer->reindexEverything();
}
