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

class Novapc_Integracommerce_Block_Adminhtml_Report_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    const CONFIRM_MESSAGE = 'Esta ação removerá os itens da fila, inclusive caso ainda não tenha sido atualizado!';
    const RESET_MESSAGE = 'Esta ação indicará que o erro foi corrigido e o produto voltará a tentar ser atualizado!';

    public function __construct()
    {
        parent::__construct();
        $this->setId('reportGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('report_filter');
    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }        
    
    protected function _prepareCollection() 
    {
        $collection = Mage::getModel('integracommerce/update')->getCollection();

        $this->setCollection($collection);
                  
        parent::_prepareCollection();
        
        return $this;
    }

    protected function _prepareColumns() 
    {
        $this->addColumn(
            'product_id',
            array(
                'header'=> Mage::helper('integracommerce')->__('Product Id'),
                'index' => 'product_id',
                'width' => '50px',
                'type'  => 'number',
            )
        );

        $this->addColumn(
            'product_error',
            array(
                'header'=> Mage::helper('integracommerce')->__('Produto'),
                'index' => 'product_error',
                'width' => '50px',
                'renderer' => 'Novapc_Integracommerce_Block_Adminhtml_Report_Renderer_Status',
            )
        );

        $this->addColumn(
            'sku_error',
            array(
                'header'=> Mage::helper('integracommerce')->__('SKU'),
                'index' => 'sku_error',
                'width' => '50px',
                'renderer' => 'Novapc_Integracommerce_Block_Adminhtml_Report_Renderer_Status',
            )
        );

        $this->addColumn(
            'price_error',
            array(
                'header'=> Mage::helper('integracommerce')->__('Preço'),
                'index' => 'price_error',
                'width' => '50px',
                'renderer' => 'Novapc_Integracommerce_Block_Adminhtml_Report_Renderer_Status',
            )
        );

        $this->addColumn(
            'stock_error',
            array(
                'header'=> Mage::helper('integracommerce')->__('Estoque'),
                'index' => 'stock_error',
                'width' => '50px',
                'renderer' => 'Novapc_Integracommerce_Block_Adminhtml_Report_Renderer_Status',
            )
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('integracommerce_report');

        $this->getMassactionBlock()->addItem(
            'delete',
            array(
                'label'    => Mage::helper('integracommerce')->__('Excluir da Fila'),
                'url'      => $this->getUrl('*/*/massDelete'),
                'confirm'  => Mage::helper('integracommerce')->__(self::CONFIRM_MESSAGE)
            )
        );

        $this->getMassactionBlock()->addItem(
            'reset',
            array(
                'label'    => Mage::helper('integracommerce')->__('Erros Corrigidos'),
                'url'      => $this->getUrl('*/*/massReset'),
                'confirm'  => Mage::helper('integracommerce')->__(self::RESET_MESSAGE)
            )
        );

        return $this;
    }

    public function getGridUrl() 
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id'=>$row->getProductId()));
    }

}