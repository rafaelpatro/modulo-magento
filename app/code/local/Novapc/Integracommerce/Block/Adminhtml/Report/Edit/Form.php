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

class Novapc_Integracommerce_Block_Adminhtml_Report_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(
            array(
                'id' => 'edit_form',
            )
        );

        /*$fieldset = $form->addFieldset('report_form', array(
            'legend' => Mage::helper('integracommerce')->__('Relatório de Erros e Atualização')
        )); */

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}