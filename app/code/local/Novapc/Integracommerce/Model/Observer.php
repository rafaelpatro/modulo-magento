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
        $ordersCollection = Novapc_Integracommerce_Helper_Data::getOrders();

        if (empty($ordersCollection)) {
            return;
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

        return;
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
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());

        $queueCollection = Mage::getModel('integracommerce/update')->getCollection()->setPageSize(30);
        
        $_productAttributes = Mage::getStoreConfig('integracommerce/general/attributes',Mage::app()->getStore());
        $productAttributes = explode(',', $_productAttributes);

        $fieldsCollection = Mage::getModel('integracommerce/attributes')->load(1,'entity_id');
        $preparedFields = Novapc_Integracommerce_Helper_IntegrationData::loadAttr($fieldsCollection); 

        $useConfigurable = Mage::getStoreConfig('integracommerce/general/configprod',Mage::app()->getStore());
        foreach ($queueCollection as $queueItem) {
            $product = Mage::getModel('catalog/product')->load($queueItem->getProductId());

            $height = $product->getData($preparedFields['4']);
            $width = $product->getData($preparedFields['5']);
            $length = $product->getData($preparedFields['6']);
            $weight = $product->getData($preparedFields['7']);

            $productType = $product->getTypeId();
            if ($productType !== 'configurable') {
                if (empty($height) || empty($width) || empty($length) || empty($weight)) {
                    $queueItem = Mage::getModel('integracommerce/update')->load($product->getId(), 'product_id');
                    $queueProductId = !$queueItem->getProductId();
                    if ($queueProductId) {
                        $queueItem = Mage::getModel('integracommerce/update');
                        $queueItem->setProductId($product->getId());
                    }
                    $queueItem->setProductBody('Erro na Sincronização');
                    $queueItem->setProductError('O produto não possui as informações de Altura, Largura, Comprimento ou Peso');
                    $queueItem->save();
                    continue;
                }
            }

            if ($product->getTypeId() == 'simple' && $useConfigurable == 1) {
                $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            }

            if ($parentIds && !empty($parentIds)) {
                //PREPARA AS INFORMACOES DO PRODUTO SIMPLES PARA O ENVIO
                list($_Scats,$Spictures) = Novapc_Integracommerce_Helper_IntegrationData::prepareProduct($product);
                $_Sattrs = Novapc_Integracommerce_Helper_IntegrationData::prepareSkuAttributes($product);

                //PARA CADA PRODUTO CONFIGURAVEL VINCULADO, O MODULO IRA FAZER O ENVIO DO CONFIGURAVEL E DO SIMPLES PARA CADA UM
                foreach ($parentIds as $configurableId) {
                    //CARREGA O PRODUTO CONFIGURAVEL DE ACORDO COM O ID RETORNADO
                    $configurableProduct = Mage::getModel('catalog/product')->load($configurableId);
                    //PREPARA AS INFORMACOES DO PRODUTO CONFIGURAVEL PARA O ENVIO
                    list($_cats,$pictures) = Novapc_Integracommerce_Helper_IntegrationData::prepareProduct($configurableProduct);
                    $_attrs = Novapc_Integracommerce_Helper_IntegrationData::prepareAttributes($configurableProduct,$_productAttributes,$productAttributes);
                    $productId = $product->getId();
                    //ENVIA O PRODUTO CONFIGURAVEL PARA O INTEGRA
                    list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newProduct($configurableProduct,$_cats,$_attrs,$preparedFields,$authentication,$environment);
                    //VERIFICANDO ERROS DO PRODUTO
                    if ($errorId == $productId) {
                        Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId,0, 'product');
                    } else {
                        Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'product');
                    }

                    $productControl = Mage::getStoreConfig('integracommerce/general/sku_control',Mage::app()->getStore());

                    if ($productControl == 2) {
                        $idProduct = $configurableProduct->getData('sku');
                    } else {
                        $idProduct = $configurableProduct->getId();
                    }

                    //ENVIA O PRODUTO SIMPLES PARA O INTEGRA
                    if (empty($Spictures) || !$Spictures) {
                        list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product,$configurableProduct,$pictures,$_Sattrs,$preparedFields,$idProduct,$authentication,$environment);
                    } else {
                        list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product,$configurableProduct,$Spictures,$_Sattrs,$preparedFields,$idProduct,$authentication,$environment);
                    }
                    //VERIFICANDO ERROS DA SKU
                    if ($errorId == $productId) {
                        Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'sku');
                    } else {
                        Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'sku');
                    }

                    list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::updatePrice($product,$authentication,$environment);
                    //VERIFICANDO ERROS DE PRECO
                    if ($errorId == $productId) {
                        Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'price');
                    } else {
                        Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'price');
                    }

                    list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::updateStock($product,$authentication,$environment);
                    //VERIFICANDO ERROS DE ESTOQUE
                    if ($errorId == $productId) {
                        Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'stock');
                    } else {
                        Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'stock');
                    }
                }                
            } else {
                $productId = $product->getId();

                list($_cats,$pictures) = Novapc_Integracommerce_Helper_IntegrationData::prepareProduct($product);
                $_attrs = Novapc_Integracommerce_Helper_IntegrationData::prepareAttributes($product,$_productAttributes,$productAttributes);

                list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newProduct($product,$_cats,$_attrs,$preparedFields,$authentication,$environment);
                //VERIFICANDO ERROS DE PRODUTO
                if ($errorId == $productId) {
                    Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'product');
                } else {
                    Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'product');
                }

                if ($productType == 'configurable') {
                    continue;
                }

                $productControl = Mage::getStoreConfig('integracommerce/general/sku_control',Mage::app()->getStore());

                if ($productControl == 2) {
                    $idProduct = $product->getData('sku');
                } else {
                    $idProduct = $product->getId();
                }

                $_skuAttrs = Novapc_Integracommerce_Helper_IntegrationData::prepareSkuAttributes($product);
                list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product,null,$pictures,$_skuAttrs,$preparedFields,$idProduct,$authentication,$environment);
                //VERIFICANDO ERROS DE SKU
                if ($errorId == $productId) {
                    Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'sku');
                } else {
                    Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'sku');
                }

                list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::updatePrice($product,$authentication,$environment);
                //VERIFICANDO ERROS DE PRECO
                if ($errorId == $productId) {
                    Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'price');
                } else {
                    Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'price');
                }

                list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::updateStock($product,$authentication,$environment);
                //VERIFICANDO ERROS DE ESTOQUE
                if ($errorId == $productId) {
                    Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'stock');
                } else {
                    Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'stock');
                }

            }

            sleep(1);
        }

        return;
    }

    public function orderUpdate()
    {
        $Collection = Mage::getModel('integracommerce/queue')->getCollection();
        $api_user = Mage::getStoreConfig('integracommerce/general/api_user',Mage::app()->getStore());
        $api_password = Mage::getStoreConfig('integracommerce/general/api_password',Mage::app()->getStore());
        $authentication = base64_encode($api_user . ':' . $api_password); 
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());

        foreach ($Collection as $item) {
            $itemIsDone = $item->getDone();
            if ($itemIsDone == 1) {
                continue;
            }

            $itemCreatedAt = $item->getCreatedAt();
            $itemLastUpdate = $item->getLastUpdate();
            if ($itemCreatedAt < $itemLastUpdate) {
                $item->setDone(1);
                $item->save();
                continue;
            }    

            $jsonBody = $item->getSentJson();

            if ($environment == 1) {
                $post_url = 'https://api.integracommerce.com.br/api/' . $item->getType();
            } else {
                $post_url = 'https://in.integracommerce.com.br/api/' . $item->getType();
            }

            $exec = Novapc_Integracommerce_Helper_IntegrationData::reUpdate($jsonBody,$authentication,$post_url);
            $item->setDone($exec);
            $item->save(); 
        
        }

        return;
    }     

}