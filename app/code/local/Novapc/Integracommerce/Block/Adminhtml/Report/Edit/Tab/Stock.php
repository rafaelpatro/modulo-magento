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

class Novapc_Integracommerce_Block_Adminhtml_Report_Edit_Tab_Stock extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('vendor_form', array(
            'legend' => Mage::helper('integracommerce')->__('Estoque')
        ));

        $fieldset->addField('stock_body', 'textarea', array(
            'name' => 'stock_body',
            'label' => Mage::helper('integracommerce')->__('Requisição'),
        ));


        $fieldset->addField('stock_error', 'textarea', array(
            'name'    => 'stock_error',
            'label'   => Mage::helper('integracommerce')->__('Erro'),
        ));

        $form->addValues(Mage::registry('report_data')->getData());

        return parent::_prepareForm();
    }

}