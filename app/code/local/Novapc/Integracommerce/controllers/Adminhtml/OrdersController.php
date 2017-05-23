<?php
/**
 * Novapc Integracommerce
 * 
 * @category     Novapc
 * @package      Novapc_Integracommerce
 * @copyright    Copyright (c) 2016 Novapc (http://www.novapc.com.br/)
 * @author       NovaPC
 * @version      Release: 1.0.0 
 */

class Novapc_Integracommerce_Adminhtml_OrdersController extends Mage_Adminhtml_Controller_Action
{

	
    public function indexAction() 
    {
        //$this->_initAction();
        //$this->renderLayout();
        $this->loadLayout();
        $this->_setActiveMenu('integracommerce');
        $this->renderLayout();
    
    }

    protected function integrateAction() 
    {
        $ordersCollection = Novapc_Integracommerce_Helper_Data::getOrders();

        if (empty($ordersCollection)) {
            $this->_redirect('*/*/');
        }

        foreach ($ordersCollection as $order) {
            //VERIFICA SE CLIENTE JÁ EXISTE
            if ($order['CustomerPfCpf']) {
                $customer_doc = $order['CustomerPfCpf'];
            } elseif ($order['CustomerPjCnpj']) {
                $customer_doc = $order['CustomerPjCnpj'];
            }

            $customer = Mage::getModel('customer/customer')
                ->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('taxvat', $customer_doc)
                ->getFirstItem();

            $customerId = $customer->getId();
            if ($customerId && !empty($customerId)) {
                Novapc_Integracommerce_Helper_OrderData::updateCustomer($customer,$order);
            } else {
                $customerId = Novapc_Integracommerce_Helper_OrderData::createCustomer($order);
            }

            //VERIFIFA SE JA EXISTE PEDIDO NO MAGENTO COM O ID DA COMPRA INTEGRACOMMERCE
            $existingOrder = Mage::getModel('sales/order')->load($order['IdOrder'], 'integracommerce_id');

            $incrementId = $existingOrder->getIncrementId();
            if ($incrementId && !empty($incrementId)) {
                continue;
            } else {
                $mageOrder = Novapc_Integracommerce_Helper_OrderData::createOrder($order,$customerId);
                Novapc_Integracommerce_Helper_OrderData::integraOrder($order,$customerId,$mageOrder);
            }
        }

        $this->_redirect('*/*/');
    }   

    public function massDeleteAction()
    {
        $ordersIds = (array) $this->getRequest()->getParam('integracommerce_order');

        foreach ($ordersIds as $orderId) {
            $orderModel = Mage::getModel('integracommerce/order')
                            ->getCollection()
                            ->addFieldToFilter('entity_id',$orderId)
                            ->getFirstItem();                          

            $orderModel->delete();                        
        }

        $this->_redirect('*/*/');
    }         

    public function viewAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('integracommerce');
        $this->renderLayout();
    }

    public function feedbackAction()
    {
        return;
    }  

    public function replyAction()
    {
        return;
    }      

    /**
     * Product grid for AJAX request
     */
    public function gridAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('integracommerce/adminhtml_order_grid')->toHtml()
        );
    }
}