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

class Novapc_Integracommerce_Block_Adminhtml_Report_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('reports_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle('Seções');
    }

    protected function _beforeToHtml()
    {
        $this->addTab('product_section', array(
            'label' => 'Produto',
            'title' => 'Produto',
            'content' => $this->getLayout()
                ->createBlock('integracommerce/adminhtml_report_edit_tab_product')
                ->toHtml()
        ));

        $this->addTab('sku_section', array(
            'label' => 'SKU',
            'title' => 'SKU',
            'content' => $this->getLayout()
                ->createBlock('integracommerce/adminhtml_report_edit_tab_sku')
                ->toHtml()
        ));

        $this->addTab('price_section', array(
            'label' => 'Preço',
            'title' => 'Preço',
            'content' => $this->getLayout()
                ->createBlock('integracommerce/adminhtml_report_edit_tab_price')
                ->toHtml()
        ));

        $this->addTab('stock_section', array(
            'label' => 'Estoque',
            'title' => 'Estoque',
            'content' => $this->getLayout()
                ->createBlock('integracommerce/adminhtml_report_edit_tab_stock')
                ->toHtml()
        ));

        return parent::_beforeToHtml();
    }

}