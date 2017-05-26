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

$entityTypeId = $installer->getEntityTypeId('catalog_category');

$installer->addAttribute(Mage_Catalog_Model_Category::ENTITY, 'integracommerce_active', array(
    'group'         => 'General Information',
    'input'         => 'select',
    'type'          => 'int',
    'label'         => 'Integracommerce - Sincronizado',
    'backend'       => '',
    'default'       => 0,
    'source'        => 'eav/entity_attribute_source_boolean',
    'visible'       => true,
    'required'      => false,
    'visible_on_front' => false,
    'user_defined'  =>  true,
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
));

$attributeId = $installer->getAttributeId($entityTypeId, 'integracommerce_active');

$installer->run("
INSERT IGNORE INTO `{$installer->getTable('catalog_category_entity_int')}`
(`entity_type_id`, `attribute_id`, `entity_id`, `value`)
    SELECT '{$entityTypeId}', '{$attributeId}', `entity_id`, '0'
        FROM `{$installer->getTable('catalog_category_entity')}`;
");

$installer->endSetup();