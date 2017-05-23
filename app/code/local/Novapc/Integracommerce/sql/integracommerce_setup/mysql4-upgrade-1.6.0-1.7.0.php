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

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');

//$attributesSets = Mage::getResourceModel('eav/entity_attribute_set_collection')->setEntityTypeFilter(4);

//foreach ($attributesSets as $attrSet) {
    $codigo = 'integracommerce_sync';
    $config = array(
        'group'    => 'Integracommerce',
        'position' => 2,
        'label'    => 'Sincronizar Produto',
        'user_defined' => true,
        'type'     => 'int',
        'input'    => 'boolean',
        'apply_to' => 'simple,bundle,grouped,configurable',
        'default'  => 0,
        'required' => 1,
        'note'     => 'Se deseja sincronizar este produto com o Integracommerce, marque Sim.'
    );

    $setup->addAttribute('catalog_product', $codigo, $config);
//}

$installer->endSetup();