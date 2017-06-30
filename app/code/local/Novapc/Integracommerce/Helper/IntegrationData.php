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

class Novapc_Integracommerce_Helper_IntegrationData extends Mage_Core_Helper_Abstract
{
    public static function integrateCategory($requested)
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        //FORCA O AMBIENTE COMO ADMIN DEVIDO A TABELAS FLAT COM MULTISTORE
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

        $categories = Mage::getModel('catalog/category')
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
                $parentId = "";
            } else {
                $parentId = $category->getData('parent_id');
            }

            $catId = $category->getId();
            $catName = $category->getName();

            $result = self::postCategory($catId, $catName, $parentId, $environment);

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

    public static function postCategory($catId, $catName, $parentId, $environment)
    {
        $body = array();
        array_push(
            $body, array(
                "Id" => $catId,
                "Name" => $catName,
                "ParentId" => $parentId
            )
        );

        $jsonBody = json_encode($body);

        $url = 'https://' . $environment . '.integracommerce.com.br/api/Category';

        $return = Novapc_Integracommerce_Helper_Data::callCurl("POST", $url, $jsonBody);

        return $return['httpCode'];
    }

    public static function integrateProduct($requested)
    {
        $exportType = Mage::getStoreConfig('integracommerce/general/export_type', Mage::app()->getStore());
        if ($exportType == 1) {
            $collection = Mage::getModel('catalog/product')->getCollection()
                            ->addFieldToFilter('integracommerce_sync', 1)
                            ->addFieldToFilter('integracommerce_active', 0)
                            ->addAttributeToSelect('*');

            $return = self::productSelection($collection, $requested);
        } elseif ($exportType == 2) {
            $collection = Mage::getModel('catalog/product')->getCollection()
                            ->addFieldToFilter('integracommerce_active', 0)
                            ->addAttributeToSelect('*');

            $return = self::productSelection($collection, $requested);
        }

        return $return;
    }

    public static function productSelection($collection, $requested)
    {
        $initialAttributes = Mage::getStoreConfig('integracommerce/general/attributes', Mage::app()->getStore());
        $attributes = explode(',', $initialAttributes);
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        $attrCollection = Mage::getModel('integracommerce/attributes')->load(1, 'entity_id');
        $loadedAttrs = self::loadAttr($attrCollection);
        $configProd = Mage::getStoreConfig('integracommerce/general/configprod', Mage::app()->getStore());

        foreach ($collection as $product) {
            $productType = $product->getTypeId();
            $height = $product->getData($loadedAttrs['4']);
            $width = $product->getData($loadedAttrs['5']);
            $length = $product->getData($loadedAttrs['6']);
            $weight = $product->getData($loadedAttrs['7']);

            if ($productType !== 'configurable') {
                if (empty($height) || empty($width) || empty($length) || empty($weight)) {
                    $queueItem = Mage::getModel('integracommerce/update')->load($product->getId(), 'product_id');
                    if (!$queueItem->getProductId()) {
                        $queueItem = Mage::getModel('integracommerce/update');
                        $queueItem->setProductId($product->getId());
                    }

                    $queueItem->setProductBody(
                        "Atributo: Altura(" . $loadedAttrs['4'] . "): " . $height . "
                        \nAtributo: Largura(" . $loadedAttrs['5'] . "): " . $width . "
                        \nAtributo: Comprimento(" . $loadedAttrs['6'] . "): " . $length . "
                        \nAtributo: Peso(" . $loadedAttrs['7'] . "): " . $weight
                    );
                    $queueItem->setProductError(
                        'O produto não possui as informações de Altura, Largura, Comprimento ou Peso'
                    );
                    $queueItem->save();
                    continue;
                }
            }

            //VERIFICA SE O PRODUTO ESTA ASSOCIADO A CONFIGURABLES, SE SIM E A CONFIGURACAO FOR PRODUTO UNICO
            //PREPARA PARA ENVIAR O CONFIGURABLE COMO PRODUCT E ASSOCIAR O SIMPLE COMO SKU
            if ($productType == 'simple' && $configProd == 1) {
                $configurableIds = Mage::getModel('catalog/product_type_configurable')
                    ->getParentIdsByChild($product->getId());
                if (!empty($configurableIds)) {
                    $configRequested = self::configurableProduct($product, $configurableIds, $environment, $loadedAttrs, $requested, $initialAttributes, $attributes);
                    $requested = $requested + $configRequested;
                    continue;
                }
            }

            if ($productType == 'configurable' && $product->getData('integracommerce_active') == 0) {
                continue;
            }

            $productId = $product->getId();

            list($productCats,$pictures) = self::prepareProduct($product);
            $productAttrs = self::prepareAttributes($product, $initialAttributes, $attributes);

            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newProduct($product, $productCats, $productAttrs, $loadedAttrs, $environment);
            //VERIFICANDO ERROS DE PRODUTO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'product');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'product');
            }

            $productControl = Mage::getStoreConfig('integracommerce/general/sku_control', Mage::app()->getStore());

            if ($productControl == 'sku') {
                $idProduct = $product->getData('sku');
            } else {
                $idProduct = $product->getId();
            }

            $skuAttrs = self::prepareSkuAttributes($product, null);
            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product, $pictures, $skuAttrs, $loadedAttrs, $idProduct, $environment, null);

            //VERIFICANDO ERROS DE PRODUTO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'sku');
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

    public static function configurableProduct($product, $configurableIds, $environment, $loadedAttrs, $requested, $initialAttributes, $attributes)
    {
        //PREPARA AS INFORMACOES DO PRODUTO SIMPLES PARA O ENVIO
        $configRequested = 0;
        list($simpleCats,$simplePics) = self::prepareProduct($product);
        $idSimple = $product->getId();
        //PARA CADA PRODUTO CONFIGURAVEL VINCULADO, O MODULO IRA FAZER O ENVIO DO CONFIGURAVEL E DO SIMPLES PARA CADA UM
        foreach ($configurableIds as $configurableId) {
            $simpleAttrs = self::prepareSkuAttributes($product, $configurableId);
            //CARREGA O PRODUTO CONFIGURAVEL DE ACORDO COM O ID RETORNADO
            $configurableProduct = Mage::getModel('catalog/product')->load($configurableId);
            //PREPARA AS INFORMACOES DO PRODUTO CONFIGURAVEL PARA O ENVIO
            list($configurableCats,$pictures) = self::prepareProduct($configurableProduct);
            $configurableAttrs = self::prepareAttributes($configurableProduct, $initialAttributes, $attributes);
            $productId = $configurableProduct->getId();

            //ENVIA O PRODUTO CONFIGURAVEL PARA O INTEGRA
            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newProduct($configurableProduct, $configurableCats, $configurableAttrs, $loadedAttrs, $environment);

            //VERIFICANDO ERROS DO PRODUTO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'product');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'product');
            }

            $productControl = Mage::getStoreConfig('integracommerce/general/sku_control', Mage::app()->getStore());

            if ($productControl == 'sku') {
                $idProduct = $configurableProduct->getData('sku');
            } else {
                $idProduct = $configurableProduct->getId();
            }

            //ENVIA O PRODUTO SIMPLES PARA O INTEGRA
            if (empty($simplePics) || !$simplePics) {
                list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product, $pictures, $simpleAttrs, $loadedAttrs, $idProduct, $environment, $configurableProduct);
            } else {
                list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newSku($product, $simplePics, $simpleAttrs, $loadedAttrs, $idProduct, $environment, $configurableProduct);
            }

            //VERIFICANDO ERROS DE SKU
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'sku');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $idSimple, 1, 'sku');
            }

            $configRequested++;
            $requested++;
            if ($requested == 600) {
                return $configRequested;
            }

            sleep(2);
        }

        return $configRequested;
    }

    public static function prepareAttributes($product, $initialAttributes, $attributes)
    {   
        $attrsArray = array();

        if (empty($initialAttributes)) {
            array_push(
                $attrsArray, array(
                    "Name" => "",
                    "Value" => ""
                )
            );                       
        } else {
            foreach ($attributes as $attrCode) {
                $attribute = $product->getResource()->getAttribute($attrCode);
                $attrValue = "";

                if ($attribute->getFrontendInput() == 'select') {
                    $attrValue = $product->getAttributeText($attrCode);
                } elseif ($attribute->getFrontendInput() == 'boolean') {
                    if ($product->getData($attrCode) == 0) {
                        $attrValue = 'Não';
                    } else {
                        $attrValue = 'Sim';
                    }
                } elseif ($attribute->getFrontendInput() == 'multiselect') {
                    $attrValue = $product->getAttributeText($attrCode);
                    if (is_array($attrValue)) {
                        $attrValue = implode(",", $attrValue);
                    }
                } else {
                    $attrValue = $product->getData($attrCode);
                }

                $frontendLabel = $attribute->getFrontendLabel();
                $storeLabel = $attribute->getStoreLabel();
                if (( empty($frontendLabel) && empty($storeLabel)) || empty($attrValue)) {
                    continue;
                }

                array_push(
                    $attrsArray, array(
                        "Name" => (empty($frontendLabel) ? $storeLabel : $frontendLabel),
                        "Value" => $attrValue 
                    )
                ); 
            }                
        }

        return $attrsArray;
    }

    public static function prepareSkuAttributes($product, $configurableId = null)
    {
        $attrsArray = array();
        $i = 0;

        $categoryIds = $product->getCategoryIds();

        /*SE O PRODUTO ESTIVER SEM CATEGORIAS E FOR ASSOCIADO A UM CONFIGURABLE CARREGA
        AS CATEGORIAS DO CONFIGURABLE*/
        if (empty($categoryIds) && !empty($configurableId)) {
            $configurableProduct = Mage::getModel('catalog/product')->load($configurableId);
            $categoryIds = $configurableProduct->getCategoryIds();
        }

        /*INVERTENDO A ORDEM DO ARRAY PARA COMECAR DO ULTIMO NIVEL DE CATEGORIA*/
        $categoryIds = array_reverse($categoryIds);

        foreach ($categoryIds as $categoryId) {
            /*CARREGA O MODEL DE ATRIBUTOS DO MODULO ATRAVES DO CODIGO DA CATEGORIA*/
            $categoryModel = Mage::getModel('integracommerce/sku')->load($categoryId, 'category');

            /*CARREGA O CODIGO DO ATRIBUTO*/
            $attrCode = $categoryModel->getAttribute();

            /*CASO NAO RETORNE NENHUM ATRIBUTO, CARREGA O CODIGO DA CATEGORIA DE NIVEL SUPERIOR E TENTA CARREGAR
            O ATRIBUTO ATRAVES DESSE CODIGO*/
            if (!$attrCode || empty($attrCode)) {
                $category = Mage::getModel('catalog/category')->load($categoryId);
                $parentId = $category->getData('parent_id');

                $categoryModel = Mage::getModel('integracommerce/sku')->load($parentId, 'category');

                $attrCode = $categoryModel->getAttribute();

                if (!$attrCode || empty($attrCode)) {
                    continue;
                }
            }

            /*CARREGA O ATRIBUTO*/
            $attribute = $product->getResource()->getAttribute($attrCode);
            $attrValue = "";

            /*VERIFICA O TIPO DO ATRIBUTO E CARREGA O SEU VALOR DE ACORDO COM SEU TIPO*/
            if ($attribute->getFrontendInput() == 'select') {
                $attrValue = $product->getAttributeText($attrCode);
            } elseif ($attribute->getFrontendInput() == 'boolean') {
                if ($product->getData($attrCode) == 0) {
                    $attrValue = 'Não';
                } else {
                    $attrValue = 'Sim';
                }
            } elseif ($attribute->getFrontendInput() == 'multiselect') {
                $attrValue = $product->getAttributeText($attrCode);
                if (is_array($attrValue)) {
                    $attrValue = implode(",", $attrValue);
                }
            } else {
                $attrValue = $product->getData($attrCode);
            }

            /*CARREGA AS LABELS DO ATRIBUTO, A PRIORIDADE SERA DA FRONTENDLABEL*/
            $frontendLabel = $attribute->getFrontendLabel();
            $storeLabel = $attribute->getStoreLabel();
            if (( empty($frontendLabel) && empty($storeLabel)) || empty($attrValue)) {
                continue;
            }

            array_push(
                $attrsArray, array(
                    "Name" => (empty($frontendLabel) ? $storeLabel : $frontendLabel),
                    "Value" => $attrValue
                )
            );

            $i++;

            /*PARANDO EXECUCAO PARA ENVIAR APENAS 1 ATRIBUTO*/
            break;
        }

        /*SE NAO ENCONTROU NENHUM ATRIBUTO RETORNA O ARRAY SEM INFORMACOES*/
        if ($i == 0) {
            array_push(
                $attrsArray, array(
                    "Name" => "",
                    "Value" => ""
                )
            );
        }

        return $attrsArray;
    }

    public static function prepareProduct($product)
    {
        $product->getResource()->getAttribute('media_gallery')
            ->getBackend()->afterLoad($product);

        $categories = array();
        $pictures = array();

        $categoryIds = $product->getCategoryIds();
        if (count($categoryIds) <= 1) {
                $actual = Mage::getModel('catalog/category')->load(array_shift($categoryIds));
                $name = $actual->getName();
            array_push(
                $categories, array(
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
                array_push(
                    $categories, array(
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

        return array($categories,$pictures);
    }

    public static function loadAttr($attrCollection)
    { 
        $loadedAttrs = array(
            $attrCollection->getNbmOrigin(),
            $attrCollection->getNbmNumber(),
            $attrCollection->getWarranty(),
            $attrCollection->getBrand(),
            $attrCollection->getHeight(),
            $attrCollection->getWidth(),
            $attrCollection->getLength(),
            $attrCollection->getWeight(),
            $attrCollection->getEan(),
            $attrCollection->getNcm(),
            $attrCollection->getIsbn()
        );

        return $loadedAttrs;

    }    

    public static function forceUpdate($alreadyRequested)
    {
        $queueCollection = Mage::getModel('integracommerce/update')->getCollection()->getAllIds();
        $productCollection = Mage::getModel('catalog/product')->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $queueCollection))
            ->addAttributeToSelect('*');

        $requested = self::productSelection($productCollection, $alreadyRequested);

        foreach ($productCollection as $product) {
            $productId = $product->getId();

            $productType = $product->getTypeId();
            if ($productType == 'configurable') {
                continue;
            }

            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::updatePrice($product);
            //VERIFICANDO ERROS DE PRECO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'price');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'price');
            }

            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::updateStock($product);
            //VERIFICANDO ERROS DE ESTOQUE
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'stock');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'stock');
            }

            $alreadyRequested++;
            if ($alreadyRequested == $requested) {
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

        /*CARREGANDO A QUANTIDADE DE REQUISICOES POR TEMPO*/
        $requestedHour = $model->getRequestedHour();
        $requestedDay = $model->getRequestedDay();
        $requestedWeek = $model->getRequestedWeek();

        /*CARREGANDO O HORARIO DA ULTIMA REQUISICAO*/
        $status = new DateTime($model->getStatus());
        $initialHour = new DateTime($model->getInitialHour());
        $now = new DateTime(Mage::getSingleton('core/date')->date('Y-m-d H:i:s'));
        $diff = date_diff($status, $now);
        $diffInitial = date_diff($initialHour, $now);

        //CHECANDO REQUISICOES HORA
        if ($requestedHour >= $hour && $diff->h < 1) {
            $message = 'O limite de ' . $hour . ' requisições por hora foi atingido, por favor, tente mais tarde.';
        } elseif ($diff->h >= 1) {
            /*SE A DIFERENCA DE HORAS FOR MAIOR OU IGUAL A UM LIBERA O METODO*/
            $model->setRequestedHour(0);
            $model->setAvailable(1);
            $model->save();
        } elseif ($requestedHour < $hour && $diff->h < 1) {
            /*SE A QUANTIDADE DE REQUISICOES POR HORA FOR MENOR QUE O LIMITE E A DIFERENCA DE HORAS FOR MENOR QUE UM
            LIBERA O METODO*/
            $model->setAvailable(1);
            $model->save();
        }

        //CHECANDO REQUISICOES DIA
        if ($requestedDay >= $day && $diff->d < 1) {
            $message = 'O limite de ' . $day . ' requisições por dia foi atingido, por favor, tente mais tarde.';
        } elseif ($diff->d >= 1) {
            /*SE A DIFERENCA DE DIAS FOR MAIOR OU IGUAL A UM LIBERA O METODO*/
            $model->setRequestedDay(0);
            $model->save();
        }

        //CHECANDO REQUISICOES SEMANA
        if ($requestedWeek >= $week && $diffInitial->d < 7) {
            $message = 'O limite de ' . $week . ' requisições por semana foi atingido, por favor, tente mais tarde.';
        } elseif ($diffInitial->d >= 7) {
            /*SE A DIFERENCA DE DIAS FOR MAIOR OU IGUAL A SETE LIBERA O METODO*/
            $model->setInitialHour(null);
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