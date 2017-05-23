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
 
$attrCode = 'integracommerce_sync';

$collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*');
$size = $collection->getSize();
$sizeFiltered = 0;

$i = 1;
$firstBatch = 1;
do {
	$collection->addAttributeToSelect('*');
	$collection->setPageSize(100);
	$collection->setCurPage($i);
	$collection->clear();
	$sizeFiltered = $sizeFiltered + 100;  

    if ($sizeFiltered <= $size || $firstBatch == 1) {
    	foreach ($collection as $product) {
			$product->setData($attrCode, '0')
		         	->getResource()
		         	->saveAttribute($product, $attrCode);
    	}

        $i++;
        $firstBatch = 0;    	
    } else {
    	$i = 0;
    }
} while ($i > 0);