<?php
/**
 * Novapc Integracommerce
 * 
 * @category     Novapc
 * @package      Novapc_Integracommerce 
 * @copyright    Copyright (c) 2016 Novapc (http://www.novapc.com.br/)
 * @author       Novapc
 * @version      Release: 1.0.0 
 */

class Novapc_Integracommerce_Helper_IntegrationData extends Mage_Core_Helper_Abstract
{
    public static function integrateCategory($authentication, $requested)
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());
        $configValue = Mage::getStoreConfig('catalog/frontend/flat_catalog_category');

		$categories = Mage::getModel('catalog/category')
                        ->setStoreId(0)
    					->getCollection()
                        ->addFieldToFilter('integracommerce_active', array('neq' => 1))
                        ->setOrder('level', 'ASC')
    					->addAttributeToSelect('*');

        foreach ($categories as $category) {
            $catLevel = $category->getData('level');

            if ($catLevel <= 1) {
                continue;
            }

            if ($catLevel == 2) {
                $_parent_id = "";
            } else {
                $_parent_id = $category->getData('parent_id');
            }

            $cat_id = $category->getId();
            $cat_name = $category->getName();

            $result = self::postCategory($cat_id, $cat_name, $_parent_id, $authentication, $environment);

            if ($result == 201 || $result == 204) {
                $attrCode = 'integracommerce_active';
                $category->setData($attrCode, '1');
                $category->save();
            }

            $requested++;
            if ($requested == 600) {
                break;
            }
            sleep(2);
        }

        return $requested;
	} 

    public static function postCategory($cat_id, $cat_name, $_parent_id, $authentication, $environment)
    {
        $body = array();
        array_push($body,
            array(
                "Id" => $cat_id,
                "Name" => $cat_name,
                "ParentId" => $_parent_id
            )
        );

        $jsonBody = json_encode($body);

        if ($environment == 1) {
            $post_url = 'https://api.integracommerce.com.br/api/Category';
        } else {
            $post_url = 'https://in.integracommerce.com.br/api/Category';
        }

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Basic " . $authentication . ""));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $jsonBody);

        $_curl_exe = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        return $httpcode;

    }

    public static function integrateProduct($authentication, $requested)
    {
        $exportType = Mage::getStoreConfig('integracommerce/general/export_type',Mage::app()->getStore());
        if ($exportType == 1) {
            $collection = Mage::getModel('catalog/product')->getCollection()
                            ->addFieldToFilter('integracommerce_sync',1)
                            ->addFieldToFilter('integracommerce_active',0)
                            ->addAttributeToSelect('*');

            $return = self::productSelection($collection, $authentication, $requested);

        } elseif ($exportType == 2) {
            $collection = Mage::getModel('catalog/product')->getCollection()
                            ->addFieldToFilter('integracommerce_active',0)
                            ->addAttributeToSelect('*');

            $return = self::productSelection($collection, $authentication, $requested);
        }

        return $return;
    }

    public static function productSelection($collection, $authentication, $requested)
    {
        $_attributes = Mage::getStoreConfig('integracommerce/general/attributes',Mage::app()->getStore());
        $attributes = explode(',', $_attributes);

        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());       

        $attrCollection = Mage::getModel('integracommerce/attributes')->load(1,'entity_id');  

        $loadedAttrs = self::loadAttr($attrCollection);

        $configProd = Mage::getStoreConfig('integracommerce/general/configprod',Mage::app()->getStore());

        foreach ($collection as $product) {
            $productType = $product->getTypeId();

            if ($productType == 'configurable') {
                continue;
            }

            $height = $product->getData($loadedAttrs['4']);
            $width = $product->getData($loadedAttrs['5']);
            $length = $product->getData($loadedAttrs['6']);
            $weight = $product->getData($loadedAttrs['7']);

            if (empty($height) || empty($width) || empty($length) || empty($weight)) {
                $queueItem = Mage::getModel('integracommerce/update')->load($product->getId(), 'product_id');
                if (!$queueItem->getProductId()) {
                    $queueItem = Mage::getModel('integracommerce/update');
                    $queueItem->setProductId($product->getId());
                }
                $queueItem->setProductBody("Atributo: Altura(". $loadedAttrs['4'] ."): ". $height ."\nAtributo: Largura(".$loadedAttrs['5']  ."): ". $width ."\nAtributo: Comprimento(". $loadedAttrs['6'] ."): ". $length ."\nAtributo: Peso(". $loadedAttrs['7'] ."): ". $weight);
                $queueItem->setProductError('O produto não possui as informações de Altura, Largura, Comprimento ou Peso');
                $queueItem->save();
                continue;
            }

            if ($product->getTypeId() == 'simple') {
                $configurableIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            }

            //VERIFICA SE O PRODUTO ESTA ASSOCIADO A CONFIGURABLES, SE SIM E A CONFIGURACAO FOR PRODUTO UNICO
            //PREPARA PARA ENVIAR O CONFIGURABLE COMO PRODUCT E ASSOCIAR O SIMPLE COMO SKU
            if (!empty($configurableIds) && $configProd == 1) {
                //PREPARA AS INFORMACOES DO PRODUTO SIMPLES PARA O ENVIO
                list($_Scats,$Spictures) = self::prepareProduct($product);
                $idSimple = $product->getId();
                //PARA CADA PRODUTO CONFIGURAVEL VINCULADO, O MODULO IRA FAZER O ENVIO DO CONFIGURAVEL E DO SIMPLES PARA CADA UM
                foreach ($configurableIds as $configurableId) {
                    $_Sattrs = self::prepareSkuAttributes($product, $configurableId);
                    //CARREGA O PRODUTO CONFIGURAVEL DE ACORDO COM O ID RETORNADO
                    $configurableProduct = Mage::getModel('catalog/product')->load($configurableId);
                    //PREPARA AS INFORMACOES DO PRODUTO CONFIGURAVEL PARA O ENVIO
                    list($_cats,$pictures) = self::prepareProduct($configurableProduct);
                    $_attrs = self::prepareAttributes($configurableProduct,$_attributes,$attributes);
                    $productId = $configurableProduct->getId();
                    //ENVIA O PRODUTO CONFIGURAVEL PARA O INTEGRA

                    list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newProduct($configurableProduct,$_cats,$_attrs,$loadedAttrs,$authentication,$environment);

                    //VERIFICANDO ERROS DO PRODUTO
                    if ($errorId == $productId) {
                        Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0,'product');
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
                        list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product,$configurableProduct,$pictures,$_Sattrs,$loadedAttrs,$idProduct,$authentication,$environment);
                    } else {
                        list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product,$configurableProduct,$Spictures,$_Sattrs,$loadedAttrs,$idProduct,$authentication,$environment);
                    }

                    //VERIFICANDO ERROS DE SKU
                    if ($errorId == $productId) {
                        Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId,0, 'sku');
                    } else {
                        Novapc_Integracommerce_Helper_Data::checkError(null, null, $idSimple, 1, 'sku');
                    }

                    $requested++;
                    if ($requested == 600) {
                        return $requested;
                    }
                    sleep(2);
                }

                continue;
            }

            $productId = $product->getId();

            list($_cats,$pictures) = self::prepareProduct($product);
            $_attrs = self::prepareAttributes($product,$_attributes,$attributes);

            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newProduct($product,$_cats,$_attrs,$loadedAttrs,$authentication,$environment);
            //VERIFICANDO ERROS DE PRODUTO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId,0, 'product');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'product');
            }

            $productControl = Mage::getStoreConfig('integracommerce/general/sku_control',Mage::app()->getStore());

            if ($productControl == 2) {
                $idProduct = $product->getData('sku');
            } else {
                $idProduct = $product->getId();
            }

            $_skuAttrs = self::prepareSkuAttributes($product, null);
            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product,null,$pictures,$_skuAttrs,$loadedAttrs,$idProduct,$authentication,$environment);

            //VERIFICANDO ERROS DE PRODUTO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId,0, 'sku');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'sku');
            }

            $requested++;
            if ($requested == 600) {
                return $requested;
            }
            sleep(2);
        }

        return $requested;
    }

    public static function prepareAttributes($product,$_attributes,$attributes)
    {   
        $_attrs = array();

        if (empty($_attributes)) { 
            array_push($_attrs, 
                array(
                    "Name" => "",
                    "Value" => ""
                )
            );                       
        } else {
            foreach ($attributes as $attr_code) {
                $attribute = $product->getResource()->getAttribute($attr_code);
                $attrValue = "";

                if ($attribute->getFrontendInput() == 'select') {
                    $attrValue = $product->getAttributeText($attr_code);
                } elseif ($attribute->getFrontendInput() == 'boolean') {
                    if ($product->getData($attr_code) == 0) {
                        $attrValue = 'Não';
                    } else {
                        $attrValue = 'Sim';
                    }
                } elseif ($attribute->getFrontendInput() == 'multiselect') {
                    $attrValue = $product->getAttributeText($attr_code);
                    if (is_array($attrValue)) {
                        $attrValue = implode(",",$attrValue);
                    }
                } else {
                    $attrValue = $product->getData($attr_code);
                }

                $frontendLabel = $attribute->getFrontendLabel();
                $storeLabel = $attribute->getStoreLabel();
                if (( empty($frontendLabel) && empty($storeLabel)) || empty($attrValue)) {
                    continue;
                }

                array_push($_attrs, 
                    array(
                        "Name" => (empty($frontendLabel) ? $storeLabel : $frontendLabel),
                        "Value" => $attrValue 
                    )
                ); 
            }                
        }

        return $_attrs;
    }

    public static function prepareSkuAttributes($product, $configurableId = null)
    {
        $_attrs = array();
        $i = 0;

        $categoryIds = $product->getCategoryIds();

        if (empty($categoryIds) && !empty($configurableId)) {
            $configurableProduct = Mage::getModel('catalog/product')->load($configurableId);
            $categoryIds = $configurableProduct->getCategoryIds();
        }

        //INVERTENDO A ORDEM DO ARRAY PARA COMECAR DO ULTIMO NIVEL DE CATEGORIA
        $categoryIds = array_reverse($categoryIds);

        foreach ($categoryIds as $categoryId) {
            $categoryModel = Mage::getModel('integracommerce/sku')
                ->getCollection()
                ->addFieldToFilter('category',$categoryId)
                ->getFirstItem();

            $attr_code = $categoryModel->getAttribute();

            if (!$attr_code || empty($attr_code)) {
                $category = Mage::getModel('catalog/category')->load($categoryId);
                $parentId = $category->getData('parent_id');

                $categoryModel = Mage::getModel('integracommerce/sku')
                    ->getCollection()
                    ->addFieldToFilter('category',$parentId)
                    ->getFirstItem();

                $attr_code = $categoryModel->getAttribute();

                if (!$attr_code || empty($attr_code)) {
                    continue;
                }
            }

            $attribute = $product->getResource()->getAttribute($attr_code);
            $attrValue = "";

            if ($attribute->getFrontendInput() == 'select') {
                $attrValue = $product->getAttributeText($attr_code);
            } elseif ($attribute->getFrontendInput() == 'boolean') {
                if ($product->getData($attr_code) == 0) {
                    $attrValue = 'Não';
                } else {
                    $attrValue = 'Sim';
                }
            } elseif ($attribute->getFrontendInput() == 'multiselect') {
                $attrValue = $product->getAttributeText($attr_code);
                if (is_array($attrValue)) {
                    $attrValue = implode(",",$attrValue);
                }
            } else {
                $attrValue = $product->getData($attr_code);
            }

            $frontendLabel = $attribute->getFrontendLabel();
            $storeLabel = $attribute->getStoreLabel();
            if (( empty($frontendLabel) && empty($storeLabel)) || empty($attrValue)) {
                continue;
            }

            array_push($_attrs,
                array(
                    "Name" => (empty($frontendLabel) ? $storeLabel : $frontendLabel),
                    "Value" => $attrValue
                )
            );

            $i++;

            //PARANDO EXECUCAO PARA ENVIAR APENAS 1 ATRIBUTO
            break;
        }

        if ($i == 0) {
            array_push($_attrs,
                array(
                    "Name" => "",
                    "Value" => ""
                )
            );
        }

        return $_attrs;
    }

    public static function prepareProduct($product)
    {
        $product->getResource()->getAttribute('media_gallery')
            ->getBackend()->afterLoad($product);

        $_cats = array();        
        $pictures = array();

        $categoryIds = $product->getCategoryIds();
        if (count($categoryIds) <= 1) {
                $actual = Mage::getModel('catalog/category')->load(array_shift($categoryIds));
                $name = $actual->getName();
            array_push($_cats, 
                array(
                    "Id" => $actual->getData('entity_id'),
                    "Name" => $name,
                    "ParentId" => $actual->getData('parent_id')
                    )
            );  
        } else {            
            foreach ($categoryIds as $key => $category) {
                $actual = Mage::getModel('catalog/category')->load($category);
                $catLevel = $actual->getData('level');
                if ($catLevel <= 1) {
                    continue;
                }
                $name = $actual->getName();
                $parentId = $actual->getParentId();
                array_push($_cats, 
                    array(
                        "Id" => $category,
                        "Name" => $name,
                        "ParentId" => ($parentId == 2 ? '' : $parentId)
                        )
                ); 
            }
        }

        $galleryData = $product->getData('media_gallery');
        if (is_array($galleryData['images'])) {
            $newGallery = $galleryData['images'];
        } else {
            $newGallery = json_decode($galleryData['images'], true);
        }

        if (!is_array($newGallery)) {
            $newGallery = array($newGallery);
        }

        $baseImage = $product->getImage();
        if ($baseImage && $baseImage !== 'no_selection' && !empty($baseImage)) {
            $pictures[] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'. $baseImage;
        }

        foreach ($newGallery as $image) {
            if ($baseImage == $image['file']) {
                continue;
            } else {
                $pictures[] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$image['file'];
            }
        }

        return array($_cats,$pictures);   
    }

    public static function loadAttr($attrCollection)
    { 
        $loadedAttrs = array($attrCollection->getNbmOrigin(), $attrCollection->getNbmNumber(), $attrCollection->getWarranty(), $attrCollection->getBrand(), $attrCollection->getHeight(), $attrCollection->getWidth(), $attrCollection->getLength(), $attrCollection->getWeight(), $attrCollection->getEan(), $attrCollection->getNcm(), $attrCollection->getIsbn());

        return $loadedAttrs;

    }    

    public static function forceUpdate($authentication, $alreadyRequested)
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());
        $queueCollection = Mage::getModel('integracommerce/update')->getCollection()->getAllIds();
        $productCollection = Mage::getModel('catalog/product')->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $queueCollection))
            ->addAttributeToSelect('*');

        $requested = self::productSelection($productCollection,$authentication, $alreadyRequested);

        foreach ($productCollection as $product) {
            $productId = $product->getId();

            $productType = $product->getTypeId();
            if ($productType == 'configurable') {
                continue;
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

            $alreadyRequested++;
            if ($alreadyRequested == 600) {
                break;
            }
        }

        return $requested;
    }

    public static function checkRequest($model, $method)
    {
        if ($method == 'get') {
            $hour = 900;
            $day = 10800;
            $week = 75000;
        } elseif ($method == 'post' || $method == 'put') {
            $hour = 600;
            $day = 7200;
            $week = 75000;
        } elseif ($method == 'getid') {
            $hour = 1800;
            $day = 21600;
            $week = 75000;
        }

        $requestedHour = $model->getRequestedHour();
        $requestedDay = $model->getRequestedDay();
        $requestedWeek = $model->getRequestedWeek();
        $status = new DateTime($model->getStatus());
        $now = new DateTime(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        $diff = date_diff($status, $now);

        //CHECANDO REQUISICOES HORA
        if ($requestedHour >= $hour && $diff->h < 1) {
            $message = 'O limite de ' . $hour . ' requisições por hora foi atingido, por favor, tente mais tarde.';
        } elseif ($diff->h >= 1) {
            $model->setRequestedHour(0);
            $model->setAvailable(1);
            $model->save();
        } elseif ($requestedHour < $hour && $diff->h < 1) {
            $model->setAvailable(1);
            $model->save();
        }

        //CHECANDO REQUISICOES DIA
        if ($requestedDay >= $day && $diff->d < 1) {
            $message = 'O limite de ' . $day . ' requisições por dia foi atingido, por favor, tente mais tarde.';
        } elseif ($diff->d >= 1) {
            $model->setRequestedDay(0);
            $model->save();
        }

        //CHECANDO REQUISICOES SEMANA
        if ($requestedWeek >= $week && $diff->d < 7) {
            $message = 'O limite de ' . $week . ' requisições por semana foi atingido, por favor, tente mais tarde.';
        } elseif ($diff->d >= 7) {
            $model->setRequestedWeek(0);
            $model->save();
        }

        if (isset($message)) {
            return $message;
        } else {
            return;
        }
    }
}