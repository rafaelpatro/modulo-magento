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

$attributesSets = Mage::getResourceModel('eav/entity_attribute_set_collection')->setEntityTypeFilter(4);
$entityTypeId = $installer->getEntityTypeId('catalog_product');
// adding attribute group
foreach ($attributesSets as $attrSet) {
    $installer->addAttributeGroup('catalog_product', $attrSet->getAttributeSetName(), 'Integracommerce', 1000);
}

// Add attribute to product attribute set
$codigo = 'integracommerce_active';
$config = array(
    'group'    => 'Integracommerce',
    'position' => 1,
    'label'    => 'Sincronizado',
    'user_defined' => true,
    'type'     => 'int',
    'input'    => 'boolean',
    'apply_to' => 'simple,bundle,grouped,configurable',
    'default'  => 0,
    'required' => 1,
    'note'     => 'Se este produto não foi sincronizado com o Integracommerce, marque Não.'
);

$installer->addAttribute('catalog_product', $codigo, $config);

$attributeId = $installer->getAttributeId($entityTypeId, 'integracommerce_active');

$installer->run(
    "INSERT IGNORE INTO `{$installer->getTable('catalog_product_entity_int')}`
    (`entity_type_id`, `attribute_id`, `entity_id`, `value`)
    SELECT '{$entityTypeId}', '{$attributeId}', `entity_id`, '0'
        FROM `{$installer->getTable('catalog_product_entity')}`;"
);

$installer->endSetup();