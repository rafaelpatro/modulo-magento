<?php

class Novapc_Integracommerce_Block_Adminhtml_Form_Field_Sku extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('category', array(
            'label' => Mage::helper('integracommerce')->__('Categoria'),
            'size'  => 28
        ));
        $this->addColumn('attribute', array(
            'label' => Mage::helper('integracommerce')->__('Attribute'),
            'size'  => 28
        ));

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('integracommerce')->__('Add New Attribute');

        parent::__construct();
        $this->setTemplate('integracommerce/system/config/form/field/array_dropdown.phtml');
    }

    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            throw new Exception('Wrong column name specified.');
        }

        if ($columnName == 'category') {
            $column     = $this->_columns[$columnName];
            $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

            $rendered = '<select name="'.$inputName.'">';
            if ($columnName == 'category') {
                $categories = Mage::getModel('catalog/category')
                    ->getCollection()
                    ->addFieldToFilter('level', array(
                        'eq'     => '2'
                    ))
                    ->addAttributeToSelect('*');

                foreach ($categories as $category) {
                    $catName = str_replace("'","\'",$category->getName());
                    $rendered .= '<option value="'.$category->getId().'">'.$catName.'</option>';
                }
            }
            $rendered .= '</select>';

            return $rendered;
        } elseif ($columnName == 'attribute') {
            $column     = $this->_columns[$columnName];
            $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

            $rendered = '<select name="'.$inputName.'">';
            if ($columnName == 'attribute') {
                //$attributeSetId = Mage::getStoreConfig('mercadolivre/general/attribute_set',Mage::app()->getStore());
                $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
                //->setAttributeSetFilter($attributeSetId);

                foreach ($productAttrs as $productAttr) {
                    if ($productAttr->getData('is_configurable') > 0) {
                        $attrLabel = str_replace("'","\'",$productAttr->getFrontendLabel());
                        $rendered .= '<option value="'.$productAttr->getAttributeCode().'">'.$attrLabel.'</option>';
                    } else {
                        continue;
                    }
                }
            }
            $rendered .= '</select>';

            return $rendered;
        }
    }      
    
}
