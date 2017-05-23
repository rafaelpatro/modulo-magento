<?php
class Novapc_Integracommerce_Model_System_Config_Backend_Sku extends Mage_Core_Model_Config_Data
{
    public function _afterLoad()
    {
        if (!is_array($this->getValue())) {
            $value = $this->getValue();
            $this->setValue(empty($value) ? false : unserialize($value));
        }
    }    

    public function _beforeSave()
    {   
        $value = $this->getValue();
        
        $clearCollection = Mage::getModel('integracommerce/sku')->getCollection();
        
        if (!empty($clearCollection) && $clearCollection) {
            foreach ($clearCollection as $item) {
                $item->delete();
            }
        }

        foreach ($value as $key => $newValue) {
            if (empty($newValue)) {
                continue;
            }

            $integraAttrs = Mage::getModel('integracommerce/sku');
            $integraAttrs->setData('category',$newValue['category']);
            $integraAttrs->setData('attribute',$newValue['attribute']);
            $integraAttrs->save();                    
        }
        
        if (empty($value['__empty']) && count($value) <= 1) {
            $this->setValue(null);
        } else {
            unset($value['__empty']);
            $this->setValue(serialize($value));
        }
    }     
}