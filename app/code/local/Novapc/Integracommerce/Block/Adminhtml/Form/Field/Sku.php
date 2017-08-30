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

class Novapc_Integracommerce_Block_Adminhtml_Form_Field_Sku
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn(
            'category',
            array(
                'label' => Mage::helper('integracommerce')->__('Categoria'),
                'size'  => 28
            )
        );

        $this->addColumn(
            'attribute',
            array(
                'label' => Mage::helper('integracommerce')->__('Attribute'),
                'size'  => 28
            )
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('integracommerce')->__('Novo Atributo');

        parent::__construct();
        $this->setTemplate('integracommerce/system/config/form/field/array_dropdown.phtml');
    }

    protected function _renderCellTemplate($columnName)
    {
        if (empty($this->_columns[$columnName])) {
            Mage::throwException('Nome da coluna especificado está errado.');
        }

        if ($columnName == 'category') {
            $column     = $this->_columns[$columnName];
            $inputName  = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';

            $rendered = '<select name="'.$inputName.'">';
            if ($columnName == 'category') {
                $categories = Mage::getModel('catalog/category')
                    ->getCollection()
                    ->addFieldToFilter(
                        'level',
                        array(
                            'gteq' => '2'
                        )
                    )
                    ->addAttributeToSelect('*');

                foreach ($categories as $category) {
                    $catName = str_replace("'", "\'", $category->getName());
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
                $productAttrs = Mage::getResourceModel('catalog/product_attribute_collection');
                
                foreach ($productAttrs as $productAttr) {
                    if ($productAttr->getData('is_configurable') > 0) {
                        $attrLabel = str_replace("'", "\'", $productAttr->getFrontendLabel());
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
