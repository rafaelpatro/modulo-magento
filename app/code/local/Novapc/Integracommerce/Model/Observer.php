<?php

class Novapc_Integracommerce_Model_Observer
{  

    public function stockQueue(Varien_Event_Observer $event)
    {
        $item = $event->getEvent()->getItem();
        $product = Mage::getModel('catalog/product')->load($item->getId());

        $exportType = Mage::getStoreConfig('integracommerce/general/export_type',Mage::app()->getStore());
        if (($exportType == 1 && $product->getData('integracommerce_sync') == 0) && $product->getData('integracommerce_active') == 0) {
            return;
        } else {
            $insertQueue = Mage::getModel('integracommerce/update')->load($product->getId(), 'product_id');
            $queueProductId = $insertQueue->getProductId();
            if (!$queueProductId || empty($queueProductId)) {
                $insertQueue = Mage::getModel('integracommerce/update');
                $insertQueue->setProductId($product->getId());
                $insertQueue->save();
            }
        }

        return;
    }  

    public function orderQueue(Varien_Event_Observer $event)
    {
        $order = $event->getEvent()->getOrder();
        $exportType = Mage::getStoreConfig('integracommerce/general/export_type',Mage::app()->getStore());

        foreach ($order->getAllItems() as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            if (($exportType == 1 && $product->getData('integracommerce_sync') == 0) && $product->getData('integracommerce_active') == 0) {
                continue;
            } else {
                $insertQueue = Mage::getModel('integracommerce/update')->load($product->getId(), 'product_id');
                $queueProductId = $insertQueue->getProductId();
                if (!$queueProductId || empty($queueProductId)) {
                    $insertQueue = Mage::getModel('integracommerce/update');
                    $insertQueue->setProductId($product->getId());
                    $insertQueue->save();
                }
            }
        }

        $integracommerceId = $order->getData('integracommerce_id');
        if (!empty($integracommerceId)) {
            $response_status = Novapc_Integracommerce_Helper_OrderData::updateOrder($order);
        }
    
        return;
    }

    public function massproductQueue(Varien_Event_Observer $event)
    {
        $attributesData = $event->getEvent()->getAttributesData();
        $productIds     = $event->getEvent()->getProductIds();

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

        return;
    }

    public function productQueue(Varien_Event_Observer $event)
    {
        $product = $event->getProduct();

        if ($product->getData('integracommerce_active') == 0) {
            return;
        }

        $exportType = Mage::getStoreConfig('integracommerce/general/export_type',Mage::app()->getStore());
        if (($exportType == 1 && $product->getData('integracommerce_sync') == 0) && $product->getData('integracommerce_active') == 0) {
           return;
        } else {
           $insertQueue = Mage::getModel('integracommerce/update')->load($product->getId(), 'product_id');
            $queueProductId = $insertQueue->getProductId();
            if (!$queueProductId || empty($queueProductId)) {
               $insertQueue = Mage::getModel('integracommerce/update');
               $insertQueue->setProductId($product->getId());
               $insertQueue->save();
           }
        }

        return;
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

    public function integraCategories(Varien_Event_Observer $event)
    {
        $current_category = $event->getCategory();

        if ($current_category->getData('parent_id') == 2) {
           $_parent_id = "";
        } else {
            $_parent_id = $current_category->getData('parent_id');
        }

        $body = array();
        array_push($body,
            array(
                "Id" => $current_category->getId(),
                "Name" => $current_category->getName(),
                "ParentId" => $_parent_id
            )
        );

        Novapc_Integracommerce_Helper_Data::postCategory($body);

        return;

    }    

    public function productUpdate()
    {
        $api_user = Mage::getStoreConfig('integracommerce/general/api_user',Mage::app()->getStore());
        $api_password = Mage::getStoreConfig('integracommerce/general/api_password',Mage::app()->getStore());
        $authentication = base64_encode($api_user . ':' . $api_password);
        $productModel = Mage::getModel('integracommerce/integration')->load('Product Update', 'integra_model');

        $message = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($productModel, 'put');

        if (isset($message)) {
            $productModel->setAvailable(0);
            $productModel->save();
            return;
        } else {
            $queueCollection = Mage::getModel('integracommerce/update')->getCollection()->getAllIds();

            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $queueCollection))
                ->addAttributeToSelect('*');

            $alreadyRequested = $productModel->getRequestedHour();
            $requestedDay = $productModel->getRequestedDay();
            $requestedWeek = $productModel->getRequestedWeek();
            $requested = Novapc_Integracommerce_Helper_IntegrationData::productSelection($collection, $authentication, $alreadyRequested);

            if ($alreadyRequested == $requested) {
                $requested = 0;
            }

            $requestedDay = $requestedDay + $requested;
            $requestedWeek = $requestedWeek + $requested;

            $productModel->setStatus(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
            $productModel->setRequestedHour($requested);
            $productModel->setRequestedDay($requestedDay);
            $productModel->setRequestedWeek($requestedWeek);
            $productModel->save();

            return;
        }
    }
}