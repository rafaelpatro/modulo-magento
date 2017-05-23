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

class Novapc_Integracommerce_Block_Adminhtml_Report_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
   {
       parent::__construct();
        $this->_objectId = 'id';
        //you will notice that assigns the same blockGroup the Grid Container
        $this->_blockGroup = 'integracommerce';
        // and the same container
        $this->_controller = 'adminhtml_report';
        //we define the labels for the buttons save and delete
        $this->_removeButton('save');
        $this->_updateButton('delete', 'label', 'Excluir Item');
        $this->_removeButton('reset');
        $this->_headerText = $this->__('Informações da Integração');
    }

}