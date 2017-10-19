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
    const SUCCESS_MESSAGE = 'Sincronização Completa';
    const NO_ORDERS       = 'Não existe nenhum pedido em Aprovado no momento.';

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
        $limits = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($orderModel, '(GET) api/Order');

        if (isset($limits['message'])) {
            /*SE FOR RETORNADO UMA MENSAGEM DE ERRO BLOQUEIA O METODO E RETORNA A MENSAGEM AO USUARIO*/
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($limits['message']));
            $orderModel->setAvailable(0);
            $orderModel->save();
            $this->_redirect('*/*/');
        } else {
            /*INICIANDO GET DE PEDIDOS*/
            $requested = Novapc_Integracommerce_Helper_Data::getOrders();

            if (empty($requested['Orders'])) {
                /*SE NAO FOR RETORNADO PEDIDOS RETORNA A MENSAGEM AO USUARIO*/
                Mage::getSingleton('core/session')->addSuccess(Mage::helper('integracommerce')->__(self::NO_ORDERS));
                $this->_redirect('*/*/');
            }

            /*INCIA PROCESSO DE CRIACAO DE PEDIDOS*/
            Novapc_Integracommerce_Helper_OrderData::startOrders($requested, $orderModel);

            Mage::getSingleton('core/session')->addSuccess(Mage::helper('integracommerce')->__(self::SUCCESS_MESSAGE));
            $this->_redirect('*/*/');
        }
    }   

    public function massDeleteAction()
    {
        $ordersIds = (array) $this->getRequest()->getParam('integracommerce_order');

        $collection = Mage::getModel('integracommerce/order')
            ->getCollection()
            ->deleteOrders($ordersIds);

        $this->_redirect('*/*/');
    }

    public function massSearchAction()
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        $orderModel = Mage::getModel('integracommerce/queue')->load('Orderid', 'integra_model');
        $limits = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($orderModel, '(GET) api/Order/{id}');

        if (isset($limits['message'])) {
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($limits['message']));
            $orderModel->setAvailable(0);
            $orderModel->save();
            $this->_redirect('*/*/');
        } else {
            $requestedHour = $orderModel->getRequestedHour();

            $ordersIds = (array) $this->getRequest()->getParam('integracommerce_order');

            $mageOrdersIds = Mage::getModel('integracommerce/order')
                ->getCollection()
                ->addIdsToFilter($ordersIds, null, null);

            $ordersCollection = Mage::getModel('sales/order')
                ->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $mageOrdersIds))
                ->addFieldToSelect('*');

            $mageOrdersIds = array();
            foreach ($ordersCollection as $order) {
                $mageOrdersIds[] = $order->getId();
            }

            $nonexistentOrders = array_diff($ordersIds, $mageOrdersIds);
            $integraCollection = Mage::getModel('integracommerce/order')
                ->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $nonexistentOrders))
                ->addFieldToSelect('*');

            $requestedMin = 0;
            foreach ($integraCollection as $integraOrder) {
                $integraId = $integraOrder->getIntegraId();

                $url = "https://" . $environment . ".integracommerce.com.br/api/Order/" . $integraId;

                $return = Novapc_Integracommerce_Helper_Data::callCurl("GET", $url, null);

                $requestedMin++;
                $requestedHour++;
                if ($requestedHour == $limits['hour']) {
                    break;
                }

                if ($return['OrderStatus'] !== 'APPROVED' && $return['OrderStatus'] !== 'PROCESSING') {
                    continue;
                }

                Novapc_Integracommerce_Helper_OrderData::processingOrder($return);

                usleep(500000);
                $time = strtotime('s');
                $seconds = date("s", $time);
                if ($requestedMin >= $limits['minute'] && $seconds < 60) {
                    $waitFor = 60 - $seconds;
                    time_sleep_until(time()+$waitFor);
                }
            }

            $requestTime = Novapc_Integracommerce_Helper_Data::currentDate(null, 'string');
            $orderModel->setStatus($requestTime);
            $orderModel->setRequestedHour($requestedHour);
            $orderModel->save();

            Mage::getSingleton('core/session')->addSuccess(Mage::helper('integracommerce')->__(self::SUCCESS_MESSAGE));
            $this->_redirect('*/*/');
        }
    }

    public function viewAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('integracommerce');
        $this->renderLayout();
    }

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