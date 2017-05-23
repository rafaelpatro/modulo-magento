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

class Novapc_Integracommerce_Block_Adminhtml_Report_Renderer_Status extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    
    public function render(Varien_Object $row)
    {
        $value =  $row->getData($this->getColumn()->getIndex());
        if (!$value || empty($value)) {
            return '<div style="background: green; width: 100%; padding: 1px; border-radius: 15px; text-align: center;"><span style="color: white;">A Sincronizar </span></div>';
        } else {
            return '<div style="background: red; width: 100%; padding: 1px; border-radius: 15px; text-align: center;"><span style="color: white;">Erro</span></div>';
        }
    }
   
}