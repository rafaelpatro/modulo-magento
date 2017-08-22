<?php
/**
 * PHP version 5
 * Novapc Integracommerce
 *
 * @category  Magento
 * @package   Novapc_Integracommerce
 * @author    Novapc <novapc@novapc.com.br>
 * @copyright 2017 Integracommerce
 * @license   https://opensource.org/licenses/osl-3.0.php PHP License 3.0
 * @version   GIT: 1.0
 * @link      https://github.com/integracommerce/modulo-magento
 */

$installer = $this;
$installer->startSetup();

$entityTypeId = $installer->getEntityTypeId('catalog_category');

$installer->addAttribute(
    Mage_Catalog_Model_Category::ENTITY, 'integracommerce_active', array(
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
    )
);

$attributeId = $installer->getAttributeId($entityTypeId, 'integracommerce_active');

$tablePrefix = Mage::getConfig()->getTablePrefix();
if (!empty($tablePrefix)) {
    $tableName = $tablePrefix . 'catalog_category_entity_int';
} else {
    $tableName = 'catalog_category_entity_int';
}

$installer->run(
    "INSERT IGNORE INTO `{$tableName}`
(`entity_type_id`, `attribute_id`, `entity_id`, `value`)
    SELECT '{$entityTypeId}', '{$attributeId}', `entity_id`, '0'
        FROM `{$tableName}`;"
);

$installer->endSetup();