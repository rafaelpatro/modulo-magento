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

class Novapc_Integracommerce_Model_Observer
{

    public function stockQueue(Varien_Event_Observer $event)
    {
        $item = $event->getEvent()->getItem();
        $product = Mage::getModel('catalog/product')->load($item->getId());

        if ($product->getData('integracommerce_active') == 0) {
            return;
        }

        $insertQueue = Mage::getModel('integracommerce/update')->load($product->getId(), 'product_id');
        $queueProductId = $insertQueue->getProductId();
        if (!$queueProductId || empty($queueProductId)) {
            $insertQueue = Mage::getModel('integracommerce/update');
            $insertQueue->setProductId($product->getId());
            $insertQueue->save();
        }
    }  

    public function orderQueue(Varien_Event_Observer $event)
    {
        $order = $event->getEvent()->getOrder();

        foreach ($order->getAllItems() as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            if ($product->getData('integracommerce_active') == 0) {
                return;
            }

            $insertQueue = Mage::getModel('integracommerce/update')->load($product->getId(), 'product_id');
            $queueProductId = $insertQueue->getProductId();
            if (!$queueProductId || empty($queueProductId)) {
                $insertQueue = Mage::getModel('integracommerce/update');
                $insertQueue->setProductId($product->getId());
                $insertQueue->save();
            }
        }

        $integracommerceId = $order->getData('integracommerce_id');
        if (!empty($integracommerceId)) {
            $responseStatus = Novapc_Integracommerce_Helper_OrderData::updateOrder($order);
        }
    }

    public function massproductQueue(Varien_Event_Observer $event)
    {
        $attributesData = $event->getEvent()->getAttributesData();
        $productIds     = $event->getEvent()->getProductIds();

        $count = count($attributesData);
        if ($count == 1 && array_key_exists("integracommerce_active", $attributesData)) {
            return;
        }

        foreach ($productIds as $id) {
            $product = Mage::getModel('catalog/product')->load($id);

            if (array_key_exists("integracommerce_active", $attributesData)) {
                $activate = $attributesData['integracommerce_active'];
            }

            //VERIFICANDO SE O ATRIBUTO DE CONTROLE SERA ALTERADO PARA NAO
            //POIS MESMO SENDO EVENTO AFTER NAO RETORNA APOS ATUALIZACAO
            if (isset($activate) && $activate == 0) {
                continue;
            }

            //VERIFICANDO SE O PRODUTO JA FOI SINCRONIZADO
            if (empty($activate) && $product->getData('integracommerce_active') == 0) {
                continue;
            }

            $insertQueue = Mage::getModel('integracommerce/update')->load($id, 'product_id');
            $queueProductId = $insertQueue->getProductId();
            if (!$queueProductId || empty($queueProductId)) {
                $insertQueue = Mage::getModel('integracommerce/update');
                $insertQueue->setProductId($id);
                $insertQueue->save();
            }
        }
    }

    public function productQueue(Varien_Event_Observer $event)
    {
        $product = $event->getProduct();

        if ($product->getData('integracommerce_active') == 0) {
            return;
        }

        $insertQueue = Mage::getModel('integracommerce/update')->load($product->getId(), 'product_id');
        $queueProductId = $insertQueue->getProductId();
        if (!$queueProductId || empty($queueProductId)) {
           $insertQueue = Mage::getModel('integracommerce/update');
           $insertQueue->setProductId($product->getId());
           $insertQueue->save();
        }
    }  

    public function getOrders()
    {
        $orderModel = Mage::getModel('integracommerce/queue')->load('Order', 'integra_model');
        $message = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($orderModel, 'get');

        if (isset($message)) {
            $orderModel->setAvailable(0);
            $orderModel->save();
            return;
        } else {
            $requested = Novapc_Integracommerce_Helper_Data::getOrders();

            if (empty($requested['Orders'])) {
                return;
            }

            Novapc_Integracommerce_Helper_OrderData::startOrders($requested, $orderModel);

            return;
        }
    }      

    public function productUpdate()
    {
        $productModel = Mage::getModel('integracommerce/integration')->load('Product Update', 'integra_model');

        $message = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($productModel, 'put');

        if (isset($message)) {
            $productModel->setAvailable(0);
            $productModel->save();
            return;
        } else {
            $alreadyRequested = $productModel->getRequestedHour();
            $requestedDay = $productModel->getRequestedDay();
            $requestedWeek = $productModel->getRequestedWeek();
            $requestedInitial = $productModel->getInitialHour();
            $requestedHour = Novapc_Integracommerce_Helper_IntegrationData::forceUpdate($alreadyRequested);

            if ($alreadyRequested == $requestedHour) {
                $requested = 0;
                $requestedHour = 0;
            } else {
                $requested = $requestedHour - $alreadyRequested;
            }

            $requestedDay = $requestedDay + $requested;
            $requestedWeek = $requestedWeek + $requested;
            $requestTime = Mage::getSingleton('core/date')->date('Y-m-d H:i:s');

            $productModel->setStatus($requestTime);
            $productModel->setRequestedHour($requestedHour);
            $productModel->setRequestedDay($requestedDay);
            $productModel->setRequestedWeek($requestedWeek);

            if (empty($requestedInitial)) {
                $productModel->setInitialHour($requestTime);
            }

            $productModel->save();

            return;
        }
    }
}