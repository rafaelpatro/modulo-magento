<?php

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Customer
{
    public function toOptionArray()
    {
        $productAttrs = Mage::getResourceModel('eav/entity_attribute_collection')->setEntityTypeFilter(2);
        $retornArray = array('not_selected' => 'Selecione o atributo...');
        foreach ($productAttrs as $productAttr) {
            $retornArray[] = array('value' => $productAttr->getAttributeCode(), 'label' => $productAttr->getFrontendLabel());
        }

        return $retornArray;
    }
}