<?php
/**
 * Acaldeira Mercadolivre 
 * 
 * @category     Acaldeira
 * @package      Acaldeira_Mercadolivre 
 * @copyright    Copyright (c) 2015 MM (http://blog.meumagento.com.br/)
 * @author       MM (Thiago Caldeira de Lima)  
 * @version      Release: 0.1.0 
 */

class Novapc_Integracommerce_Block_Adminhtml_Integration_Renderer_Model extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    
    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        if ($value == 'Category') {
            return '<span>'. Mage::helper('integracommerce')->__('Exportar Categorias') .'</span>';
        } elseif ($value == 'Product Insert') {
            return '<span>'. Mage::helper('integracommerce')->__('Exportar Produtos') .'</span>';
        } elseif ($value == 'Product Update'){
            return '<span>'. Mage::helper('integracommerce')->__('Atualizar Produtos') .'</span>';
        }
    }
   
}