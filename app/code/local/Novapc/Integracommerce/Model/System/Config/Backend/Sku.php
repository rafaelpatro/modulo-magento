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

        $categoryData = array();
        $attributeData = array();
        foreach ($value as $key => $newValue) {
            if (empty($newValue)) {
                continue;
            }

            $categoryId = $newValue['category'];
            $attributeCode = $newValue['attribute'];
            $categoryData[$categoryId] = $categoryId;
            $attributeData[$categoryId] = $attributeCode;
        }

        Mage::getModel('integracommerce/sku')->getCollection()->bulkInsert($categoryData, $attributeData);
        
        if (empty($value['__empty']) && count($value) <= 1) {
            $this->setValue(null);
        } else {
            unset($value['__empty']);
            $this->setValue(serialize($value));
        }
    }     
}