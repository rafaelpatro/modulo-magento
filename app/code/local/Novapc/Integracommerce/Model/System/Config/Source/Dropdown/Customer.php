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

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Customer
{
    public function toOptionArray()
    {
        $productAttrs = Mage::getResourceModel('eav/entity_attribute_collection')->setEntityTypeFilter(2);
        $retornArray = array('not_selected' => 'Selecione o atributo...');
        foreach ($productAttrs as $productAttr) {
            $retornArray[] = array(
                'value' => $productAttr->getAttributeCode(),
                'label' => $productAttr->getFrontendLabel()
            );
        }

        return $retornArray;
    }
}