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

class Novapc_Integracommerce_Block_Adminhtml_Report_Edit_Tab_Price extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset('vendor_form', array(
            'legend' => Mage::helper('integracommerce')->__('Preço')
        ));

        $fieldset->addField('price_body', 'textarea', array(
            'name' => 'price_body',
            'label' => Mage::helper('integracommerce')->__('Requisição'),
        ));


        $fieldset->addField('price_error', 'textarea', array(
            'name'    => 'price_error',
            'label'   => Mage::helper('integracommerce')->__('Erro'),
        ));

        $form->addValues(Mage::registry('report_data')->getData());

        return parent::_prepareForm();
    }

}