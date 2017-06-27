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

class Novapc_Integracommerce_Adminhtml_OrdersController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction() 
    {
        $this->loadLayout();
        $this->_setActiveMenu('integracommerce');
        $this->renderLayout();
    
    }

    protected function integrateAction() 
    {
        /*CARREGA O MODEL DE CONTROLE DE REQUISICOES DE PEDIDOS*/
        $orderModel = Mage::getModel('integracommerce/queue')->load('Order', 'integra_model');
        /*VERIFICA A QUANTIDADE DE REQUISICOES*/
        $message = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($orderModel, 'get');

        if (isset($message)) {
            /*SE FOR RETORNADO UMA MENSAGEM DE ERRO BLOQUEIA O METODO E RETORNA A MENSAGEM AO USUARIO*/
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($message));
            $orderModel->setAvailable(0);
            $orderModel->save();
            $this->_redirect('*/*/');
        } else {
            /*INICIANDO GET DE PEDIDOS*/
            $requested = Novapc_Integracommerce_Helper_Data::getOrders();

            if (empty($requested['Orders'])) {
                /*SE NAO FOR RETORNADO PEDIDOS RETORNA A MENSAGEM AO USUARIO*/
                Mage::getSingleton('core/session')->addSuccess(Mage::helper('integracommerce')->__('Não existe nenhum pedido em Aprovado no momento.'));
                $this->_redirect('*/*/');
            }

            /*INCIA PROCESSO DE CRIACAO DE PEDIDOS*/
            Novapc_Integracommerce_Helper_OrderData::startOrders($requested, $orderModel);

            Mage::getSingleton('core/session')->addSuccess(Mage::helper('integracommerce')->__('Sincronização Completa.'));
            $this->_redirect('*/*/');
        }
    }   

    public function massDeleteAction()
    {
        $ordersIds = (array) $this->getRequest()->getParam('integracommerce_order');

        $collection = Mage::getModel('integracommerce/order')->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $ordersIds));

        foreach ($collection as $order) {
            $order->delete();
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
            $requestedHour = $orderModel->getRequestedHour();
            $requestedDay = $orderModel->getRequestedDay();
            $requestedWeek = $orderModel->getRequestedWeek();
            $requestedInitial = $orderModel->getInitialHour();

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

                $url = "https://api.integracommerce.com.br/api/Order/" . $integraId;

                $return = Novapc_Integracommerce_Helper_Data::callCurl("GET", $url, null);

                $requested++;
                if ($return['OrderStatus'] !== 'APPROVED' && $return['OrderStatus'] !== 'PROCESSING') {
                    continue;
                }

                Novapc_Integracommerce_Helper_OrderData::processingOrder($return);

                sleep(2);
            }

            $requestedHour = $requestedHour + $requested;
            $requestedDay = $requestedDay + $requested;
            $requestedWeek = $requestedWeek + $requested;
            $requestTime = Mage::getSingleton('core/date')->date('Y-m-d H:i:s');

            $orderModel->setStatus($requestTime);
            $orderModel->setRequestedHour($requestedHour);
            $orderModel->setRequestedDay($requestedDay);
            $orderModel->setRequestedWeek($requestedWeek);

            if (empty($requestedInitial)) {
                $orderModel->setInitialHour($requestTime);
            }

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


    /**
     * Product grid for AJAX request
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('integracommerce/adminhtml_order_grid')->toHtml()
        );
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('integracommerce/orders');
    }
}