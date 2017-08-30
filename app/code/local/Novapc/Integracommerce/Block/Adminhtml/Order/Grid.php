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

class Novapc_Integracommerce_Block_Adminhtml_Order_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('orderGrid');
        $this->setDefaultSort('id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setVarNameFilter('order_filter');

        $this->shippingMode = array(
                         'custom' => Mage::helper('integracommerce')->__('custom')
                         );
        $this->status = array(
                         'PROCESSING' => Mage::helper('integracommerce')->__('Processing'),
                         'INVOICED' => Mage::helper('integracommerce')->__('Invoiced'),
                         'SHIPPED' => Mage::helper('integracommerce')->__('Shipped'),
                         'DELIVERED' => Mage::helper('integracommerce')->__('Delivered'),
                         'SHIPMENT_EXCEPTION' => Mage::helper('integracommerce')->__('Shipment Exception'),
                         'UNAVAILABLE' => Mage::helper('integracommerce')->__('Unavailable'),
                         'CANCELED' => Mage::helper('integracommerce')->__('Canceled'),
                         );
    }

    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return Mage::app()->getStore($storeId);
    }        
    
    protected function _prepareCollection() 
    {
        $collection = Mage::getModel('integracommerce/order')->getCollection();

        $this->setCollection($collection);
                  
        parent::_prepareCollection();
        
        return $this;

    }

    protected function _prepareColumns() 
    {
        $this->addColumn(
            'integra_id',
            array(
                'header'=> Mage::helper('integracommerce')->__('Codígo Integracommerce'),
                'index' => 'integra_id',
                'actions'   => array(
                    array(
                    'caption'   => __('Edit'),
                    'url'       => array('base'=> '*/*/view'),
                    'field'     => 'integra_id'
                    )
                ),
            )
        );

        $this->addColumn(
            'magento_order_id',
            array(
                'header'=> Mage::helper('integracommerce')->__('Código Magento'),
                'index' => 'magento_order_id',
                'renderer' => 'Novapc_Integracommerce_Block_Adminhtml_Order_Renderer_Mageid',
            )
        );

        $this->addColumn(
            'inserted_at',
            array(
                'header'=> Mage::helper('catalog')->__('Date Created'),
                'index' => 'inserted_at',
            )
        );

        $this->addColumn(
            'customer_pf_name',
            array(
                'header'=> Mage::helper('integracommerce')->__('Customer Name'),
                'index' => 'customer_pf_name',
                'renderer' => 'Novapc_Integracommerce_Block_Adminhtml_Order_Renderer_Name',         
            )
        );

        $this->addColumn(
            'customer_pj_corporate_name',
            array(
                'header'=> Mage::helper('integracommerce')->__('Customer Corporate Name'),
                'index' => 'customer_pj_corporate_name',
                'renderer' => 'Novapc_Integracommerce_Block_Adminhtml_Order_Renderer_Corporate',
            )
        );

        $this->addColumn(
            'total_amount',
            array(
                'header'=> Mage::helper('integracommerce')->__('Total Amount'),
                'type' => 'currency',   
                'width' => '1',             
                'currency_code' => Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
                'index' => 'total_amount',
            )
        );

        $this->addColumn(
            'total_freight',
            array(
                'header'=> Mage::helper('integracommerce')->__('Shipping Cost'),
                'type' => 'currency',   
                'width' => '1',             
                'currency_code' => Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
                'index' => 'total_freight',
            )
        );

        $this->addColumn(
            'total_discount',
            array(
                'header'=> Mage::helper('integracommerce')->__('Discount'),
                'type' => 'currency',   
                'width' => '1',             
                'currency_code' => Mage::getStoreConfig(Mage_Directory_Model_Currency::XML_PATH_CURRENCY_BASE),
                'index' => 'total_discount',
            )
        );

        $this->addColumn(
            'shipped_carrier_name',
            array(
                'header'=> Mage::helper('integracommerce')->__('Transportadora'),
                'index' => 'shipped_carrier_name',
            )
        );

        $this->addColumn(
            'order_status',
            array(
                'header'=> Mage::helper('integracommerce')->__('Status'),
                'index' => 'order_status',
                'type'  => 'options',
                'options' => $this->status,
            )
        );

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {

        $this->setMassactionIdField('entity_id');
        $this->getMassactionBlock()->setFormFieldName('integracommerce_order');

        $this->getMassactionBlock()->addItem(
            'delete',
            array(
                'label'    => Mage::helper('integracommerce')->__('Excluir Pedido'),
                'url'      => $this->getUrl('*/*/massDelete'),
                'confirm'  => Mage::helper('customer')->__('Are you sure?')
            )
        );

        $this->getMassactionBlock()->addItem(
            'search',
            array(
                'label'    => Mage::helper('integracommerce')->__('Buscar Pedido'),
                'url'      => $this->getUrl('*/*/massSearch')
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
        return $this->getUrl('*/*/view', array('id' => $row->getIntegraId()));
    }
}
    

