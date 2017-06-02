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
        $orderModel = Mage::getModel('integracommerce/queue')->load('Order', 'integra_model');
        $message = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($orderModel, 'get');

        if (isset($message)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($message));
            $orderModel->setAvailable(0);
            $orderModel->save();
            $this->_redirect('*/*/');
        } else {
            $requested = Novapc_Integracommerce_Helper_Data::getOrders();

            if (empty($requested['Orders'])) {
                Mage::getSingleton('core/session')->addSuccess(Mage::helper('integracommerce')->__('Não existe nenhum pedido em Aprovado no momento.'));
                $this->_redirect('*/*/');
            }

            Novapc_Integracommerce_Helper_OrderData::startOrders($requested, $orderModel);

            Mage::getSingleton('core/session')->addSuccess(Mage::helper('integracommerce')->__('Sincronização Completa.'));
            $this->_redirect('*/*/');
        }
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

    public function massSearchAction()
    {
        $orderModel = Mage::getModel('integracommerce/queue')->load('Orderid', 'integra_model');
        $message = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($orderModel, 'getid');

        if (isset($message)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($message));
            $orderModel->setAvailable(0);
            $orderModel->save();
            $this->_redirect('*/*/');
        } else {
            $api_user = Mage::getStoreConfig('integracommerce/general/api_user',Mage::app()->getStore());
            $api_password = Mage::getStoreConfig('integracommerce/general/api_password',Mage::app()->getStore());
            $authentication = base64_encode($api_user . ':' . $api_password);
            $requestedHour = $orderModel->getRequestedHour();
            $requestedDay = $orderModel->getRequestedDay();
            $requestedWeek = $orderModel->getRequestedWeek();

            $ordersIds = (array) $this->getRequest()->getParam('integracommerce_order');

            $requested = 0;
            foreach ($ordersIds as $id) {
                $checkOrder = Mage::getModel('integracommerce/order')->load($id, 'entity_id');
                $magentoId = $checkOrder->getData('magento_order_id');

                if (!empty($magentoId)) {
                    $tryOrder = Mage::getModel('sales/order')->load($magentoId);
                    $checkIncrementId = $tryOrder->getIncrementId();

                    if ($checkIncrementId || !empty($checkIncrementId)) {
                        continue;
                    }
                }

                $integraId = $checkOrder->getIntegraId();

                $geturl = "https://api.integracommerce.com.br/api/Order/" . $integraId;

                $ch = curl_init();
                curl_setopt($ch,CURLOPT_URL,$geturl);
                curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Basic " . $authentication . ""));
                $response = curl_exec($ch);
                curl_close ($ch);

                $order = json_decode($response, true);

                $requested++;

                if ($order['OrderStatus'] !== 'APPROVED' && $order['OrderStatus'] !== 'PROCESSING') {
                    continue;
                }

                Novapc_Integracommerce_Helper_OrderData::processingOrder($order);

                sleep(2);
            }

            $requestedHour = $requestedHour + $requested;
            $requestedDay = $requestedDay + $requested;
            $requestedWeek = $requestedWeek + $requested;

            $orderModel->setStatus(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
            $orderModel->setRequestedHour($requestedHour);
            $orderModel->setRequestedDay($requestedDay);
            $orderModel->setRequestedWeek($requestedWeek);
            $orderModel->save();

            Mage::getSingleton('core/session')->addSuccess(Mage::helper('integracommerce')->__('Sincronização Completa!'));
            $this->_redirect('*/*/');
        }
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