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

        $isActive = (int) $product->getData('integracommerce_active');
        if ($isActive !== 1) {
            return;
        }

        $productId = $product->getId();
        if (!empty($productId)) {
            $insertQueue = Mage::getModel('integracommerce/update')->load($productId, 'product_id');
            $queueProductId = $insertQueue->getProductId();
            if (!$queueProductId || empty($queueProductId)) {
                $insertQueue = Mage::getModel('integracommerce/update');
                $insertQueue->setProductId($productId);
                $insertQueue->save();
            }
        }
    }  

    public function orderQueue(Varien_Event_Observer $event)
    {
        $order = $event->getEvent()->getOrder();

        $orderItemIds = array();
        foreach ($order->getAllItems() as $item) {
            $orderItemIds[] = $item->getProductId();
        }

        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $orderItemIds))
            ->addAttributeToSelect('*');

        $updateIds = array();
        foreach ($productCollection as $product) {
            $isActive = (int) $product->getData('integracommerce_active');
            if ($isActive !== 1) {
                continue;
            }

            $updateIds[] = $product->getId();
        }

        if (!empty($updateIds)) {
            Mage::getModel('integracommerce/update')->getCollection()->bulkInsert($updateIds);
        }
    }

    public function updateStatus(Varien_Event_Observer $event)
    {
        $comment = $event->getDataObject();
        $orderId = $comment->getParentId();
        $createdAt = $comment->getCreatedAt();
        $now = new DateTime('NOW');
        $formatedNow = $now->format('Y-m-d H:i:s');
        if ($formatedNow !== $createdAt) {
            return;
        }

        $order = Mage::getModel('sales/order')->load($orderId);
        $integracommerceId = $order->getData('integracommerce_id');
        if (!empty($integracommerceId)) {
            Novapc_Integracommerce_Helper_OrderData::updateOrder($order, $comment);
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

        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $productIds))
            ->addAttributeToSelect('*');

        $updatedIds = array();
        foreach ($productCollection as $product) {
            if (array_key_exists("integracommerce_active", $attributesData)) {
                $activate = $attributesData['integracommerce_active'];
            }

            //VERIFICANDO SE O ATRIBUTO DE CONTROLE SERA ALTERADO PARA NAO
            //POIS MESMO SENDO EVENTO AFTER NAO RETORNA APOS ATUALIZACAO
            if (isset($activate) && $activate == 0) {
                continue;
            }

            //VERIFICANDO SE O PRODUTO JA FOI SINCRONIZADO
            $isActive = (int) $product->getData('integracommerce_active');
            if (empty($activate) && $isActive == 0) {
                continue;
            }

            $prodType = $product->getTypeId();
            if ($prodType == 'configurable') {
                $simpleIds = Mage::getModel('catalog/product_type_configurable')
                    ->getUsedProductIds($product);

                Mage::getModel('integracommerce/update')->getCollection()->bulkInsert($simpleIds);
            }

            $updatedIds[] = $product->getId();
        }

        if (!empty($updatedIds)) {
            Mage::getModel('integracommerce/update')->getCollection()->bulkInsert($updatedIds);
        }
    }

    public function productQueue(Varien_Event_Observer $event)
    {
        $product = $event->getProduct();
        $isActive = (int) $product->getData('integracommerce_active');
        if ($isActive !== 1) {
            return;
        }

        $prodType = $product->getTypeId();
        if ($prodType == 'configurable') {
            $simpleIds = Mage::getModel('catalog/product_type_configurable')
                ->getUsedProductIds($product);

            Mage::getModel('integracommerce/update')->getCollection()->bulkInsert($simpleIds);
        }

        $productId = $product->getId();
        if (!empty($productId)) {
            $insertQueue = Mage::getModel('integracommerce/update')->load($productId, 'product_id');
            $queueProductId = $insertQueue->getProductId();
            if (!$queueProductId || empty($queueProductId)) {
               $insertQueue = Mage::getModel('integracommerce/update');
               $insertQueue->setProductId($productId);
               $insertQueue->save();
            }        
        }
    }  

    public function getOrders()
    {
        $orderModel = Mage::getModel('integracommerce/queue')->load('Order', 'integra_model');
        $limits = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($orderModel, '(GET) api/Order');

        if (isset($limits['message'])) {
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

        $limits = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($productModel, '(PUT) api/Product');

        if (isset($limits['message'])) {
            $productModel->setAvailable(0);
            $productModel->save();
            return;
        } else {
            $alreadyRequested = $productModel->getRequestedHour();
            $requestedHour = Novapc_Integracommerce_Helper_IntegrationData::forceUpdate($alreadyRequested, $limits);

            $requestTime = Novapc_Integracommerce_Helper_Data::currentDate(null, 'string');

            $productModel->setStatus($requestTime);
            $productModel->setRequestedHour($requestedHour);

            $productModel->save();

            return;
        }
    }
}