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
$entityTypeId = $setup->getEntityTypeId('catalog_product');

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

$attributeId = $setup->getAttributeId($entityTypeId, 'integracommerce_sync');

$setup->run("
INSERT IGNORE INTO `{$installer->getTable('catalog_product_entity_int')}`
(`entity_type_id`, `attribute_id`, `entity_id`, `value`)
    SELECT '{$entityTypeId}', '{$attributeId}', `entity_id`, '0'
        FROM `{$installer->getTable('catalog_product_entity')}`;
");

$installer->endSetup();