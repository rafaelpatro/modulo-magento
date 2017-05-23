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

class Novapc_Integracommerce_Block_Adminhtml_Integration_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    
    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        if (!$value || empty($value)) {
            return '<span style="color:red;">'. Mage::helper('integracommerce')->__('A Sincronizar') .'</span>';
        } else {
            $date = strtotime($value);
            $newformat = date('d/m/Y H:i:s',$date);
            return '<span>'. $newformat .'</span>';
        }
    }
   
}