<?php
/**
 * Novapc Integracommerce
 * 
 * @category     Novapc
 * @package      Novapc_Integracommerce 
 * @copyright    Copyright (c) 2016 Novapc (http://www.novapc.com.br/)
 * @author       Novapc
 * @version      Release: 1.0.0 
 */

class Novapc_Integracommerce_Block_Adminhtml_Order_Renderer_Name extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract {
    
    public function render(Varien_Object $row)
    {

    $value =  $row->getData($this->getColumn()->getIndex());
    if (!$value || $value == '') {
        return '<span style="color:red; font-weight: bold;">'. Mage::helper('integracommerce')->__('No Data') .'</span>';
    } else {
    	return $value;
    }
 
    }
   
}