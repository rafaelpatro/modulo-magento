<?php

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Prodattr
{
    public function toOptionArray()
    {
        $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
        $retornArray = array();
        foreach ($productAttrs as $productAttr) {
            $retornArray[] = array('value' => $productAttr->getAttributeCode(), 'label' => $productAttr->getFrontendLabel());
        }

        return $retornArray;
    }
}