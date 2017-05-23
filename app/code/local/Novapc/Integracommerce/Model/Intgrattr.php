<?php

class Novapc_Integracommerce_Model_Intgrattr extends Mage_Core_Model_Config_Data
{
    public function _afterSave()
    {
    	$integraAttrs = Mage::getModel('integracommerce/attributes')->load(1,'entity_id');
    	$integraAttrs->setData($this->getField(),$this->getValue());
    	$integraAttrs->save();
    }
} 