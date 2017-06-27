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

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Attributes
{
    public function toOptionArray()
    {
        $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
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