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
    public static function integrateCategory($requested, $limits)
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        $collSize = (int) $limits['minute'];
        //FORCA O AMBIENTE COMO ADMIN DEVIDO A TABELAS FLAT COM MULTISTORE
        Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
        $categories = Mage::getModel('catalog/category')
                        ->getCollection()
                        ->addFieldToFilter('integracommerce_active', array('neq' => 1))
                        ->addFieldToFilter('level', array('gt' => 1))
                        ->setOrder('level', 'ASC')
                        ->addAttributeToSelect('*')
                        ->setPageSize($collSize)
                        ->setCurPage(1);

        $createdIds = array();
        $requestedMin = 0;
        foreach ($categories as $category) {
            $catLevel = $category->getData('level');

            if ($catLevel == 2) {
                $parentId = "";
            } else {
                $parentId = $category->getData('parent_id');
            }

            $catId = $category->getId();
            $catName = $category->getName();

            $result = self::postCategory($catId, $catName, $parentId, $environment);

            if ($result == 201 || $result == 204) {
                $createdIds[] = $catId;
            }

            $requestedMin++;
            $requested++;
            if ($requested == $limits['hour']) {
                break;
            }

            usleep(500000);

            $time = strtotime('s');
            $seconds = date("s", $time);
            if ($requestedMin >= $limits['minute'] && $seconds < 60) {
                $waitFor = 60 - $seconds;
                time_sleep_until(time()+$waitFor);
            }
        }

        if (!empty($createdIds)) {
            /*ATUALIZANDO O ATRIBUTO DE CONTROLE DO MODULO*/
            $categoriesUpdate = Mage::getModel('integracommerce/integration')
                ->getCollection()
                ->updateCategories(
                    $createdIds,
                    'integracommerce_active',
                    1
                );
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

    public static function integrateProduct($requested, $limits)
    {
        $skipProducts = Mage::getModel('integracommerce/update')
            ->getCollection()
            ->addFieldToFilter('requested_times', array('gteq' => 5))
            ->getProductIds();

        $collSize = (int) $limits['minute'];
        $exportType = Mage::getStoreConfig('integracommerce/general/export_type', Mage::app()->getStore());
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addFieldToFilter('integracommerce_active', array('neq' => 1))
            ->addAttributeToSelect('*');

        if ($exportType == 1) {
            $collection->addFieldToFilter('integracommerce_sync', array('eq' => 1));
        }

        if (!empty($skipProducts)) {
            $notificationModel = Mage::getModel('adminnotification/inbox');
            $latestNotice = $notificationModel->loadLatestNotice();
            $noticeTitle = (string) $latestNotice->getTitle();
            if ($noticeTitle !== 'Integracommerce - Erro de Sincronização: O Relatório contém itens Bloqueados!') {
                $notificationModel->addCritical(
                    "Integracommerce - Erro de Sincronização: O Relatório contém itens Bloqueados!",
                    "Quando um item encontra-se bloqueado o mesmo não será atualizado até que o erro apresentado seja
                corrigido e o item marcado como corrigido. Para realizar este procedimento, navegue até
                Integracommerce -> Relatorio, clique sobre um item bloqueado, verifique o erro no campo Erro,
                realize a correção (caso tenha dúvidas sobre o erro, por favor contate nosso suporte), volte ao
                relatório, marque o item corrigido, vá em Ações -> Erros Corrigidos -> Enviar. Após este processo
                o módulo tentará atualizar este item novamente."
                );
            }

            $collection->addFieldToFilter('entity_id', array('nin' => $skipProducts));
        }

        $collection->setPageSize($collSize)
            ->setCurPage(1);

        $return = self::productSelection($collection, $requested, $limits);

        return $return;
    }

    public static function productSelection($collection, $requested, $limits)
    {
        $initialAttributes = Mage::getStoreConfig('integracommerce/general/attributes', Mage::app()->getStore());
        $attributes = explode(',', $initialAttributes);
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        $attrCollection = Mage::getModel('integracommerce/attributes')->load(1, 'entity_id');
        $loadedAttrs = self::loadAttr($attrCollection);
        $configProd = Mage::getStoreConfig('integracommerce/general/configprod', Mage::app()->getStore());
        $dataHelper = Mage::helper('integracommerce/data');

        $requestedMin = 0;
        $seconds = 0;
        foreach ($collection as $product) {
            $prodType = $product->getTypeId();
            $height = $product->getData($loadedAttrs['4']);
            $width = $product->getData($loadedAttrs['5']);
            $length = $product->getData($loadedAttrs['6']);
            $weight = $product->getData($loadedAttrs['7']);

            if ($prodType !== 'configurable' && (empty($height) || empty($width) || empty($length) || empty($weight))) {
                $body = "Atributo: Altura(" . $loadedAttrs['4'] . "): " . $height . "
                    \n Atributo: Largura(" . $loadedAttrs['5'] . "): " . $width . "
                    \n Atributo: Comprimento(" . $loadedAttrs['6'] . "): " . $length . "
                    \n Atributo: Peso(" . $loadedAttrs['7'] . "): " . $weight;
                $productId = $product->getId();
                $response = "O produto não possui as informações de Altura, Largura, Comprimento ou Peso";

                Novapc_Integracommerce_Helper_Data::checkError($body, $response, $productId, 0, 'product');
                continue;
            }

            //VERIFICA SE O PRODUTO ESTA ASSOCIADO A CONFIGURABLES, SE SIM E A CONFIGURACAO FOR PRODUTO UNICO
            //PREPARA PARA ENVIAR O CONFIGURABLE COMO PRODUCT E ASSOCIAR O SIMPLE COMO SKU
            if ($prodType == 'simple' && $configProd == 1) {
                list($configRequested, $requestedMin) = self::configurableProduct(
                    $product, $environment, $loadedAttrs, $requested, $initialAttributes,
                $attributes, $limits, $requestedMin);

                if ($configRequested > 0) {
                    $requested = $requested + $configRequested;
                    continue;
                }
            }

            $isActive = (int) $product->getData('integracommerce_active');
            if ($prodType == 'configurable' && $isActive == 0) {
                continue;
            }

            $productId = $product->getId();

            list($productCats,$pictures) = self::prepareProduct($product);
            $productAttrs = self::prepareAttributes($product, $initialAttributes, $attributes);

            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newProduct(
                $product, $productCats, $productAttrs, $loadedAttrs, $environment
            );
            //VERIFICANDO ERROS DE PRODUTO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'product');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'product');
            }

            usleep(500000);

            if ($prodType == 'configurable') {
                $requested++;
                $requestedMin++;
                continue;
            }

            $productControl = Mage::getStoreConfig('integracommerce/general/sku_control', Mage::app()->getStore());

            $idProduct = $product->getData($productControl);

            $skuAttrs = self::prepareSkuAttributes($product, null);
            list($jsonBody, $response, $errorId) = $dataHelper::newSku(
                $product, $pictures, $skuAttrs, $loadedAttrs, $idProduct, $environment, null
            );

            //VERIFICANDO ERROS DE PRODUTO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'sku');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'sku');
            }

            $requestedMin++;
            $requested++;
            if ($requested == $limits['hour']) {
                return $requested;
            }

            usleep(500000);

            $time = strtotime('s');
            $seconds = date("s", $time);
            if ($requestedMin >= $limits['minute'] && $seconds < 60) {
                $waitFor = 60 - $seconds;
                time_sleep_until(time()+$waitFor);
            }
        }

        return $requested;
    }

    public static function configurableProduct($product, $environment, $loadAtr, $rqtd, $iniAttrs,
                                               $attributes, $limits, $requestedMin)
    {
        $cfgIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());

        if (empty($cfgIds)) {
            return array(0, $requestedMin);
        }

        $dataHelper = Mage::helper('integracommerce/data');

        //PREPARA AS INFORMACOES DO PRODUTO SIMPLES PARA O ENVIO
        $configRequested = 0;
        list($simpleCats,$simplePics) = self::prepareProduct($product);
        $idSimple = $product->getId();
        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control', Mage::app()->getStore());

        $configCollection = Mage::getModel('catalog/product')
            ->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $cfgIds))
            ->addAttributeToSelect('*');

        /*PARA CADA PRODUTO CONFIGURAVEL VINCULADO, O MODULO IRA FAZER O ENVIO DO CONFIGURAVEL E DO SIMPLES*/
        $seconds = 0;
        foreach ($configCollection as $configurableProduct) {
            $configurableId = $configurableProduct->getId();
            $simpleAttrs = self::prepareSkuAttributes($product, $configurableId);

            //PREPARA AS INFORMACOES DO PRODUTO CONFIGURAVEL PARA O ENVIO
            list($configurableCats,$pictures) = self::prepareProduct($configurableProduct);
            $configurableAttrs = self::prepareAttributes($configurableProduct, $iniAttrs, $attributes);
            $productId = $configurableProduct->getId();

            //ENVIA O PRODUTO CONFIGURAVEL PARA O INTEGRA
            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::newProduct(
                $configurableProduct, $configurableCats, $configurableAttrs, $loadAtr, $environment
            );

            //VERIFICANDO ERROS DO PRODUTO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'product');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 1, 'product');
            }

            usleep(500000);

            if ($productControl == 'sku') {
                $idProduct = $configurableProduct->getData('sku');
            } else {
                $idProduct = $configurableId;
            }

            //ENVIA O PRODUTO SIMPLES PARA O INTEGRA
            if (empty($simplePics) || !$simplePics) {
                list($jsonBody, $response, $errorId) = $dataHelper::newSku(
                    $product, $pictures, $simpleAttrs, $loadAtr, $idProduct, $environment, $configurableProduct
                );
            } else {
                list($jsonBody, $response, $errorId) = $dataHelper::newSku(
                    $product, $simplePics, $simpleAttrs, $loadAtr, $idProduct, $environment, $configurableProduct
                );
            }

            //VERIFICANDO ERROS DE SKU
            if ($errorId == $idSimple) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'sku');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $idSimple, 1, 'sku');
            }

            $requestedMin++;
            $configRequested++;
            $rqtd++;
            $time = strtotime('s');
            $seconds = date("s", $time);
            if ($rqtd == $limits['hour']) {
                return array($configRequested, $requestedMin);
            }

            usleep(500000);

            if ($requestedMin >= $limits['minute'] && $seconds < 60) {
                $waitFor = 60 - $seconds;
                time_sleep_until(time()+$waitFor);
            }
        }

        return array($configRequested, $requestedMin);
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
        $categoryIds = $product->getCategoryIds();

        /*SE O PRODUTO ESTIVER SEM CATEGORIAS E FOR ASSOCIADO A UM CONFIGURABLE CARREGA AS CATEGORIAS DO CONFIGURABLE*/
        if (empty($categoryIds) && !empty($configurableId)) {
            $configurableProduct = Mage::getModel('catalog/product')->load($configurableId);
            $categoryIds = $configurableProduct->getCategoryIds();
        }

        $catModelColl = Mage::getModel('integracommerce/sku')
            ->getCollection()
            ->addFieldToFilter('category', array('in' => $categoryIds))
            ->setOrder('category', 'DSC')
            ->addFieldToSelect('*');

        foreach ($catModelColl as $categoryModel) {
            /*CARREGA O CODIGO DO ATRIBUTO*/
            $attrCode = $categoryModel->getAttribute();

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
            if ((empty($frontendLabel) && empty($storeLabel)) || empty($attrValue)) {
                continue;
            }

            array_push(
                $attrsArray, array(
                    "Name" => (empty($frontendLabel) ? $storeLabel : $frontendLabel),
                    "Value" => $attrValue
                )
            );

            /*PARANDO EXECUCAO PARA ENVIAR APENAS 1 ATRIBUTO */
            break;
        }

        /*SE NAO ENCONTROU NENHUM ATRIBUTO RETORNA O ARRAY SEM INFORMACOES*/
        if (empty($attrsArray['Name'])) {
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
            $categoryCollection = Mage::getModel('catalog/category')
                ->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $categoryIds))
                ->setOrder('level', 'DSC')
                ->addAttributeToSelect('*');

            foreach ($categoryCollection as $category) {
                $catLevel = $category->getData('level');
                if ($catLevel <= 1) {
                    continue;
                }

                $name = $category->getName();
                $parentId = $category->getParentId();
                $categoryId = $category->getId();
                array_push(
                    $categories, array(
                        "Id" => $categoryId,
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
            $attrCollection->getIsbn()
        );

        return $loadedAttrs;

    }    

    public static function forceUpdate($alreadyRequested, $limits)
    {
        $queueCollection = Mage::getModel('integracommerce/update')
            ->getCollection()
            ->addFieldToFilter('requested_times', array('lt' => 5))
            ->getProductIds();

        $collSize = (int) $limits['minute'];
        $productCollection = Mage::getModel('catalog/product')->getCollection()
            ->addFieldToFilter('entity_id', array('in' => $queueCollection))
            ->addAttributeToSelect('*')
            ->setPageSize($collSize)
            ->setCurPage(1);

        $requested = self::productSelection($productCollection, $alreadyRequested, $limits);
        $dataHelper = Mage::helper('integracommerce/data');

        $productModel = Mage::getModel('integracommerce/integration')->load('Product Update', 'integra_model');
        $limits = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($productModel, '(PUT) api/Price');

        $requestedMin = 0;
        $executedSecs = 0;
        foreach ($productCollection as $product) {
            $productStatus = $product->getStatus();
            if ($productStatus == 2) {
                continue;
            }

            $productId = $product->getId();

            $productType = $product->getTypeId();
            if ($productType == 'configurable') {
                continue;
            }

            list($jsonBody, $response, $errorId) = $dataHelper::updatePrice($product);
            //VERIFICANDO ERROS DE PRECO
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'price');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 0, 'price');
            }

            usleep(500000);

            list($jsonBody, $response, $errorId) = Novapc_Integracommerce_Helper_Data::updateStock($product);
            //VERIFICANDO ERROS DE ESTOQUE
            if ($errorId == $productId) {
                Novapc_Integracommerce_Helper_Data::checkError($jsonBody, $response, $errorId, 0, 'stock');
            } else {
                Novapc_Integracommerce_Helper_Data::checkError(null, null, $productId, 0, 'stock');
            }

            $alreadyRequested++;
            if ($alreadyRequested == $requested) {
                break;
            }

            usleep(500000);

            $requestedMin++;
            $time = strtotime('s');
            $seconds = date("s", $time);
            if ($requestedMin >= $limits['minute'] && $seconds < 60) {
                $waitFor = 60 - $seconds;
                time_sleep_until(time()+$waitFor);
            }
        }

        return $requested;
    }

    public static function checkRequest($model, $method)
    {
        $requestModel = Mage::getModel('integracommerce/request')->load($method, 'name');
        $limits = array();

        if (!$requestModel->getId()) {
            $limits['message'] = 'Não foi encontrada a configuração de limites de requisição. Por favor, entre 
            em contato com nosso suporte.';

            return $limits;
        }

        $limits['minute'] = (int) $requestModel->getMinute();
        $limits['hour'] = (int) $requestModel->getHour();

        /*CARREGANDO A QUANTIDADE DE REQUISICOES JA FEITAS NA ULTIMA HORA*/
        $requestedHour = $model->getRequestedHour();

        /*CARREGANDO O HORARIO DA ULTIMA REQUISICAO*/
        $timeZone = Mage::getStoreConfig('general/locale/timezone');
        $lastRequest = new DateTime($model->getStatus(), new DateTimeZone($timeZone));
        $now = Novapc_Integracommerce_Helper_Data::currentDate();
        $lastRequestHour = $lastRequest->format('H');
        $currentHour = $now->format('H');

        //CHECANDO REQUISICOES HORA
        if ($requestedHour >= $limits['hour'] && $lastRequestHour == $currentHour) {
            $limits['message'] = 'O limite de requisições por hora foi atingido, por favor, tente mais tarde.';
        } elseif ($lastRequestHour !== $currentHour) {
            $model->setRequestedHour(0);
            $model->setAvailable(1);
            $model->save();
        } elseif ($requestedHour < $limits['hour'] && $lastRequestHour == $currentHour) {
            /*SE A QUANTIDADE DE REQUISICOES POR HORA FOR MENOR QUE O LIMITE E A DIFERENCA DE HORAS FOR MENOR QUE UM
            LIBERA O METODO*/
            $model->setAvailable(1);
            $model->save();
        }

        return $limits;
    }
}