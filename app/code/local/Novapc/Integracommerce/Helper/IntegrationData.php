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

//require_once('./app/Mage.php');
Mage::app();

class Novapc_Integracommerce_Helper_IntegrationData extends Mage_Core_Helper_Abstract
{
    public static function integrateCategory($authentication)
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());

       /* $configValue = Mage::getStoreConfig('catalog/frontend/flat_catalog_category');
        if ($configValue == 1) {
            $storeId = Mage::app()->getStore()->getStoreId();
        } else {
            $storeId = 0;
        }*/

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
                    //->getResource()
                    //->saveAttribute($category, $attrCode);
            }
            sleep(2);
        }

        return;
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

    public static function integrateProduct($authentication)
    {
        $exportType = Mage::getStoreConfig('integracommerce/general/export_type',Mage::app()->getStore());
        if ($exportType == 1) {
            $collection = Mage::getModel('catalog/product')->getCollection()
                            ->addFieldToFilter('integracommerce_sync',1)
                            ->addFieldToFilter('integracommerce_active',0)
                            ->addAttributeToSelect('*');

            $return = self::productSelection($collection, $authentication);

        } elseif ($exportType == 2) {
            $collection = Mage::getModel('catalog/product')->getCollection()
                            ->addFieldToFilter('integracommerce_active',0)
                            ->addAttributeToSelect('*');

            $size = $collection->getSize();
            $sizeFiltered = 0;
            $return = self::allProducts($collection, $size, $sizeFiltered, $authentication);
        }

        return;
    }

    public static function productSelection($collection, $authentication)
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
                $queueItem->setProductBody('Erro na Sincronização');
                $queueItem->setProductError('O produto não possui as informações de Altura, Largura, Comprimento ou Peso');
                $queueItem->save();
                continue;
            }

            if ($product->getTypeId() == 'simple') {
                $configurableIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            }

            if (!empty($configurableIds) && $configProd == 1) {
                //PREPARA AS INFORMACOES DO PRODUTO SIMPLES PARA O ENVIO
                list($_Scats,$Spictures) = self::prepareProduct($product);
                $_Sattrs = self::prepareSkuAttributes($product);
                //PARA CADA PRODUTO CONFIGURAVEL VINCULADO, O MODULO IRA FAZER O ENVIO DO CONFIGURAVEL E DO SIMPLES PARA CADA UM
                foreach ($configurableIds as $configurableId) {
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
                        Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'sku');
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

            $_skuAttrs = self::prepareSkuAttributes($product);
            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product,null,$pictures,$_skuAttrs,$loadedAttrs,$idProduct,$authentication,$environment);

            //VERIFICANDO ERROS DE PRODUTO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId,0, 'sku');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'sku');
            }

            sleep(2);
        }

        return;
    }

    public static function allProducts($collection, $size, $sizeFiltered, $authentication)
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
                $queueItem->setProductBody('Erro na Sincronização');
                $queueItem->setProductError('O produto não possui as informações de Altura, Largura, Comprimento ou Peso');
                $queueItem->save();
                continue;
            }

            if ($product->getTypeId() == 'simple') {
                $configurableIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
            }

            if (!empty($configurableIds) && $configProd == 1) {
                //PREPARA AS INFORMACOES DO PRODUTO SIMPLES PARA O ENVIO
                list($_Scats,$Spictures) = self::prepareProduct($product);
                $_Sattrs = self::prepareSkuAttributes($product);
                //PARA CADA PRODUTO CONFIGURAVEL VINCULADO, O MODULO IRA FAZER O ENVIO DO CONFIGURAVEL E DO SIMPLES PARA CADA UM
                foreach ($configurableIds as $configurableId) {
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
                        list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product, $configurableProduct,$pictures,$_Sattrs,$loadedAttrs,$idProduct,$authentication,$environment);
                    } else {
                        list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product, $configurableProduct,$Spictures,$_Sattrs,$loadedAttrs,$idProduct,$authentication,$environment);
                    }

                    //VERIFICANDO ERROS DE SKU
                    if ($errorId == $productId) {
                        Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId,0, 'sku');
                    } else {
                        Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'sku');
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
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'product');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'product');
            }

            $productControl = Mage::getStoreConfig('integracommerce/general/sku_control',Mage::app()->getStore());

            if ($productControl == 2) {
                $idProduct = $product->getData('sku');
            } else {
                $idProduct = $product->getId();
            }

            $_skuAttrs = self::prepareSkuAttributes($product);
            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product,null, $pictures,$_skuAttrs,$loadedAttrs,$idProduct,$authentication,$environment);
            //VERIFICANDO ERROS DE PRODUTO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'sku');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'sku');
            }

            sleep(2);
        }

        return;
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

    public static function prepareSkuAttributes($product)
    {
        $_attrs = array();
        $i = 0;

        $categoryIds = $product->getCategoryIds();

        foreach ($categoryIds as $categoryId) {
            $categoryModel = Mage::getModel('integracommerce/sku')
                ->getCollection()
                ->addFieldToFilter('category',$categoryId)
                ->getFirstItem();

            $attr_code = $categoryModel->getAttribute();

            if (!$attr_code || empty($attr_code)) {
                continue;
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

    public static function reUpdate($jsonBody,$authentication,$post_url)
    {
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json", "Authorization: Basic " . $authentication . ""));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $jsonBody);

        $_curl_exe = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        if ($httpcode == 204) {
            $done = 1;
        } else {
            $done = 0;
        }

        return $done;
    }

    public static function forceUpdate($authentication)
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());
        $from = Mage::getModel('core/date')->date('Y-m-d H:i:s', strtotime("-5 minutes"));
        $productCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addFieldToFilter('integracommerce_active',1)
            ->addFieldToFilter('updated_at', array(
                'from'     => $from,
                'datetime' => true
            ))
            ->addAttributeToSelect('*')
            ->setPageSize(30);

        self::productSelection($productCollection,$authentication);

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

            //sleep(1);
        }

        return;
    }
}