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

class Novapc_Integracommerce_Helper_Data extends Mage_Core_Helper_Abstract
{
    public static function getStore()
    {
        $storeId = Mage::getStoreConfig('integracommerce/order_status/store', Mage::app()->getStore());
        $store = Mage::getModel('core/store')->load($storeId);

        if ($store->getId()) {
            return $store;
        } else {
            $store = Mage::app()->getStore();
            if ($store->getId()) {
                return $store;
            }
        }
    }

    public static function updateStock($product)
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        $exportType = Mage::getStoreConfig('integracommerce/general/export_type', Mage::app()->getStore());
        if ($exportType == 1) {
            $isSync = (int) $product->getData('integracommerce_sync');
            if ($isSync == 0) {
                return;
            }   
        }     

        $isActive = (int) $product->getData('integracommerce_active');
        if ($isActive == 0) {
            return;
        }

        $stockItem = Mage::getModel('cataloginventory/stock_item')
            ->loadByProduct($product->getId());

        $isInStock = (int) $stockItem['is_in_stock'];
        if ($isInStock == 0) {
            $stockQuantity = 0;
        } else {
            $stockQuantity = (int) strstr($stockItem['qty'], '.', true);
        }

        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control', Mage::app()->getStore());
        if ($productControl == 'sku') {
            $idSku = $product->getData('sku');
        } else {
            $idSku = $product->getId();
        }

        $body = array();
        array_push(
            $body, array(
                'IdSku' => $idSku,
                'Quantity' => $stockQuantity
            )
        );

        $jsonBody = json_encode($body);

        $url = 'https://' . $environment . '.integracommerce.com.br/api/Stock';

        $return = self::callCurl("PUT", $url, $jsonBody);

        if ($return['httpCode'] !== 204 && $return['httpCode'] !== 201) {
            return array($jsonBody, $return, $product->getId());
        }
    }

    public static function newProduct($product, $_cats, $_attrs, $loadedAttrs, $environment)
    {
        $productAttributes = self::prepareProduct($product);

        if ($loadedAttrs['2'] !== 'not_selected') {
            $warranty = $product->getData($loadedAttrs['2']);
        } else {
            $warranty = "0";
        }

        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control', Mage::app()->getStore());

        if ($productControl == 'sku') {
            $idProduct = $product->getData('sku');
        } else {
            $idProduct = $product->getId();
        }

        $body = array(
            "idProduct" => $idProduct,
            "Name" => $product->getName(),
            "Code" => $product->getId(),
            "Brand" => $productAttributes['brand'],
            "NbmOrigin" => $productAttributes['nbmOrigin'],
            "NbmNumber" => $productAttributes['nbmNumber'],
            "WarrantyTime" => $warranty,
            "Active" => true,
            "Categories" => $_cats,
            "Attributes" => $_attrs
        );

        $jsonBody = json_encode($body);

        $url = 'https://' . $environment . '.integracommerce.com.br/api/Product';

         $isActive = (int) $product->getData('integracommerce_active');
        if ($isActive == 0) {
            $return = self::callCurl("POST", $url, $jsonBody);
        } elseif ($isActive == 1) {
            $return = self::callCurl("PUT", $url, $jsonBody);
        }

        $httpCode = (int) $return['httpCode'];
        if ($httpCode !== 204 && $httpCode !== 201) {
            return array($jsonBody, $return, $product->getId());
        }

        $productType = $product->getTypeId();
        if ($isActive == 0 && $productType == 'configurable') {
            Mage::getSingleton('catalog/product_action')->updateAttributes(
                array($product->getId()),
                array('integracommerce_active' => 1),
                0
            );
        }
    }

    public static function prepareProduct($product)
    {
        $nbmOrigin = Mage::getStoreConfig('integracommerce/attributes/nbm_origin', Mage::app()->getStore());
        $nbmNumber = Mage::getStoreConfig('integracommerce/attributes/nbm_number', Mage::app()->getStore());
        $brand = Mage::getStoreConfig('integracommerce/attributes/brand', Mage::app()->getStore());
        $returnData = array();

        if ($nbmOrigin !== 'not_selected') {
            $nbmOrigin = self::checkNbmOrigin($product, $nbmOrigin);
        } else {
            $nbmOrigin = "0";
        }

        if ($nbmNumber !== 'not_selected') {
            $nbmNumber = self::checkNbmNumber($product, $nbmNumber);
        } else {
            $nbmNumber = "";
        }

        if ($brand !== 'not_selected') {
            $brand = self::checkBrand($product, $brand);
        } else {
            $brand = "";
        }

        $returnData['nbmOrigin'] = $nbmOrigin;
        $returnData['nbmNumber'] = $nbmNumber;
        $returnData['brand'] = $brand;

        return $returnData;
    }

    public static function checkNbmOrigin($product, $attrCode)
    {
        $attribute = $product->getResource()->getAttribute($attrCode);
        $frontendInput = $attribute->getFrontendInput();

        if ($frontendInput == "select" || $frontendInput == "multiselect") {
            $attrValue = $product->getAttributeText($attrCode);
            if (is_array($attrValue)) {
                $attrValue = implode(",", $attrValue);
            }
        } else {
            $attrValue = $product->getData($attrCode);
        }

        if (empty($attrValue)) {
            return "0";
        }

        $estrangeiro = strpos($attrValue, 'Estrangeira');
        $internacional = strpos($attrValue, 'Internacional');
        $nacional = strpos($attrValue, 'Nacional');

        if ($internacional !== false || $estrangeiro !== false) {
            $nbmOrigin = "1";
        } elseif ($nacional !== false) {
            $nbmOrigin = "0";
        } else {
            $nbmOrigin = (string) $attrValue;
            if ($nbmOrigin !== "0" && $nbmOrigin !== "1") {
                $nbmOrigin = "0";
            }
        }

        return $nbmOrigin;
    }

    public static function checkNbmNumber($product, $attrCode)
    {
        $attribute = $product->getResource()->getAttribute($attrCode);
        $frontendInput = $attribute->getFrontendInput();

        if ($frontendInput == "select" || $frontendInput == "multiselect") {
            $nbmNumber = $product->getAttributeText($attrCode);
            if (is_array($nbmNumber)) {
                $nbmNumber = implode(",", $nbmNumber);
            }
        } else {
            $nbmNumber = $product->getData($attrCode);
        }

        if (empty($nbmNumber)) {
            return "";
        }

        if (strpos($nbmNumber, ".") !== false) {
            $nbmNumber = str_replace(".", "", $nbmNumber);
        }

        return $nbmNumber;
    }

    public static function checkBrand($product, $attrCode)
    {
        $attribute = $product->getResource()->getAttribute($attrCode);
        $frontendInput = $attribute->getFrontendInput();

        if ($frontendInput == "select" || $frontendInput == "multiselect") {
            $brand = $product->getAttributeText($attrCode);
            if (is_array($brand)) {
                $brand = implode(",", $brand);
            }
        } else {
            $brand = $product->getData($attrCode);
        }

        if (empty($brand)) {
            return "";
        } else {
            return $brand;
        }
    }

    public static function newSku($product, $pictures, $_attrs, $loadedAttrs, $productId, $environment, $cfgProd = null)
    {
        $url = 'https://' . $environment . '.integracommerce.com.br/api/Sku';
        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control', Mage::app()->getStore());
        list($heightValue, $widthValue, $lengthValue) = self::checkMeasure($product, $loadedAttrs);
        list($normalPrice, $specialPrice) = self::checkPrice($product, $cfgProd);
        $weight = self::checkWeight($product, $loadedAttrs);

        $stockItem = Mage::getModel('cataloginventory/stock_item')
               ->loadByProduct($product->getId());

        $isInStock = (int) $stockItem['is_in_stock'];
        if ($isInStock == 0) {
            $stockQuantity = 0;
        } else {
            $stockQuantity = (int) strstr($stockItem['qty'], '.', true);
        }

        $idSku = $product->getData($productControl);

        $productStatus = $product->getStatus();
        if ($productStatus == 2) {
            $skuStatus = false;
        } else {
            $skuStatus = true;
        }

        $description = $product->getData('description');
        if (empty($description) && !empty($cfgProd)) {
            $description = $cfgProd->getData('description');
        }

        $body = array(
            "idSku" => $idSku,
            "IdSkuErp" => $product->getData('sku'),
            "idProduct" => $productId,
            "Name" => $product->getName(),
            "Description" => $description,
            "Height" => $heightValue,
            "Width" => $widthValue,
            "Length" => $lengthValue,
            "Weight" => $weight,
            "CodeEan" => ($loadedAttrs['8'] == 'not_selected' ? "" : $product->getData($loadedAttrs['8'])),
            "CodeNcm" => ($loadedAttrs['1'] == 'not_selected' ? "" : $product->getData($loadedAttrs['1'])),
            "CodeIsbn" => ($loadedAttrs['9'] == 'not_selected' ? "" : $product->getData($loadedAttrs['9'])),
            "CodeNbm" => ($loadedAttrs['1'] == 'not_selected' ? "" : $product->getData($loadedAttrs['1'])),
            "Variation" => "",
            "StockQuantity" => $stockQuantity,
            "Status" => $skuStatus,
            "Price" => array(
                "ListPrice" => ($normalPrice < $specialPrice ? $specialPrice : $normalPrice),
                "SalePrice" => $specialPrice
            ),  
            "UrlImages" => $pictures,  
            "Attributes" => $_attrs
        );

        $jsonBody = json_encode($body);
        
        $isActive = (int) $product->getData('integracommerce_active');
        if ($isActive == 0) {
            $return = self::callCurl("POST", $url, $jsonBody);
        } elseif ($isActive == 1) {
            $return = self::callCurl("PUT", $url, $jsonBody);
        }

        $productId = $product->getId();

        $httpCode = (int) $return['httpCode'];
        if ($httpCode !== 204 && $httpCode !== 201) {
            return array($jsonBody, $return, $product->getId());
        }

        if ($isActive == 0) {
            Mage::getSingleton('catalog/product_action')->updateAttributes(
                array($product->getId()),
                array('integracommerce_active' => 1),
                0
            );
        }
    }

    public static function checkWeight($product, $loadedAttrs)
    {
        $weightUnit = Mage::getStoreConfig('integracommerce/general/weight_unit', Mage::app()->getStore());
        $weight = $product->getData($loadedAttrs['7']);

        if (strstr($weight, ".") !== false) {
            if ($weightUnit == 'grama') {
                $weight = strstr($weight, '.', true);
                $weight = $weight / 1000;
            } else {
                $weight = (float) $product->getData($loadedAttrs['7']);
            }
        } else {
            if ($weightUnit == 'grama') {
                $weight = $weight / 1000;
            } else {
                $weight = (int) $product->getData($loadedAttrs['7']);
            }
        }

        return $weight;
    }

    public static function checkPrice($product, $configProduct = null)
    {
        $normalPrice = $product->getPrice();

        if (empty($normalPrice) || $normalPrice <= 0) {
            if (!empty($configProduct) && $configProduct->getId()) {
                $product = $configProduct;
                $normalPrice = $product->getPrice();
            }
        }

        $specialPrice = $product->getSpecialPrice();
        if (empty($specialPrice) || $specialPrice <= 0) {
            $specialPrice = $normalPrice;
        } else {
            $specialFrom = $product->getSpecialFromDate();
            $now = self::currentDate('Y-m-d H:i:s', 'string');
            if (!empty($specialFrom) && $specialFrom <= $now) {
                $specialTo = $product->getSpecialToDate();
                if (!empty($specialTo) && $specialTo <= $now) {
                    $specialPrice = $normalPrice;
                }
            } else {
                $specialPrice = $normalPrice;
            }
        }

        return array($normalPrice, $specialPrice);
    }

    public static function checkMeasure($product, $loadedAttrs)
    {
        $measure = Mage::getStoreConfig('integracommerce/general/measure', Mage::app()->getStore());

        $heightValue = $product->getData($loadedAttrs['4']);
        $widthValue = $product->getData($loadedAttrs['5']);
        $lengthValue = $product->getData($loadedAttrs['6']);

        if ($measure && !empty($measure) && $measure == 1) {
            $heightValue = $heightValue / 100;
            $widthValue = $widthValue / 100;
            $lengthValue = $lengthValue / 100;
        } elseif ($measure && !empty($measure) && $measure == 3) {
            $heightValue = $heightValue / 1000;
            $widthValue = $widthValue / 1000;
            $lengthValue = $lengthValue / 1000;
        }

        return array($heightValue, $widthValue, $lengthValue);
    }

    public static function getOrders()
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());

        $url = "https://" . $environment . ".integracommerce.com.br/api/Order?page=1&perPage=10&status=approved";

        $return = self::callCurl("GET", $url, null);

        return $return;
    } 

    public static function updatePrice($product)
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        $isActive = (int) $product->getData('integracommerce_active');
        if ($isActive == 0) {
            return;
        }

        if ($product->getTypeId() == 'simple') {
            $configurableIds = Mage::getModel('catalog/product_type_configurable')
                ->getParentIdsByChild($product->getId());
        }

        $configProd = Mage::getStoreConfig('integracommerce/general/configprod', Mage::app()->getStore());

        if (!empty($configurableIds) && $configProd == 1) {
            $configCollection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addFieldToFilter('entity_id', array('in' => $configurableIds))
                ->addAttributeToSelect('*');

            foreach ($configCollection as $configurableProduct) {
                list($normalPrice, $specialPrice) = self::checkPrice($product, $configurableProduct);
            }
        } else {
            list($normalPrice, $specialPrice) = self::checkPrice($product);
        }

        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control', Mage::app()->getStore());
        if ($productControl == 'sku') {
            $idSku = $product->getData('sku');
        } else {
            $idSku = $product->getId();
        }

        $body = array();
        array_push(
            $body, array(
                'IdSku' => $idSku,
                'ListPrice' => ($normalPrice < $specialPrice ? $specialPrice : $normalPrice),
                'SalePrice' => $specialPrice
            )
        );

        $jsonBody = json_encode($body);

        $url = 'https://' . $environment . '.integracommerce.com.br/api/Price';

        $return = self::callCurl("PUT", $url, $jsonBody);

        if ($return['httpCode'] !== 204 && $return['httpCode'] !== 201) {
            return array($jsonBody, $return, $product->getId());
        }
    }

    public static function checkError($body = null, $response = null, $productId = null, $delete = null, $type = null)
    {
        $errorQueue = Mage::getModel('integracommerce/update')->load($productId, 'product_id');

        $errorProductId = $errorQueue->getProductId();

        if ($delete == 1 && !empty($errorProductId)) {
            $errorQueue->delete();
            return;
        }

        if (empty($response) && empty($body)) {
            return;
        }

        if (is_array($response) && !empty($response['Errors'])) {
            foreach ($response['Errors'] as $error) {
                $response = $error['Message'] . '. ';
            };
        } elseif (is_array($response) && empty($response['Errors'])) {
            $response = json_encode($response);
        }

        if (empty($errorProductId)) {
            $errorQueue->setProductId($productId);
        }

        $requestedTimes = $errorQueue->getRequestedTimes();
        $requestedTimes++;
        $errorQueue->setData($type . '_body', $body);
        $errorQueue->setData($type . '_error', $response);
        $errorQueue->setRequestedTimes($requestedTimes);
        $errorQueue->save();
    }

    public static function callCurl($method, $url, $body = null)
    {
        $apiUser = Mage::getStoreConfig('integracommerce/general/api_user', Mage::app()->getStore());
        $apiPassword = Mage::getStoreConfig('integracommerce/general/api_password', Mage::app()->getStore());
        $authentication = base64_encode($apiUser . ':' . $apiPassword);

        $headers = array(
            "Content-type: application/json",
            "Accept: application/json",
            "Authorization: Basic " . $authentication
        );

        if ($method == "GET") {
            $zendMethod = Zend_Http_Client::GET;
        } elseif ($method == "POST") {
            $zendMethod = Zend_Http_Client::POST;
        } elseif ($method == "PUT") {
            $zendMethod = Zend_Http_Client::PUT;
        }

        $connection = new Varien_Http_Adapter_Curl();
        if ($method == "PUT") {
            //ADICIONA AS OPTIONS MANUALMENTE POIS NATIVAMENTE O WRITE NAO VERIFICA POR PUT
            $connection->addOption(CURLOPT_CUSTOMREQUEST, "PUT");
            $connection->addOption(CURLOPT_POSTFIELDS, $body);
        }

        $connection->setConfig(
            array(
            'timeout'   => 30
            )
        );

        $connection->write($zendMethod, $url, '1.0', $headers, $body);
        $response = $connection->read();
        $connection->close();

        $httpCode = Zend_Http_Response::extractCode($response);
        $response = Zend_Http_Response::extractBody($response);

        $response = json_decode($response, true);

        $response['httpCode'] = $httpCode;

        return $response;
    }

    public static function currentDate($format = null, $return = null)
    {
        if (empty($format)) {
            $format = 'Y-m-d H:i:s';
        }

        $timeZone = Mage::getStoreConfig('general/locale/timezone');
        $dateObj = new DateTime(null, new DateTimeZone($timeZone));

        if ($return == 'string') {
            $newFormat = $dateObj->format($format);
            return $newFormat;
        } else {
            return $dateObj;
        }
    }

}