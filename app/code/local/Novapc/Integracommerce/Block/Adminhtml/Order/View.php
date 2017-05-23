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

class Novapc_Integracommerce_Block_Adminhtml_Order_View extends Mage_Adminhtml_Block_Widget_Grid 
{
	public function viewOrder()
	{
		$id = $this->getRequest()->getParam('id');

		if($id){
			return Novapc_Integracommerce_Helper_OrderData::viewOrder($id);	
		}
	}
}