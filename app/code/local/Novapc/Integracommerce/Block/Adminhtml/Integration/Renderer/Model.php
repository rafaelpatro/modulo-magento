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

class Novapc_Integracommerce_Block_Adminhtml_Integration_Renderer_Model
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    
    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        if ($value == 'Category') {
            return '<span>'. Mage::helper('integracommerce')->__('Exportar Categorias') .'</span>';
        } elseif ($value == 'Product Insert') {
            return '<span>'. Mage::helper('integracommerce')->__('Exportar Produtos') .'</span>';
        } elseif ($value == 'Product Update') {
            return '<span>'. Mage::helper('integracommerce')->__('Atualizar Produtos') .'</span>';
        }
    }
   
}