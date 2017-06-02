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

class Novapc_Integracommerce_Helper_OrderData extends Novapc_Integracommerce_Helper_Data
{
    public static function startOrders($requested, $orderModel)
    {
        $requestedHour = $orderModel->getRequestedHour();
        $requestedDay = $orderModel->getRequestedDay();
        $requestedWeek = $orderModel->getRequestedWeek();

        $requestedHour = $requestedHour + $requested['Total'];
        $requestedDay = $requestedDay + $requested['Total'];
        $requestedWeek = $requestedWeek + $requested['Total'];

        $orderModel->setStatus(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        $orderModel->setRequestedHour($requestedHour);
        $orderModel->setRequestedDay($requestedDay);
        $orderModel->setRequestedWeek($requestedWeek);
        $orderModel->save();

        foreach ($requested['Orders'] as $order) {
            self::processingOrder($order);
        }

        return;
    }

    public static function processingOrder($order)
    {
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
            self::updateCustomer($customer,$order);
        } else {
            $customerId = self::createCustomer($order);
        }

        //VERIFIFA SE JA EXISTE PEDIDO NO MAGENTO COM O ID DA COMPRA INTEGRACOMMERCE
        $existingOrder = Mage::getModel('sales/order')->load($order['IdOrder'], 'integracommerce_id');

        $incrementId = $existingOrder->getIncrementId();
        if ($incrementId && !empty($incrementId)) {
            return;
        } else {
            $integraModel = self::integraOrder($order,$customerId, null);
            self::createOrder($order,$customerId, $integraModel);
        }

        return;
    }

	public static function createCustomer($order)
	{	
		$ieAttribute = Mage::getStoreConfig('integracommerce/attributes/ierg', Mage::app()->getStore());
        $customer = Mage::getModel("customer/customer");
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->setStore(Mage::app()->getStore());
     
        $customer->setFirstname((empty($order['CustomerPfName']) ? $order['CustomerPjCorporatename'] : $order['CustomerPfName']));
        $customer->setLastname('.');
        $customer->setData('taxvat',(empty($order['CustomerPfCpf']) ? $order['CustomerPjCnpj'] : $order['CustomerPfCpf']));

        if (!empty($order['CustomerPjIe']) && $ieAttribute !== 'not_selected') {
        	$customer->setData($ieAttribute,$customer['CustomerPjIe']);
        }

        $new_email = $order['MarketplaceName'] . '_' . mt_rand() . '@email.com.br';
        $customer->setEmail($new_email);

        try {
            $customer->save();
        } catch (Exception $e){ 
                Mage::log('Erro ao criar o cliente. Mensagem: '.$e->getMessage(), null, 'customer_save_error_integracommerce.log');
        }        

        //VERIFICA SE TEM DADOS PARA CADASTRAR ENDEREÇO  
        $region = Mage::getModel('directory/region')->loadByCode($order['DeliveryAddressState'],'BR');

        $address = Mage::getModel("customer/address");
        $address->setCustomerId($customer->getId())
            ->setFirstname((empty($order['CustomerPfName']) ? $order['CustomerPjCorporatename'] : $order['CustomerPfName']))
            ->setLastname('.')           
            ->setCountryId('BR')
            ->setPostcode($order['DeliveryAddressZipcode'])
            ->setCity($order['DeliveryAddressCity'])
            ->setRegion($region->getName())
            ->setRegionId($region->getId())
            ->setTelephone($order['TelephoneMainNumber'])
            ->setStreet(array($order['DeliveryAddressStreet'], $order['DeliveryAddressNumber'], (empty($order['DeliveryAddressReference']) ? 'Não Informado' : $order['DeliveryAddressReference']) , $order['DeliveryAddressNeighborhood']))
            ->setIsDefaultBilling('1')
            ->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1');

        try {
            $address->save(); 
        } catch (Exception $e){
            $message = $e->getMessage();
            Mage::log('Erro ao cadastrar endereço. Mensagem: '. $message, null, 'customer_save_error_integracommerce.log');
        }

        $customerId = $customer->getId();
        return $customerId;

	}
    
	public static function updateCustomer($customer,$order)
	{
		$defaultShippingId = $customer->getDefaultShipping();
        $address = Mage::getModel("customer/address");
        $address->load($defaultShippingId);

        $region = Mage::getModel('directory/region')->loadByCode($order['DeliveryAddressState'],'BR');

        $address->setCustomerId($customer->getId())
            ->setFirstname((empty($order['CustomerPfName']) ? $order['CustomerPjCorporatename'] : $order['CustomerPfName']))
            ->setLastname('.')           
            ->setCountryId('BR')
            ->setPostcode($order['DeliveryAddressZipcode'])
            ->setCity($order['DeliveryAddressCity'])
            ->setRegion($region->getName())
            ->setRegionId($region->getId())
            ->setTelephone($order['TelephoneMainNumber'])
            ->setStreet(array($order['DeliveryAddressStreet'], $order['DeliveryAddressNumber'], (empty($order['DeliveryAddressReference']) ? 'Não Informado' : $order['DeliveryAddressReference']), $order['DeliveryAddressNeighborhood']))
            ->setIsDefaultBilling('1')
            ->setIsDefaultShipping('1')
            ->setSaveInAddressBook('1');

        try {
            $address->save(); 
        } catch (Exception $e){ 
            Mage::log('Erro ao cadastrar endereço. Mensagem: '.$e->getMessage(), null, 'customer_save_error_integracommerce.log');
        }        

        $customerId = $customer->getId();
        return $customerId;

	}

	public static function createOrder($order,$customerId, $integraModel = null)
	{	
		$customer = Mage::getModel('customer/customer')->load($customerId);	
		//INICIA O MODEL DE PEDIDO DO MAGENTO                    
		$transaction = Mage::getModel('core/resource_transaction');
		$storeId = $customer->getStoreId();
		if ($storeId == 0) {
			$storeId = 1;
		}
		//PEGA DO BANCO QUAL VAI SER O PROXIMO ID DE PEDIDO
		$reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);

		$mage_order = Mage::getModel('sales/order')
			->setIncrementId($reservedOrderId)
			->setStoreId($storeId)
			->setQuoteId(0)
			->setDiscountAmount(0)
			->setShippingAmount(0)
			->setShippingTaxAmount(0)
			->setBaseDiscountAmount(0)
			->setIsVirtual(0)
			->setBaseShippingAmount(0)
			->setBaseShippingTaxAmount(0)
			->setBaseTaxAmount(0)
			->setBaseToGlobalRate(1)
			->setBaseToOrderRate(1)
			->setStoreToBaseRate(1)
			->setStoreToOrderRate(1)
			->setTaxAmount(0)
			->setGlobal_currency_code(Mage::app()->getBaseCurrencyCode())
			->setBase_currency_code(Mage::app()->getBaseCurrencyCode())
			->setStore_currency_code(Mage::app()->getBaseCurrencyCode())
			->setOrder_currency_code(Mage::app()->getBaseCurrencyCode());

		$mage_order->setCustomer_email($customer->getEmail())
			->setCustomerFirstname($customer->getFirstname())
			->setCustomerLastname($customer->getLastname())
			->setCustomerGroupId($customer->getGroupId())
			->setCustomer_is_guest(0)
			->setCustomer($customer);

		$billing = $customer->getDefaultBillingAddress();
		$billingAddress = Mage::getModel('sales/order_address')
				->setStoreId($storeId)
				->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
				->setCustomerId($customer->getId())
				->setCustomerAddressId($customer->getDefaultBilling())
				->setCustomer_address_id($billing->getEntityId())
				->setPrefix($billing->getPrefix())
				->setFirstname($billing->getFirstname())						
				->setLastname($billing->getLastname())
				->setStreet($billing->getStreet())
				->setCity($billing->getCity())
				->setCountry_id($billing->getCountryId())
				->setRegion($billing->getRegion())
				->setRegion_id($billing->getRegionId())
				->setPostcode($billing->getPostcode())
				->setTelephone($billing->getTelephone());
		$mage_order->setBillingAddress($billingAddress);
				 
		$shipping = $customer->getDefaultShippingAddress();
		$shippingAddress = Mage::getModel('sales/order_address')
			->setStoreId($storeId)
			->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
			->setCustomerId($customer->getId())
			->setCustomerAddressId($customer->getDefaultShipping())
			->setCustomer_address_id($shipping->getEntityId())
			->setPrefix($shipping->getPrefix())
			->setFirstname($shipping->getFirstname())
			->setLastname($shipping->getLastname())
			->setStreet($shipping->getStreet())
			->setCity($shipping->getCity())
			->setCountry_id($shipping->getCountryId())
			->setRegion($shipping->getRegion())
			->setRegion_id($shipping->getRegionId())
			->setPostcode($shipping->getPostcode())
			->setTelephone($shipping->getTelephone());

		//INSERINDO METODO DE ENTREGA
		if (empty($order['TotalFreight'])) {
			$shippingprice = 0;
		} else {
			$shippingprice = $order['TotalFreight'];
		}							

		$mage_order->setShippingAddress($shippingAddress)
			->setShipping_method('flatrate_flatrate')
			->setShippingDescription('Loja: '. $order['StoreName'] .', Marketplace: '. $order['MarketplaceName'] .', Frete: ' . $order['ShippedCarrierName'])
			->setShippingAmount($shippingprice)
            ->setBaseShippingAmount($shippingprice);

		$orderPayment = Mage::getModel('sales/order_payment')
			->setStoreId($storeId)
			->setCustomerPaymentId(0)
			->setMethod('cashondelivery')
			->setPo_number(' – ');
		$mage_order->setPayment($orderPayment);

        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control',Mage::app()->getStore());
		$subTotal = 0;
	    foreach ($order['Products'] as $key => $product) {
            if ($productControl == 2) {
                $_product = Mage::getModel('catalog/product')->loadByAttribute('sku',$product['IdSku']);
            } else {
                $_product = Mage::getModel('catalog/product')->loadByAttribute('entity_id',$product['IdSku']);
            }

            $productId = $_product->getId();
	        if (!$productId || empty($productId)) {
	        	continue;
	        }

	        $new_price = $product['Price'];
			$rowTotal = $new_price * $product['Quantity'];
			$orderItem = Mage::getModel('sales/order_item')
				->setStoreId($storeId)
				->setQuoteItemId(0)
				->setQuoteParentItemId(NULL)
				->setProductId($_product->getId())
				->setProductType($_product->getTypeId())
				->setQtyBackordered(NULL)
				->setTotalQtyOrdered($product['Quantity'])
				->setQtyOrdered($product['Quantity'])
				->setName($_product->getName())
				->setSku($_product->getSku())
				->setPrice($new_price)
				->setBasePrice($new_price)
				->setOriginalPrice($new_price)
				->setRowTotal($rowTotal)
				->setBaseRowTotal($rowTotal);
				
				$subTotal += $rowTotal;
				$mage_order->addItem($orderItem);
			
		} 

		$mage_order->setSubtotal($subTotal)
			->setBaseSubtotal($subTotal)
			->setGrandTotal($subTotal + $shippingprice)
			->setBaseGrandTotal($subTotal);	

		$mage_order->setData('integracommerce_id', $order['IdOrder']);	

		$comment = $mage_order->addStatusHistoryComment("Código do Pedido Integracommerce: " . $order['IdOrder'] . "<br>" . "Código do Pedido Marketplace: " . $order['IdOrderMarketplace'], false);
		$comment->setIsCustomerNotified(false);

		try {
			$transaction->addObject($mage_order);
			$transaction->addCommitCallback(array($mage_order, 'place'));
			$transaction->addCommitCallback(array($mage_order, 'save'));
						
			$newOrder2 = Mage::getModel('sales/order')->load($order['IdOrder'], 'integracommerce_id');

			$newOrderIncrement = $newOrder2->getIncrementId();
			if ($newOrderIncrement && !empty($newOrderIncrement)) {
				Mage::throwException('Pedido '. $order['MarketplaceName'] .' já cadastrado. Nº ' . $order['IdOrder']);
			}

			$transaction->save();

            $mageOrderId = $mage_order->getEntityId();
		} catch (Exception $e){
            $integraModel->setMageError($e->getMessage());
            $integraModel->save();
			Mage::log($e->getTraceAsString());
		}

		$updateStatusOrder = Mage::getModel('sales/order')->load($order['IdOrder'], 'integracommerce_id');
        $updateIncrementId = $updateStatusOrder->getIncrementId();
		if ($updateIncrementId && !empty($updateIncrementId)) {
            self::updateIntegraOrder($order['IdOrder'], $mageOrderId);

            $status = Mage::getStoreConfig('integracommerce/order_status/approved',Mage::app()->getStore());
            if ($status !== 'keepstatus') {
                $states = array();
                $stateCollection = Mage::getResourceModel('sales/order_status_collection')->addStatusFilter($status);
                foreach ($stateCollection as $state) {
                    $states[] = $state->getState();
                }
                $updateStatusOrder->setData('state', $states[0]);
                $updateStatusOrder->setStatus($status);

                $history = $updateStatusOrder->addStatusHistoryComment("Status no Integracommerce: Aprovado", false);
                $history->setIsCustomerNotified(false);

                try {
                    $updateStatusOrder->save();
                } catch (Exception $e) {
                    $integraModel->setMageError($e->getMessage());
                    $integraModel->save();
                    Mage::log($e->getTraceAsString());
                }
            }
        }

        $entityId = $mage_order->getEntityId();
        $integraModel->setMagentoOrderId($entityId);
        $integraModel->setMagentoCustomerId($customer->getId());
        $integraModel->setCustomerEmail($customer->getEmail());
        $integraModel->save();

		return $entityId;
	}	

	public static function updateIntegraOrder($orderId, $mageOrderId)
	{
        $api_user = Mage::getStoreConfig('integracommerce/general/api_user',Mage::app()->getStore());
        $api_password = Mage::getStoreConfig('integracommerce/general/api_password',Mage::app()->getStore());
        $authentication = base64_encode($api_user . ':' . $api_password);
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());

        if ($environment == 1) {
         	$post_url = 'https://api.integracommerce.com.br/api/Order';
        } else {
        	$post_url = 'https://in.integracommerce.com.br/api/Order';
        } 

       	$body = array(
        	"IdOrder" => $orderId,
        	"OrderStatus" => 'PROCESSING' 	
        );

		$jsonBody = json_encode($body); 

        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $post_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json", "Authorization: Basic " . $authentication . ""));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $jsonBody);

        $_curl_exe = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ($ch);

        $decoded = json_decode($_curl_exe, true); 

        if ($httpcode !== 204) {
        	if (!empty($decoded['Errors'])) {
            	foreach ($decoded['Errors'] as $error) {
                	$error_message = $error['Message'] . ', ';
            	}
                Mage::log('Error: ' . $httpcode . 'Erro ao atualizar o pedido ' . $mageOrderId . ', Codigo Integracommerce: ' . $orderId . '. Motivo: ' . $decoded['Message'] . '. Erros: ' . $error_message, null, 'integracommerce_order_update_error.log');
        	}

            $requestLog = Mage::getStoreConfig('integracommerce/general/request_log', Mage::app()->getStore());
            if ($requestLog == 1) {
                Mage::log('Requisição: ' . $jsonBody, null, 'integracommerce_order_request.log');
            }
        }        

        return;

	}

	public static function integraOrder($order,$customerId,$mageOrder = null)
	{
        $customer = Mage::getModel('customer/customer')->load($customerId);
		$integraOrder = Mage::getModel('integracommerce/order')->load($order['IdOrder'], 'integra_id');

		$integraId = $integraOrder->getIntegraId();
		if (!$integraId && empty($integraId)) {
            $integraOrder = Mage::getModel('integracommerce/order');
            $integraOrder->setIntegraId($order['IdOrder']);
        }

		$integraOrder->setMarketplaceId($order['IdOrderMarketplace']);
		$integraOrder->setMarketplaceName($order['MarketplaceName']);
		$integraOrder->setStoreName($order['StoreName']);
		$integraOrder->setUpdatedMarketplaceStatus($order['UpdatedMarketplaceStatus']);
		$integraOrder->setEstimatedDeliveryDate($order['EstimatedDeliveryDate']);
		$integraOrder->setCustomerPfCpf($order['CustomerPfCpf']);
		$integraOrder->setCustomerPfName($order['CustomerPfName']);
		$integraOrder->setCustomerPjCnpj($order['CustomerPjCnpj']);
		$integraOrder->setCustomerPjCorporateName($order['CustomerPjCorporatename']);
		$integraOrder->setDeliveryStreet($order['DeliveryAddressStreet']);
		$integraOrder->setDeliveryAdditionalInfo($order['DeliveryAddressAdditionalInfo']);
		$integraOrder->setDeliveryNeighborhood($order['DeliveryAddressNeighborhood']);
		$integraOrder->setDeliveryCity($order['DeliveryAddressCity']);
		$integraOrder->setDeliveryReference($order['DeliveryAddressReference']);
		$integraOrder->setDeliveryState($order['DeliveryAddressState']);
		$integraOrder->setDeliveryNumber($order['DeliveryAddressNumber']);
		$integraOrder->setTelephoneMain($order['TelephoneMainNumber']);
		$integraOrder->setTelephoneSecondary($order['TelephoneSecondaryNumber']);
		$integraOrder->setTelephoneBusiness($order['TelephoneBusinessNumber']);
		$integraOrder->setTotalAmount($order['TotalAmount']);
		$integraOrder->setTotalFreight($order['TotalFreight']);
		$integraOrder->setTotalDiscount($order['TotalDiscount']);
		$integraOrder->setCustomerBirthday($order['CustomerBirthDate']);
		$integraOrder->setOrderStatus($order['OrderStatus']);
		$integraOrder->setInvoicedNumber($order['InvoicedNumber']);
		$integraOrder->setInvoicedLine($order['InvoicedLine']);
		$integraOrder->setInvoicedKey($order['InvoicedKey']);
		$integraOrder->setInvoicedDanfeXml($order['InvoicedDanfeXml']);
		$integraOrder->setShippingTrackingUrl($order['ShippedTrackingUrl']);
		$integraOrder->setShippingTrackingProtocol($order['ShippedTrackingProtocol']);
		$integraOrder->setShippedEstimatedDelivery($order['ShippedEstimatedDelivery']);
		$integraOrder->setShippedCarrierAt($order['ShippedCarrierDate']);
		$integraOrder->setShippedCarrierName($order['ShippedCarrierName']);
		$integraOrder->setShipmentExceptionObservation($order['ShipmentExceptionObservation']);
		$integraOrder->setShipmentExceptionOccurrenceAt($order['ShipmentExceptionOccurrenceDate']);
		$integraOrder->setDeliveredAt($order['DeliveredDate']);
		$integraOrder->setProductsSkus($order['Products']);

		if (isset($mageOrder)) {
            $integraOrder->setMagentoOrderId($mageOrder);
            $integraOrder->setMagentoCustomerId($customer->getId());
            $integraOrder->setCustomerEmail($customer->getEmail());
        }

		$integraOrder->setInsertedAt($order['InsertedDate']);
		$integraOrder->setPurchasedAt($order['PurchasedDate']);
		$integraOrder->setApprovedAt($order['ApprovedDate']);
		$integraOrder->setUpdatedAt($order['UpdatedDate']);

        try {
            $integraOrder->save();
        } catch (Exception $e) {
            Mage::log($e->getMessage(), null, 'integra_order_save_error_integracommerce.log');
        }		

        return $integraOrder;
	}

    public static function updateOrder($order)
    {
        $api_user = Mage::getStoreConfig('integracommerce/general/api_user',Mage::app()->getStore());
        $api_password = Mage::getStoreConfig('integracommerce/general/api_password',Mage::app()->getStore());
        $authentication = base64_encode($api_user . ':' . $api_password); 
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());
        $invoiceStatus = Mage::getStoreConfig('integracommerce/order_status/nota_fiscal',Mage::app()->getStore());
        $ShippingStatus = Mage::getStoreConfig('integracommerce/order_status/dados_rastreio',Mage::app()->getStore());
        $integraModel = Mage::getModel('integracommerce/order')->load($order->getData('integracommerce_id'), 'integra_id');

        if ($environment == 1) {
        	$post_url = 'https://api.integracommerce.com.br/api/Order';
        } else {
        	$post_url = 'https://in.integracommerce.com.br/api/Order';
        }

        $status = $order->getStatus();

        if ( ( ( $invoiceStatus && !empty($invoiceStatus) ) && $invoiceStatus == $status ) || $status == 'processing') {

            $comment = $order->getStatusHistoryCollection(true)->getFirstItem();

            $commentData = $comment->getData('comment');
            if (empty($commentData)) {
            	return;
            }

            $lines = explode('|', $comment->getData('comment'));

            if (empty($lines)) {
            	return;
            }

            $new_status = "INVOICED";
            $line = array();
            foreach ($lines as $_line) {
                $line[] = $_line;
                
            }   

            if (!empty($line[2])) {
            	$ymd = DateTime::createFromFormat('d/m/Y', $line[2]);
            	if ($ymd) {
            		$line[2] = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
            	} else {
            		$ymd = DateTime::createFromFormat('d/m/Y H:i:s', $line[2]);
            		if ($ymd) {
                        $line[2] = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
                    } else {
                       $integraModel->setMageError('Motivo: Data inválida. Erros: a data deve seguir o padrão brasileiro');
                       $integraModel->save();
                       return;
                    }
            	}   
            }     

            if (strlen($line[3]) < 44) {
            	$line[3] = str_pad($line[3],44,"0");
            }

            if (empty($line[4]) || !$line[4]) {
            	$line[4] = "";
            }

            $body = array(
                "IdOrder" => $order->getData('integracommerce_id'),
                "OrderStatus" => "INVOICED",
                "InvoicedNumber" => (empty($line[0]) ? "" : $line[0]),
                "InvoicedLine" => (!isset($line[1]) ? "" : $line[1]),
                "InvoicedIssueDate" => (empty($line[2]) ? "" : $line[2]),
                "InvoicedKey" => (empty($line[3]) ? "" : $line[3]),
                "InvoicedDanfeXml" => (empty($line[4]) ? "" : $line[4])
            );
        } elseif ( ( ( $ShippingStatus && !empty($ShippingStatus) ) && $ShippingStatus == $status ) || $status == 'complete') {
 
            $comment = $order->getStatusHistoryCollection(true)->getFirstItem();

            $commentData = $comment->getData('comment');
            if (empty($commentData)) {
                return;
            }

            $lines = explode('|', $comment->getData('comment'));

            if (empty($lines)) {
            	return;
            }

            $new_status = "SHIPPED";
            $line1 = array();
            foreach ($lines as $_line) {
                $line1[] = $_line;
                
            }

            if (!empty($line1[2])) {
            	$ymd = DateTime::createFromFormat('d/m/Y', $line1[2]);
            	if ($ymd) {
            		$line1[2] = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
            	} else {
            		$ymd = DateTime::createFromFormat('d/m/Y H:i:s', $line1[2]);
            		if ($ymd) {
                        $line1[2] = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
                    } else {
                        $integraModel->setMageError('Motivo: Data inválida. Erros: a data deve seguir o padrão brasileiro');
                        $integraModel->save();
                        return;
                    }
            	}
            }

            if (!empty($line1[3])) {
            	$ymd = DateTime::createFromFormat('d/m/Y', $line1[3]);
            	if ($ymd) {
            		$line1[3] = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
            	} else {
            		$ymd = DateTime::createFromFormat('d/m/Y H:i:s', $line1[3]);
            		if ($ymd) {
                        $line1[3] = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
                    } else {
                        $integraModel->setMageError('Motivo: Data inválida. Erros: a data deve seguir o padrão brasileiro');
                        $integraModel->save();
                        return;
                    }
            	}
            }                        

            $body = array(
                "IdOrder" => $order->getData('integracommerce_id'),
                "OrderStatus" =>"SHIPPED",
                "ShippedTrackingUrl" => (empty($line1[0]) ? "" : $line1[0]),
                "ShippedTrackingProtocol" => (empty($line1[1]) ? "" : $line1[1]),
                "ShippedEstimatedDelivery" => (empty($line1[2]) ? "" : $line1[2]),
                "ShippedCarrierDate" => (empty($line1[3]) ? "" : $line1[3]),
                "ShippedCarrierName" => (empty($line1[4]) ? "" : $line1[4])
            );
        } elseif ($status == 'delivered') {
        	$comment = $order->getStatusHistoryCollection(true)->getFirstItem();

            $commentData = $comment->getData('comment');
            if (empty($commentData)) {
                return;
            }

        	$ymd = DateTime::createFromFormat('d/m/Y', $comment->getData('comment'));
            if ($ymd) {
                $delivered_date = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
            } else {
                $ymd = DateTime::createFromFormat('d/m/Y H:i:s', $comment->getData('comment'));
                if ($ymd) {
                    $delivered_date = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
                } else {
                    $integraModel->setMageError('Motivo: Data inválida. Erros: a data deve seguir o padrão brasileiro');
                    $integraModel->save();
                    return;
                }
            }

        	$new_status = "DELIVERED";

        	$body = array(
        	    "IdOrder" => $order->getData('integracommerce_id'),
                "OrderStatus" => "DELIVERED",
                "DeliveredDate" => $delivered_date
            );
        } elseif ($status == 'shipexception') {
            $comment = $order->getStatusHistoryCollection(true)->getFirstItem();
            $lines = explode('|', $comment->getData('comment'));

            if (empty($lines)) {
            	return;
            }

            $new_status = "SHIPMENT_EXCEPTION";
            $line2 = array();
            foreach ($lines as $_line) {
                $line2[] = $_line;
                
            }

            if (!empty($line2[1])) {
            	$ymd = DateTime::createFromFormat('d/m/Y', $line2[1]);
            	if ($ymd) {
            		$line2[1] = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
            	} else {
            		$ymd = DateTime::createFromFormat('d/m/Y H:i:s', $line2[1]);
            		if ($ymd) {
                        $line2[1] = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
                    } else {
                        $integraModel->setMageError('Motivo: Data inválida. Erros: a data deve seguir o padrão brasileiro');
                        $integraModel->save();
                        return;
                    }
            	}    
            }

            $body = array(
                "IdOrder" => $order->getData('integracommerce_id'),
                "OrderStatus" => "SHIPMENT_EXCEPTION",
                "ShipmentExceptionObservation" => (empty($line2[0]) ? "" : $line2[0]),
                "ShipmentExceptionOccurrenceDate" => (empty($line2[1]) ? "" : $line2[1])
            );
        }

        if (isset($body)) {
            $jsonBody = json_encode($body);

            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL, $post_url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json", "Authorization: Basic " . $authentication . ""));
            curl_setopt($ch,CURLOPT_POSTFIELDS, $jsonBody);

            $_curl_exe = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close ($ch);

            $decoded = json_decode($_curl_exe, true);

            if ($httpcode !== 204) {
                if (!empty($decoded['Errors'])) {
                    foreach ($decoded['Errors'] as $error) {
                        $error_message = $error['Message'] . ', ';
                    }

                    $integraModel->setIntegraError('Motivo: ' . $decoded['Message'] . '. Erros: ' . $error_message);
                    $integraModel->save();
                }
            }
        }

        return;
    } 		

	public static function viewOrder($id)
	{
		$integraOrder = Mage::getModel('integracommerce/order')->load($id,'integra_id');
		$mageCustomer = Mage::getModel('customer/customer')->load($integraOrder->getMagentoCustomerId());
		$mageOrder = Mage::getModel('sales/order')->load($integraOrder->getMagentoOrderId());

		return array($integraOrder,$mageCustomer,$mageOrder);
	}	

}