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

$entityTypeId = $installer->getEntityTypeId('catalog_product');

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

$installer->addAttribute('catalog_product', $codigo, $config);

$attributeId = $installer->getAttributeId($entityTypeId, 'integracommerce_sync');

$tablePrefix = Mage::getConfig()->getTablePrefix();
if (!empty($tablePrefix)) {
    $tableName = $tablePrefix . 'catalog_product_entity_int';
} else {
    $tableName = 'catalog_product_entity_int';
}

$installer->run(
    "INSERT IGNORE INTO `{$tableName}`
(`entity_type_id`, `attribute_id`, `entity_id`, `value`)
    SELECT '{$entityTypeId}', '{$attributeId}', `entity_id`, '0'
        FROM `{$tableName}`;"
);

$installer->endSetup();