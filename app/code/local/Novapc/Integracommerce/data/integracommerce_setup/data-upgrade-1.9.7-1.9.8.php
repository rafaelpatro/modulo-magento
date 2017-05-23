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
$configValue = Mage::getStoreConfig('catalog/frontend/flat_catalog_category');

if ($configValue == 1) {
    $indexer = Mage::getModel('index/indexer')->getProcessByCode('catalog_category_flat');
    $indexer->reindexEverything();
    $storeId = Mage::app()->getStore()->getStoreId();
} else {
    $storeId = 0;
    $attrCode = 'integracommerce_active';
    $categoriesIds = Mage::getModel('catalog/category')->getCollection()->getAllIds();
    foreach ($categoriesIds as $categoryId) {
        $category = Mage::getModel('catalog/category')
            ->setStoreId($storeId)
            ->load($categoryId);

        $category->setData($attrCode, '0')
            ->getResource()
            ->saveAttribute($category, $attrCode);
    }
}

