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

class Novapc_Integracommerce_Helper_OrderData extends Novapc_Integracommerce_Helper_Data
{
    public static function startOrders($requested, $orderModel)
    {
        /*CARREGANDO A QUANTIDADE DE REQUISICOES POR TEMPO*/
        $requestedHour = $orderModel->getRequestedHour();
        $requestedDay = $orderModel->getRequestedDay();
        $requestedWeek = $orderModel->getRequestedWeek();
        $requestedInitial = $orderModel->getInitialHour();

        /*SOMA A QUANTIDADE ANTERIOR COM A RETORNADA*/
        $requestedHour = $requestedHour + $requested['Total'];
        $requestedDay = $requestedDay + $requested['Total'];
        $requestedWeek = $requestedWeek + $requested['Total'];
        $requestTime = Mage::getSingleton('core/date')->date('Y-m-d H:i:s');

        /*GRAVA O HORARIO DA REQUISICAO E AS QUANTIDADES*/
        $orderModel->setStatus($requestTime);
        $orderModel->setRequestedHour($requestedHour);
        $orderModel->setRequestedDay($requestedDay);
        $orderModel->setRequestedWeek($requestedWeek);

        if (empty($requestedInitial)) {
            $orderModel->setInitialHour($requestTime);
        }

        $orderModel->save();

        foreach ($requested['Orders'] as $order) {
            self::processingOrder($order);
        }
    }

    public static function processingOrder($order)
    {
        /*VERIFICA SE CLIENTE JÁ EXISTE*/
        if ($order['CustomerPfCpf']) {
            $customerDoc = $order['CustomerPfCpf'];
        } elseif ($order['CustomerPjCnpj']) {
            $customerDoc = $order['CustomerPjCnpj'];
        }

        if (!empty($customerDoc)) {
            $customer = Mage::getModel('customer/customer')
                ->getCollection()
                ->addFieldToFilter('taxvat', $customerDoc)->load()->getFirstItem();
            $customerId = $customer->getId();
        }

        /*SE CLIENTE JA ESXISTE, ATUALIZA, SE NAO, CRIA*/
        if ($customerId && !empty($customerId)) {
            self::updateCustomer($customer, $order);
        } else {
            $customerId = self::createCustomer($order);
        }

        /*VERIFIFA SE JA EXISTE PEDIDO NO MAGENTO COM O ID DA COMPRA INTEGRACOMMERCE*/
        $existingOrder = Mage::getModel('sales/order')->load($order['IdOrder'], 'integracommerce_id');

        $incrementId = $existingOrder->getIncrementId();
        if (!empty($incrementId)) {
            return;
        } else {
            /*CRIA O PEDIDO NA TABELA DE CONTROLE DO MODULO*/
            $integraModel = self::integraOrder($order, $customerId, null);
            /*CRIA O PEDIDO NO MAGENTO*/
            self::createOrder($order, $customerId, $integraModel);
        }
    }

    public static function createCustomer($order)
    {
        $ieAttribute = Mage::getStoreConfig('integracommerce/attributes/ierg', Mage::app()->getStore());
        $customer = Mage::getModel("customer/customer");
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        $customer->setStore(Mage::app()->getStore());
     
        $customer->setFirstname((empty($order['CustomerPfName']) ? $order['CustomerPjCorporatename'] : $order['CustomerPfName']));
        $customer->setLastname('.');
        $customer->setData('taxvat', (empty($order['CustomerPfCpf']) ? $order['CustomerPjCnpj'] : $order['CustomerPfCpf']));

        if (!empty($order['CustomerPjIe']) && $ieAttribute !== 'not_selected') {
            $customer->setData($ieAttribute, $customer['CustomerPjIe']);
        }

        $newEmail = $order['MarketplaceName'] . '_' . mt_rand() . '@email.com.br';
        $customer->setEmail($newEmail);

        try {
            $customer->save();
        } catch (Exception $e) {
                Mage::log('Erro ao criar o cliente. Mensagem: '.$e->getMessage(), null, 'customer_save_error_integracommerce.log');
        }        

        //VERIFICA SE TEM DADOS PARA CADASTRAR ENDEREÇO  
        $region = Mage::getModel('directory/region')->loadByCode($order['DeliveryAddressState'], 'BR');

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
            ->setFax($order['TelephoneSecundaryNumber'])
            ->setStreet(array($order['DeliveryAddressStreet'], $order['DeliveryAddressNumber'], (empty($order['DeliveryAddressAdditionalInfo']) ? 'Não Informado' : $order['DeliveryAddressAdditionalInfo']) , $order['DeliveryAddressNeighborhood']))
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

        $region = Mage::getModel('directory/region')->loadByCode($order['DeliveryAddressState'], 'BR');

        $address->setCustomerId($customer->getId())
            ->setFirstname((empty($order['CustomerPfName']) ? $order['CustomerPjCorporatename'] : $order['CustomerPfName']))
            ->setLastname('.')           
            ->setCountryId('BR')
            ->setPostcode($order['DeliveryAddressZipcode'])
            ->setCity($order['DeliveryAddressCity'])
            ->setRegion($region->getName())
            ->setRegionId($region->getId())
            ->setTelephone($order['TelephoneMainNumber'])
            ->setFax($order['TelephoneSecundaryNumber'])
            ->setStreet(array($order['DeliveryAddressStreet'], $order['DeliveryAddressNumber'], (empty($order['DeliveryAddressAdditionalInfo']) ? 'Não Informado' : $order['DeliveryAddressAdditionalInfo']), $order['DeliveryAddressNeighborhood']))
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

    public static function createOrder($order, $customerId, $integraModel = null)
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

        $mageOrder = Mage::getModel('sales/order')
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

        $mageOrder->setCustomer_email($customer->getEmail())
            ->setCustomerFirstname($customer->getFirstname())
            ->setCustomerLastname($customer->getLastname())
            ->setCustomerTaxvat($customer->getTaxvat())
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
                ->setTelephone($billing->getTelephone())
                ->setFax($billing->getFax())
                ->setVatId($customer->getData('taxvat'));
        $mageOrder->setBillingAddress($billingAddress);

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
            ->setTelephone($shipping->getTelephone())
            ->setFax($shipping->getFax())
            ->setVatId($customer->getData('taxvat'));

        //INSERINDO METODO DE ENTREGA
        if (empty($order['TotalFreight'])) {
            $shippingprice = 0;
        } else {
            $shippingprice = $order['TotalFreight'];
        }

        $mageOrder->setShippingAddress($shippingAddress)
            ->setShipping_method('flatrate_flatrate')
            ->setShippingDescription('Loja: '. $order['StoreName'] .', Marketplace: '. $order['MarketplaceName'] .', Frete: ' . $order['ShippedCarrierName'])
            ->setShippingAmount($shippingprice)
            ->setBaseShippingAmount($shippingprice);

        $orderPayment = Mage::getModel('sales/order_payment')
            ->setStoreId($storeId)
            ->setCustomerPaymentId(0)
            ->setMethod('cashondelivery')
            ->setPo_number(' – ');
        $mageOrder->setPayment($orderPayment);

        $weightAttribute = Mage::getStoreConfig('integracommerce/attributes/weight', Mage::app()->getStore());
        $productControl = Mage::getStoreConfig('integracommerce/general/sku_control', Mage::app()->getStore());
        $subTotal = 0;
        foreach ($order['Products'] as $key => $product) {
            if ($productControl == 'sku') {
                $productId = Mage::getModel('catalog/product')->getResource()->getIdBySku($product['IdSku']);
            } else {
                $productId = $product['IdSku'];
            }

            $mageProduct = Mage::getModel('catalog/product')->load($productId);

            if (!$mageProduct->getId()) {
                continue;
            }

            $newPrice = $product['Price'];
            $rowTotal = $newPrice * $product['Quantity'];
            $orderItem = Mage::getModel('sales/order_item')
                ->setStoreId($storeId)
                ->setQuoteItemId(0)
                ->setQuoteParentItemId(null)
                ->setProductId($mageProduct->getId())
                ->setProductType($mageProduct->getTypeId())
                ->setQtyBackordered(null)
                ->setTotalQtyOrdered($product['Quantity'])
                ->setQtyOrdered($product['Quantity'])
                ->setName($mageProduct->getName())
                ->setSku($mageProduct->getSku())
                ->setPrice($newPrice)
                ->setWeight($mageProduct->getData($weightAttribute))
                ->setBasePrice($newPrice)
                ->setOriginalPrice($newPrice)
                ->setRowTotal($rowTotal)
                ->setBaseRowTotal($rowTotal);

                $subTotal += $rowTotal;
                $mageOrder->addItem($orderItem);
        }

        $mageOrder->setSubtotal($subTotal)
            ->setBaseSubtotal($subTotal)
            ->setGrandTotal($subTotal + $shippingprice)
            ->setBaseGrandTotal($subTotal);

        $mageOrder->setData('integracommerce_id', $order['IdOrder']);

        $estimatedDate = substr($order['EstimatedDeliveryDate'], 0, 10);
        $estimatedDate = DateTime::createFromFormat('Y-m-d', $estimatedDate);
        $estimatedDate = $estimatedDate->format('d/m/Y');
        
        $comment = $mageOrder->addStatusHistoryComment("Código do Pedido Integracommerce: " .
            $order['IdOrder'] . "<br>" . "Código do Pedido Marketplace: " .
            $order['IdOrderMarketplace'] . "<br>" . "Data Estimada de Entrega: " . $estimatedDate, false);
        $comment->setIsCustomerNotified(false);

        try {
            $transaction->addObject($mageOrder);
            $transaction->addCommitCallback(array($mageOrder, 'place'));
            $transaction->addCommitCallback(array($mageOrder, 'save'));
            $transaction->save();
        } catch (Exception $e){
            $integraModel->setMageError($e->getMessage());
            $integraModel->save();
        }

        $entityId = $mageOrder->getEntityId();
        $updateIncrementId = $mageOrder->getIncrementId();
        
        if (!empty($updateIncrementId)) {
            self::updateIntegraOrder($order['IdOrder'], $entityId);

            $status = Mage::getStoreConfig('integracommerce/order_status/approved', Mage::app()->getStore());
            if ($status !== 'keepstatus') {
                $states = array();
                $stateCollection = self::orderStatusFilter($status);
                foreach ($stateCollection as $state) {
                    $states[] = $state->getState();
                }

                $mageOrder->setData('state', $states[0]);
                $mageOrder->setStatus($status);
                $history = $mageOrder->addStatusHistoryComment("Status no Integracommerce: Aprovado", false);
                $history->setIsCustomerNotified(false);

                try {
                    $mageOrder->save();
                } catch (Exception $e) {
                    $integraModel->setMageError($e->getMessage());
                    $integraModel->save();
                }
            }
        }

        $shouldInvoice = Mage::getStoreConfig('integracommerce/order_status/invoice', Mage::app()->getStore());
        if ($mageOrder->canInvoice() && $shouldInvoice == 1) {
            self::createInvoice($mageOrder);
        }

        $integraModel->setMagentoOrderId($entityId);
        $integraModel->setMagentoCustomerId($customer->getId());
        $integraModel->setCustomerEmail($customer->getEmail());
        $integraModel->save();

        return $entityId;
    }

    public static function createInvoice($mageOrder)
    {
        $invoice = Mage::getModel('sales/service_order', $mageOrder)->prepareInvoice();
        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::NOT_CAPTURE);
        $invoice->register();
        $invoice->getOrder()->setCustomerNoteNotify(false);
        $invoice->getOrder()->setIsInProcess(true);
        $invoice->setState(Mage_Sales_Model_Order_Invoice::STATE_OPEN);

        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transactionSave->save();
    }

    public static function updateIntegraOrder($orderId, $mageOrderId)
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        $url = 'https://' . $environment . '.integracommerce.com.br/api/Order';

        $body = array(
            "IdOrder" => $orderId,
            "OrderStatus" => 'PROCESSING'
        );

        $jsonBody = json_encode($body);

        $return = Novapc_Integracommerce_Helper_Data::callCurl("PUT", $url, $jsonBody);

        if ($return['httpCode'] !== 204) {
            if (!empty($return['Errors'])) {
                foreach ($return['Errors'] as $error) {
                    $errorMessage = $error['Message'] . ', ';
                }

                Mage::log('Error: ' . $httpcode . 'Erro ao atualizar o pedido ' . $mageOrderId . ', Codigo Integracommerce: ' . $orderId . '. Motivo: ' . $return['Message'] . '. Erros: ' . $errorMessage, null, 'integracommerce_order_update_error.log');
            }

            $requestLog = Mage::getStoreConfig('integracommerce/general/request_log', Mage::app()->getStore());
            if ($requestLog == 1) {
                Mage::log('Requisição: ' . $jsonBody, null, 'integracommerce_order_request.log');
            }
        }
    }

    public static function integraOrder($order,$customerId,$mageOrder = null)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        $integraOrder = Mage::getModel('integracommerce/order')->load($order['IdOrder'], 'integra_id');

        $integraId = $integraOrder->getIntegraId();
        if (empty($integraId)) {
            $integraOrder = Mage::getModel('integracommerce/order');
            $integraOrder->setIntegraId($order['IdOrder']);
        }

        $integraOrder->setMarketplaceId($order['IdOrderMarketplace']);
        $integraOrder->setMarketplaceName($order['MarketplaceName']);
        $integraOrder->setStoreName($order['StoreName']);

        $integraOrder->setCustomerPfCpf((empty($order['CustomerPfCpf']) ? "" : $order['CustomerPfCpf']));
        $integraOrder->setCustomerPfName((empty($order['CustomerPfName']) ? "" : $order['CustomerPfName']));
        $integraOrder->setCustomerPjCnpj((empty($order['CustomerPjCnpj']) ? "" : $order['CustomerPjCnpj']));
        $integraOrder->setCustomerPjCorporateName((empty($order['CustomerPjCorporatename']) ? "" : $order['CustomerPjCorporatename']));

        $integraOrder->setDeliveryStreet($order['DeliveryAddressStreet']);
        $integraOrder->setDeliveryAdditionalInfo($order['DeliveryAddressAdditionalInfo']);
        $integraOrder->setDeliveryNeighborhood($order['DeliveryAddressNeighborhood']);
        $integraOrder->setDeliveryCity($order['DeliveryAddressCity']);
        $integraOrder->setDeliveryReference($order['DeliveryAddressReference']);
        $integraOrder->setDeliveryState($order['DeliveryAddressState']);
        $integraOrder->setDeliveryNumber($order['DeliveryAddressNumber']);
        $integraOrder->setTelephoneMain($order['TelephoneMainNumber']);
        $integraOrder->setTelephoneSecondary($order['TelephoneSecundaryNumber']);
        $integraOrder->setTelephoneBusiness($order['TelephoneBusinessNumber']);
        $integraOrder->setTotalAmount($order['TotalAmount']);
        $integraOrder->setTotalFreight($order['TotalFreight']);
        $integraOrder->setTotalDiscount($order['TotalDiscount']);
        $integraOrder->setOrderStatus($order['OrderStatus']);

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

    public static function checkDate($line, $integraModel)
    {
        if (!empty($line)) {
            $ymd = DateTime::createFromFormat('d/m/Y', $line);
            if ($ymd) {
                $line = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
            } else {
                $ymd = DateTime::createFromFormat('d/m/Y H:i:s', $line);
                if ($ymd) {
                    $line = $ymd->format('Y-m-d\TH:i:s\.000-03:00');
                } else {
                    $integraModel->setMageError('Motivo: Data inválida. Erros: a data deve seguir o padrão brasileiro');
                    $integraModel->save();
                    return;
                }
            }

            return $line;
        }
    }

    public static function updateOrder($order)
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        $invoiceStatus = Mage::getStoreConfig('integracommerce/order_status/nota_fiscal', Mage::app()->getStore());
        $shippingStatus = Mage::getStoreConfig('integracommerce/order_status/dados_rastreio', Mage::app()->getStore());
        $integraModel = Mage::getModel('integracommerce/order')->load($order->getData('integracommerce_id'), 'integra_id');
        $formatoNfe = Mage::getStoreConfig('integracommerce/order_status/nfe_model', Mage::app()->getStore());
        $url = 'https://' . $environment . '.integracommerce.com.br/api/Order';

        try {
            $status = $order->getStatus();
            $comment = self::getHistoryByStatus($order, $status);
            $commentData = $comment->getData('comment');

            $lines = explode('|', $commentData);
            if ((empty($lines) && $status !== 'delivered') || empty($commentData)) {
                return;
            }

            $line = array();
            foreach ($lines as $_line) {
                $line[] = $_line;
            }

            if ($status == $invoiceStatus && count($line) < 4 && $formatoNfe == 'old') {
                throw new Exception("Não foi possivel enviar os dados da Nota Fiscal. Informações inválidas.");
            } elseif ($status == $shippingStatus && count($line) !== 5) {
                throw new Exception("Não foi possivel enviar os dados de Rastreio. Informações inválidas.");
            } elseif ($status == 'shipexception' && count($line) !== 2) {
                throw new Exception("Não foi possivel enviar os dados de Falha no Envio. Informações inválidas.");
            }

            if (($invoiceStatus && !empty($invoiceStatus)) && $invoiceStatus == $status) {
                //VERIFICANDO FORMATO UTILIZADO
                if ($formatoNfe == 'new') {
                    $preparedNfe = self::nfeFormat($commentData);
                    if (empty($preparedNfe)) {
                        throw new Exception("Não foi possivel enviar os dados da Nota Fiscal. Informações inválidas.");
                    }

                    $line[0] = $preparedNfe['numeroNota'];
                    $line[1] = $preparedNfe['serieNota'];
                    $return = self::checkDate($preparedNfe['dataEmissaoNota'], $integraModel);
                    $line[2] = $return;
                    if (strlen($preparedNfe['chaveNota']) < 44) {
                        $line[3] = str_pad($preparedNfe['chaveNota'], 44, "0");
                    } else {
                        $line[3] = $preparedNfe['chaveNota'];
                    }

                    $line[4] = $preparedNfe['xmlNota'];
                } else {
                    //CHECANDO DATA DE EMISSAO DA FATURA
                    $return = self::checkDate($line[2], $integraModel);
                    $line[2] = $return;

                    if (strlen($line[3]) < 44) {
                        $line[3] = str_pad($line[3], 44, "0");
                    }
                }

                $body = array(
                    "IdOrder" => $order->getData('integracommerce_id'),
                    "OrderStatus" => "INVOICED",
                    "InvoicedNumber" => $line[0],
                    "InvoicedLine" => $line[1],
                    "InvoicedIssueDate" => $line[2],
                    "InvoicedKey" => $line[3],
                    "InvoicedDanfeXml" => (empty($line[4]) ? "" : $line[4])
                );
            } elseif (($shippingStatus && !empty($shippingStatus)) && $shippingStatus == $status) {
                //CHECANDO DATA ESTIMADA DE ENTREGA
                $return = self::checkDate($line[2], $integraModel);
                $line[2] = $return;

                //CHECANDO DATA DE ENTREGA A TRANSPORTADORA
                $return = self::checkDate($line[3], $integraModel);
                $line[3] = $return;

                $body = array(
                    "IdOrder" => $order->getData('integracommerce_id'),
                    "OrderStatus" =>"SHIPPED",
                    "ShippedTrackingUrl" => (empty($line[0]) ? "" : $line[0]),
                    "ShippedTrackingProtocol" => (empty($line[1]) ? "" : $line[1]),
                    "ShippedEstimatedDelivery" => $line[2],
                    "ShippedCarrierDate" => $line[3],
                    "ShippedCarrierName" => $line[4]
                );
            } elseif ($status == 'delivered') {
                //CHECANDO DATA ESTIMADA DE ENTREGA
                $return = self::checkDate($commentData, $integraModel);
                $deliveredDate = $return;

                $body = array(
                    "IdOrder" => $order->getData('integracommerce_id'),
                    "OrderStatus" => "DELIVERED",
                    "DeliveredDate" => $deliveredDate
                );
            } elseif ($status == 'shipexception') {
                $return = self::checkDate($line[1], $integraModel);
                $line[1] = $return;

                $body = array(
                    "IdOrder" => $order->getData('integracommerce_id'),
                    "OrderStatus" => "SHIPMENT_EXCEPTION",
                    "ShipmentExceptionObservation" => $line[0],
                    "ShipmentExceptionOccurrenceDate" => $line[1]
                );
            }

            if (isset($body)) {
                $jsonBody = json_encode($body);
                $return = Novapc_Integracommerce_Helper_Data::callCurl("PUT", $url, $jsonBody);

                if ($return['httpCode'] !== 204) {
                    if (!empty($return['Errors'])) {
                        foreach ($return['Errors'] as $error) {
                            $return = $error['Message'] . '. ';
                        };
                    } elseif ($return['httpCode'] == 200) {
                        $return = 'Dados inseridos';
                    } else {
                        $return = json_encode($return);
                    }
                    $integraModel->setIntegraError($return);
                    $integraModel->save();
                }
            }

        } catch (Exception $e) {
            $integraModel->setIntegraError($e->getMessage());
            $integraModel->save();
        }
    }

    public static function viewOrder($id)
    {
        $integraOrder = Mage::getModel('integracommerce/order')->load($id, 'integra_id');
        $mageCustomer = Mage::getModel('customer/customer')->load($integraOrder->getMagentoCustomerId());
        $mageOrder = Mage::getModel('sales/order')->load($integraOrder->getMagentoOrderId());

        return array($integraOrder,$mageCustomer,$mageOrder);
    }

    public static function nfeFormat($commentData)
    {
        preg_match('/^Nota/', $commentData, $checkData, PREG_OFFSET_CAPTURE);
        if (empty($checkData)) {
            return;
        }

        $preparedArray = array();
        $commentArray = explode("\n", $commentData);
        $preparedArray['numeroNota'] = substr($commentArray[0], 13);
        $preparedArray['serieNota'] = substr($commentArray[1], 7);
        $preparedArray['dataEmissaoNota'] = substr($commentArray[2], 18);
        $preparedArray['chaveNota'] = substr($commentArray[3], 17);
        if (empty($preparedArray['chaveNota']) || preg_match("/[a-z]/i", $preparedArray['chaveNota'])) {
            $preparedArray['chaveNota'] = substr($commentArray[4], 17);
        }

        $preparedArray['xmlNota'] = substr($commentArray[5], 15);
        if (empty($preparedArray['xmlNota'])) {
            $preparedArray['xmlNota'] = substr($commentArray[5], 13);
        }

        return $preparedArray;
    }

    public static function getHistoryByStatus($order, $statusId)
    {
        foreach ($order->getStatusHistoryCollection(true) as $status) {
            if ($status->getStatus() == $statusId) {
                return $status;
            }
        }

        return false;
    }

    public static function orderStatusFilter($status)
    {
        $collection = Mage::getResourceModel('sales/order_status_collection');
        $collection->getSelect()->joinLeft(
            array('state_table' => 'sales_order_status_state'),
            'main_table.status=state_table.status',
            array('state', 'is_default')
        );

        $collection->getSelect()->where('state_table.status=?', $status);

        return $collection;
    }

}