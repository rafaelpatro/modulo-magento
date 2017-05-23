<?php

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Attributes
{
    public function toOptionArray()
    {
        $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
        $retornArray = array('not_selected' => 'Selecione o atributo...');
        foreach ($productAttrs as $productAttr) {
            $retornArray[] = array('value' => $productAttr->getAttributeCode(), 'label' => $productAttr->getFrontendLabel());
        }

        return $retornArray;
    }
}