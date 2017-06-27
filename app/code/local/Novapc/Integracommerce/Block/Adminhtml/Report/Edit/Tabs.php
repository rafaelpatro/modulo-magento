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
        $this->addTab(
            'product_section',
            array(
                'label' => 'Produto',
                'title' => 'Produto',
                'content' => $this->getLayout()
                    ->createBlock('integracommerce/adminhtml_report_edit_tab_product')
                    ->toHtml()
            )
        );

        $this->addTab(
            'sku_section',
            array(
                'label' => 'SKU',
                'title' => 'SKU',
                'content' => $this->getLayout()
                    ->createBlock('integracommerce/adminhtml_report_edit_tab_sku')
                    ->toHtml()
            )
        );

        $this->addTab(
            'price_section',
            array(
                'label' => 'Preço',
                'title' => 'Preço',
                'content' => $this->getLayout()
                    ->createBlock('integracommerce/adminhtml_report_edit_tab_price')
                    ->toHtml()
            )
        );

        $this->addTab(
            'stock_section',
            array(
                'label' => 'Estoque',
                'title' => 'Estoque',
                'content' => $this->getLayout()
                    ->createBlock('integracommerce/adminhtml_report_edit_tab_stock')
                    ->toHtml()
            )
        );

        return parent::_beforeToHtml();
    }

}