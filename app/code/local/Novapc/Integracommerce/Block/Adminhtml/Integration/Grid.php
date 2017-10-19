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

class Novapc_Integracommerce_Block_Adminhtml_Integration_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('integrationGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('integration_filter');
    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }        
    
    protected function _prepareCollection() 
    {
        $collection = Mage::getModel('integracommerce/integration')->getCollection();

        $this->setCollection($collection);
                  
        parent::_prepareCollection();
        
        return $this;

    }

    protected function _prepareColumns() 
    {
        $this->addColumn(
            'integra_model',
            array(
                'header'=> Mage::helper('integracommerce')->__('A integrar'),
                'index' => 'integra_model',
                'renderer' => 'Novapc_Integracommerce_Block_Adminhtml_Integration_Renderer_Model',
            )
        );

        $this->addColumn(
            'status',
            array(
                'header'=> Mage::helper('integracommerce')->__('Ultima Atualização'),
                'index' => 'status',
                'renderer' => 'Novapc_Integracommerce_Block_Adminhtml_Integration_Renderer_Status',
            )
        );

        $this->addColumn(
            'available',
            array(
                'header'=> Mage::helper('integracommerce')->__('Disponível'),
                'index' => 'available',
                'renderer' => 'Novapc_Integracommerce_Block_Adminhtml_Integration_Renderer_Available',
            )
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('integracommerce_integration');

        $this->getMassactionBlock()->addItem(
            'category',
            array(
            'label'    => Mage::helper('integracommerce')->__('Exportar Categorias'),
            'url'      => $this->getUrl('*/*/massCategory')
            )
        );

        $this->getMassactionBlock()->addItem(
            'insert',
            array(
            'label'    => Mage::helper('integracommerce')->__('Exportar Produtos'),
            'url'      => $this->getUrl('*/*/massInsert')
            )
        );

        $this->getMassactionBlock()->addItem(
            'update',
            array(
                'label'    => Mage::helper('integracommerce')->__('Atualizar Produtos'),
                'url'      => $this->getUrl('*/*/massUpdate')
            )
        );

        return $this;
    }

    public function getGridUrl() 
    {
        return $this->getUrl('*/*/grid', array('_current'=>true));
    }

}