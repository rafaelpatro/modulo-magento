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

class Novapc_Integracommerce_Block_Adminhtml_Report_Edit_Tab_Product extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();
        $this->setForm($form);
        $fieldset = $form->addFieldset(
            'report_form',
            array(
                'legend' => Mage::helper('integracommerce')->__('Produto')
            )
        );

        $fieldset->addField(
            'product_body',
            'textarea',
            array(
                'name' => 'product_body',
                'label' => Mage::helper('integracommerce')->__('Requisição'),
            )
        );


        $fieldset->addField(
            'product_error',
            'textarea',
            array(
                'name'    => 'product_error',
                'label'   => Mage::helper('integracommerce')->__('Erro'),
            )
        );


        $form->addValues(Mage::registry('report_data')->getData());

        return parent::_prepareForm();
    }

}