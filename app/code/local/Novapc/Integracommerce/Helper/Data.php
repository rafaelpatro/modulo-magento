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
    public static function updateStock($product)
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        $exportType = Mage::getStoreConfig('integracommerce/general/export_type', Mage::app()->getStore());
        if ($exportType == 1) {
            if ($product->getData('integracommerce_sync') == 0) {
                return;
            }   
        }     

        if ($product->getData('integracommerce_active') == 0) {
            return;
        }

        $stockItem = Mage::getModel('cataloginventory/stock_item')
            ->loadByProduct($product->getId());

        $stockQuantity = (int) strstr($stockItem['qty'], '.', true);

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

    public static function newProduct($product,$_cats,$_attrs,$loadedAttrs, $environment)
    {
        if ($loadedAttrs['0'] !== 'not_selected') {
            $_nbmOrigin = $product->getResource()->getAttribute($loadedAttrs['0']);

            if (strpos($_nbmOrigin->getFrontend()->getValue($product), 'Estrangeira') !== false || strpos($_nbmOrigin->getFrontend()->getValue($product), 'Internacional') !== false) {
                $nbmOrigin = "1";
            } elseif (strpos($_nbmOrigin->getFrontend()->getValue($product), 'Nacional') !== false) {
                $nbmOrigin = "0";
            } elseif ($_nbmOrigin->getFrontend()->getValue($product)) {
                $nbmOrigin = "0";
            }
        }

        if (empty($nbmOrigin) || !$nbmOrigin || $nbmOrigin == null) {
            $nbmOrigin = $product->getData($loadedAttrs['0']);

            if (strpos($nbmOrigin, 'Estrangeira') !== false || strpos($nbmOrigin, 'Internacional') !== false) {
                $nbmOrigin = "1";
            } elseif (strpos($nbmOrigin, 'Nacional') !== false) {
                $nbmOrigin = "0";
            } else {
                $nbmOrigin = "0";
            }
        }       

        if (empty($loadedAttrs['3']) || !$loadedAttrs['3'] || $loadedAttrs['3'] == null || $loadedAttrs['3'] == 'not_selected') {
            $checkBrand = "";
        } else {
            $checkBrand = $product->getAttributeText($loadedAttrs['3']);

            if (empty($checkBrand) || !$checkBrand) {
                $checkBrand = $product->getData($loadedAttrs['3']);
            }

            if (empty($checkBrand) || $checkBrand == null) {
                $checkBrand = "";
            }
        }

        if (empty($loadedAttrs['1']) || !$loadedAttrs['1'] || $loadedAttrs['1'] == null || $loadedAttrs['1'] == 'not_selected') {
            $checkNbmNumber = "";
        } else {
            $checkNbmNumber = $product->getAttributeText($loadedAttrs['1']);

            if (empty($checkNbmNumber) || !$checkNbmNumber) {
                $checkNbmNumber = $product->getData($loadedAttrs['1']);
            }   

            if (empty($checkNbmNumber) || $checkNbmNumber == null) {
                $checkNbmNumber = "";
            }                     
        }    

        if (empty($loadedAttrs['2']) || !$loadedAttrs['2'] || $loadedAttrs['2'] == null || $loadedAttrs['2'] == 'not_selected') {
            $checkWarrantyTime = "0";
        } else {
            $checkWarrantyTime = $product->getAttributeText($loadedAttrs['2']);

            if (empty($checkWarrantyTime) || !$checkWarrantyTime) {
                $checkWarrantyTime = $product->getData($loadedAttrs['2']);
            }  

            if (empty($checkWarrantyTime) || $checkWarrantyTime == null) {
                $checkWarrantyTime = "0";
            }                       
        }  

        if ($nbmOrigin !== "0" || $nbmOrigin !== "1") {
            $nbmOrigin = "0";
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
            "Brand" => $checkBrand,
            "NbmOrigin" => $nbmOrigin,
            "NbmNumber" => $checkNbmNumber,
            "WarrantyTime" => $checkWarrantyTime,
            "Active" => true,
            "Categories" => $_cats,
            "Attributes" => $_attrs
        );  

        $jsonBody = json_encode($body);

        $url = 'https://' . $environment . '.integracommerce.com.br/api/Product';

        if ($product->getData('integracommerce_active') == 0) {
            $return = self::callCurl("POST", $url, $jsonBody);
        } elseif ($product->getData('integracommerce_active') == 1) {
            $return = self::callCurl("PUT", $url, $jsonBody);
        }

        if ($return['httpCode'] !== 204 && $return['httpCode'] !== 201) {
            return array($jsonBody, $return, $product->getId());
        }

        $productType = $product->getTypeId();
        if ($product->getData('integracommerce_active') == 0 && $productType == 'configurable') {
            Mage::getSingleton('catalog/product_action')->updateAttributes(
                array($product->getId()),
                array('integracommerce_active' => 1),
                0
            );
        }
    }

    public static function newSku($product, $pictures, $_attrs, $loadedAttrs, $productId, $environment, $configurableProduct = null)
    {
        $measure = Mage::getStoreConfig('integracommerce/general/measure', Mage::app()->getStore());

        $url = 'https://' . $environment . '.integracommerce.com.br/api/Sku';

        $heightValue = $product->getData($loadedAttrs['4']);
        $widthValue = $product->getData($loadedAttrs['5']);
        $lengthValue = $product->getData($loadedAttrs['6']);

        if (!empty($heightValue) && !empty($widthValue) && !empty($lengthValue)) {
            if ($measure && !empty($measure) && $measure == 1) {
                $heightValue = $heightValue / 100;
                $widthValue = $widthValue / 100;
                $lengthValue = $lengthValue / 100;
            } elseif ($measure && !empty($measure) && $measure == 3) {
                $heightValue = $heightValue / 1000;
                $widthValue = $widthValue / 1000;
                $lengthValue = $lengthValue / 1000;
            }
        }

        $stockItem = Mage::getModel('cataloginventory/stock_item')
               ->loadByProduct($product->getId());                          

        $normalPrice = $product->getPrice();
        $specialPrice = $product->getSpecialPrice();
        if (!$specialPrice || empty($specialPrice)) {
            $specialPrice = $normalPrice;
        }

        if (!$normalPrice || empty($normalPrice) || $normalPrice < 1) {
            if ($configurableProduct && !empty($configurableProduct)) {
                if ($configurableProduct->getId()) {
                    $normalPrice = $configurableProduct->getPrice();
                    $specialPrice = $configurableProduct->getSpecialPrice();
                    if (!$specialPrice || empty($specialPrice)) {
                        $specialPrice = $normalPrice;
                    }
                }
            }
        }

        $stockQuantity = (int) strstr($stockItem['qty'], '.', true);

        $weight = $product->getData($loadedAttrs['7']);
        $weightUnit = Mage::getStoreConfig('integracommerce/general/weight_unit', Mage::app()->getStore());

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

        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control', Mage::app()->getStore());

        if ($productControl == 'sku') {
            $idSku = $product->getData('sku');
        } else {
            $idSku = $product->getId();
        }

        $productStatus = $product->getStatus();
        if ($productStatus == 2) {
            $skuStatus = false;
        } else {
            $skuStatus = true;
        }

        $body = array(
            "idSku" => $idSku,
            "IdSkuErp" => $product->getData('sku'),
            "idProduct" => $productId,
            "Name" => $product->getName(),
            "Description" => $product->getData('description'),
            "Height" => $heightValue,
            "Width" => $widthValue,
            "Length" => $lengthValue,
            "Weight" => $weight,
            "CodeEan" => ($loadedAttrs['8'] == 'not_selected' ? "" : $product->getData($loadedAttrs['8'])),
            "CodeNcm" => ($loadedAttrs['9'] == 'not_selected' ? "" : $product->getData($loadedAttrs['9'])),
            "CodeIsbn" => ($loadedAttrs['10'] == 'not_selected' ? "" : $product->getData($loadedAttrs['10'])),
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

        if ($product->getData('integracommerce_active') == 0) {
            $return = self::callCurl("POST", $url, $jsonBody);
        } elseif ($product->getData('integracommerce_active') == 1) {
            $return = self::callCurl("PUT", $url, $jsonBody);
        }

        $productId = $product->getId();

        if ($return['httpCode'] !== 204 && $return['httpCode'] !== 201) {
            return array($jsonBody, $return, $product->getId());
        }

        if ($product->getData('integracommerce_active') == 0) {
            Mage::getSingleton('catalog/product_action')->updateAttributes(
                array($product->getId()),
                array('integracommerce_active' => 1),
                0
            );
        }
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
        if ($product->getData('integracommerce_active') == 0) {
            return;
        }

        if ($product->getTypeId() == 'simple') {
            $configurableIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
        }

        $configProd = Mage::getStoreConfig('integracommerce/general/configprod', Mage::app()->getStore());

        $normalPrice = $product->getPrice();
        $specialPrice = $product->getSpecialPrice();
        if (!$specialPrice || empty($specialPrice)) {
            $specialPrice = $normalPrice;
        }

        if (!$normalPrice || empty($normalPrice) || $normalPrice < 1) {
            if (!empty($configurableIds) && $configProd == 1) {
                foreach ($configurableIds as $configurableId) {
                    $configurableProduct = Mage::getModel('catalog/product')->load($configurableId);
                    $normalPrice = $configurableProduct->getPrice();
                    $specialPrice = $configurableProduct->getSpecialPrice();
                    if (!$specialPrice || empty($specialPrice)) {
                        $specialPrice = $normalPrice;
                    }
                }
            }
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

    public static function checkError($jsonBody = null, $response = null, $productId = null, $delete = null, $type = null)
    {
        $errorQueue = Mage::getModel('integracommerce/update')->load($productId, 'product_id');

        $errorProductId = $errorQueue->getProductId();

        if ($delete == 1 && !empty($errorProductId)) {
            $errorQueue->delete();
            return;
        }

        if (empty($errorProductId) && $delete !== 1) {
            $errorQueue = Mage::getModel('integracommerce/update');
            $errorQueue->setProductId($productId);
        }

        if (empty($response) && empty($jsonBody)) {
            return;
        }

        if (is_array($response)) {
            foreach ($response['Errors'] as $error) {
                $response = $error['Message'] . '. ';
            };
        }

        if ($type == 'product') {
            $errorQueue->setProductBody($jsonBody);
            $errorQueue->setProductError($response);
        } elseif ($type == 'sku') {
            $errorQueue->setSkuBody($jsonBody);
            $errorQueue->setSkuError($response);            
        } elseif ($type == 'price') {
            $errorQueue->setPriceBody($jsonBody);
            $errorQueue->setPriceError($response);
        } elseif ($type == 'stock') {
            $errorQueue->setStockBody($jsonBody);
            $errorQueue->setStockError($response);
        }

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

        $connection->write($zendMethod, $url, '1.0', $headers, $body);
        $response = $connection->read();
        $connection->close();

        $httpCode = Zend_Http_Response::extractCode($response);
        $response = Zend_Http_Response::extractBody($response);

        $response = json_decode($response, true);

        $response['httpCode'] = $httpCode;

        return $response;
    }

}