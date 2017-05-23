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
    'global'        => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
));

$installer->endSetup();