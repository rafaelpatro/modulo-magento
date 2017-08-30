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

class Novapc_Integracommerce_Model_Resource_Integration_Collection
    extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('integracommerce/integration');
    }

    public function updateCategories($categoryIds, $attributeCode, $attrValue)
    {
        $tablePrefix = Mage::getConfig()->getTablePrefix();
        if (!empty($tablePrefix)) {
            $catTable = $tablePrefix . 'catalog_category_entity_int';
        } else {
            $catTable = 'catalog_category_entity_int';
        }

        $stringIds = implode(",", $categoryIds);
        $attributeId = Mage::getResourceModel('eav/entity_attribute')
            ->getIdByCode('catalog_category', $attributeCode);
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $write->update(
            $catTable,
            array("value" => $attrValue),
            "entity_id in (". $stringIds . ") AND attribute_id=" . $attributeId
        );
    }

}