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
 
class Novapc_Integracommerce_Helper_Data extends Mage_Core_Helper_Abstract
{

    public static function createQueue($identificator,$type,$jsonBody,$httpcode)
    {
        if ($httpcode == 204 || $httpcode == 201) {
            $insertQueue = Mage::getModel('integracommerce/queue')
                            ->getCollection()
                            ->addFieldToFilter('identificator',$identificator . $type)
                            ->getFirstItem(); 

            if ($insertQueue->getData('identificator')) {
                $insertQueue->setSentJson($jsonBody);
                $insertQueue->setLastUpdate(date("Y-m-d h:i:sa"));
                $insertQueue->setType($type);
                $insertQueue->setDone(1);
                $insertQueue->save();                
            } else {
                $insertQueue = Mage::getModel('integracommerce/queue');
                $insertQueue->setIdentificator($identificator . $type);
                $insertQueue->setSentJson($jsonBody);
                $insertQueue->setLastUpdate(date("Y-m-d h:i:sa"));
                $insertQueue->setType($type);
                $insertQueue->setDone(1);
                $insertQueue->save();                 
            }
            return;
        } else {
            $insertQueue = Mage::getModel('integracommerce/queue')
                            ->getCollection()
                            ->addFieldToFilter('identificator',$identificator . $type)
                            ->getFirstItem(); 

            if ($insertQueue->getData('identificator')) {
                $insertQueue->setSentJson($jsonBody);
                $insertQueue->setCreatedAt(date("Y-m-d h:i:sa"));
                $insertQueue->setType($type);
                $insertQueue->setDone(0);
                $insertQueue->save();
            } else {
                $insertQueue = Mage::getModel('integracommerce/queue');
                $insertQueue->setIdentificator($identificator . $type);
                $insertQueue->setSentJson($jsonBody);
                $insertQueue->setCreatedAt(date("Y-m-d h:i:sa"));
                $insertQueue->setType($type);
                $insertQueue->setDone(0);
                $insertQueue->save();
            }
            return;
        }
    }

    public static function updateStock($product,$authentication,$environment)
    {   
        $exportType = Mage::getStoreConfig('integracommerce/general/export_type',Mage::app()->getStore());
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

        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control',Mage::app()->getStore());
        if ($productControl == 2) {
            $idSku = $product->getData('sku');
        } else {
            $idSku = $product->getId();
        }

        $body = array();
        array_push($body,
            array(
                'IdSku' => $idSku,
                'Quantity' => $stockQuantity
            )
        );

        $jsonBody = json_encode($body);
        if ($environment == 1) {
            $stock_url = 'https://api.integracommerce.com.br/api/Stock';
        } else {
            $stock_url = 'https://in.integracommerce.com.br/api/Stock';
        }

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $stock_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json", "Authorization: Basic " . $authentication . ""));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $jsonBody);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        if ($httpcode !== 204 && $httpcode !== 201) {
            return array($jsonBody, $response, $product->getId());
        }

        return;

    }

    public static function newProduct($product,$_cats,$_attrs,$loadedAttrs, $authentication, $environment)
    {
        if ($environment == 1) {
            $post_url = 'https://api.integracommerce.com.br/api/Product';
        } else {
            $post_url = 'https://in.integracommerce.com.br/api/Product';
        } 

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

        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control',Mage::app()->getStore());

        if ($productControl == 2) {
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

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $post_url);
        if ($product->getData('integracommerce_active') == 0) {
            curl_setopt($ch, CURLOPT_POST, true);   
        } elseif ($product->getData('integracommerce_active') == 1) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json", "Authorization: Basic " . $authentication . ""));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $jsonBody);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        $productId = $product->getId();

        if ($httpcode !== 204 && $httpcode !== 201) {
            return array($jsonBody, $response, $product->getId());
        }
        
        return;

    }

    public static function newSku($product,$configurableProduct = null,$pictures,$_attrs,$loadedAttrs,$productId,$authentication,$environment)
    {
        $measure = Mage::getStoreConfig('integracommerce/general/measure',Mage::app()->getStore());

        if ($environment == 1) {
            $post_url = 'https://api.integracommerce.com.br/api/Sku';
        } else {
            $post_url = 'https://in.integracommerce.com.br/api/Sku';
        }

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
            if ($configurableProduct && !empty($configurableProduct)){
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

        if (strstr($weight,".") !== false) {
            $weight = (float) $product->getData($loadedAttrs['7']);
        } else {
            $weight = (int) $product->getData($loadedAttrs['7']);
        }

        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control',Mage::app()->getStore());

        if ($productControl == 2) {
            $idSku = $product->getData('sku');
        } else {
            $idSku = $product->getId();
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
            "Status" => true,
            "Price" => array(
                "ListPrice" => ($normalPrice < $specialPrice ? $specialPrice : $normalPrice),
                "SalePrice" => $specialPrice
            ),  
            "UrlImages" => $pictures,  
            "Attributes" => $_attrs
        );  

        $jsonBody = json_encode($body);

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $post_url);
        if ($product->getData('integracommerce_active') == 0) {
            curl_setopt($ch, CURLOPT_POST, true);   
        } elseif ($product->getData('integracommerce_active') == 1) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json", "Authorization: Basic " . $authentication . ""));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $jsonBody);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        $productId = $product->getId();

        if ($httpcode !== 204 && $httpcode !== 201) {
            return array($jsonBody, $response, $product->getId());
        }

        Mage::getSingleton('catalog/product_action')->updateAttributes(
            array($product->getId()),               // Product IDs to update
            array('integracommerce_active' => 1), // Key/value pairs of attributes and their values
            0                                   // Store ID
        );

        return;
    } 

    public static function getOrders()
    {
        $api_user = Mage::getStoreConfig('integracommerce/general/api_user',Mage::app()->getStore());
        $api_password = Mage::getStoreConfig('integracommerce/general/api_password',Mage::app()->getStore());
        $authentication = base64_encode($api_user . ':' . $api_password);         
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());

        if ($environment == 1) {
            $geturl = "https://api.integracommerce.com.br/api/Order?page=1&perPage=10&status=approved";
        } else {
            $geturl = "https://in.integracommerce.com.br/api/Order?page=1&perPage=10&status=approved";
        }

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$geturl);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Basic " . $authentication . ""));

        $response = curl_exec($ch);
        curl_close ($ch);

        $responseArray = json_decode($response, true);

        return $responseArray['Orders'];
    } 

    public static function updatePrice($product,$authentication,$environment)
    {
        if ($product->getData('integracommerce_active') == 0) {
            return;
        }

        if ($product->getTypeId() == 'simple') {
            $configurableIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
        }

        $configProd = Mage::getStoreConfig('integracommerce/general/configprod',Mage::app()->getStore());

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

        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control',Mage::app()->getStore());
        if ($productControl == 2) {
            $idSku = $product->getData('sku');
        } else {
            $idSku = $product->getId();
        }

        $body = array();
        array_push($body,
            array(
                'IdSku' => $idSku,
                'ListPrice' => ($normalPrice < $specialPrice ? $specialPrice : $normalPrice),
                'SalePrice' => $specialPrice
            )
        );

        $jsonBody = json_encode($body);

        if ($environment == 1) {
            $price_url = 'https://api.integracommerce.com.br/api/Price';
        } else {
            $price_url = 'https://in.integracommerce.com.br/api/Price';
        }

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $price_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json", "Authorization: Basic " . $authentication . ""));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $jsonBody);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        if ($httpcode !== 204 && $httpcode !== 201) {
            return array($jsonBody, $response, $product->getId());
        }

        return;
    }

    public static function postCategory($body)
    {
                  
        $jsonBody = json_encode($body);

        $api_user = Mage::getStoreConfig('integracommerce/general/api_user',Mage::app()->getStore());
        $api_password = Mage::getStoreConfig('integracommerce/general/api_password',Mage::app()->getStore());
        $authentication = base64_encode($api_user . ':' . $api_password);
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());

        if ($environment == 1) {
            $category_url = 'https://api.integracommerce.com.br/api/Category';
        } else {
            $category_url = 'https://in.integracommerce.com.br/api/Category';
        }

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $category_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json", "Authorization: Basic " . $authentication . ""));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $jsonBody);
        $_curl_exe = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch); 

        $decoded = json_decode($_curl_exe, true);

        if ($httpcode !== 204 && $httpcode !== 201) {
            if (!empty($decoded['Errors'])) {
                foreach ($decoded['Errors'] as $error) {
                    $error_message = $error['Message'] . ', ';
                }
            }
        }        

        return;

    }

    public static function checkError($jsonBody = null, $response = null, $productId = null, $delete = null, $type = null) {
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

        return;
    }

}