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

class Novapc_Integracommerce_Adminhtml_IntegrationController extends Mage_Adminhtml_Controller_Action
{
    const SUCCESS_MESSAGE = 'Sincronização Completa';
    const FALSE_FAIL_MSG  = 'Sincronização Completa. Existem itens no Relatório.';

    public function indexAction() 
    {
        $this->loadLayout();
        $this->_setActiveMenu('integracommerce');
        $this->renderLayout();
    }

    public function massCategoryAction()
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        $categoryModel = Mage::getModel('integracommerce/integration')->load('Category', 'integra_model');

        $limits = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($categoryModel, '(POST) api/Category');

        if (isset($limits['message'])) {
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($limits['message']));
            $categoryModel->setAvailable(0);
            $categoryModel->save();
            $this->_redirect('*/*/');
        } else {
            $alreadyRequested = $categoryModel->getRequestedHour();
            $requested = Novapc_Integracommerce_Helper_IntegrationData::integrateCategory($alreadyRequested, $limits);

            $requestTime = Novapc_Integracommerce_Helper_Data::currentDate(null, 'string');

            $categoryModel->setStatus($requestTime);
            $categoryModel->setRequestedHour($requested);
            $categoryModel->save();

            Mage::getSingleton('core/session')->addSuccess(
                Mage::helper('integracommerce')->__(self::SUCCESS_MESSAGE)
            );

            $this->_redirect('*/*/');
        }
    }

    public function massInsertAction()
    {
        $productModel = Mage::getModel('integracommerce/integration')->load('Product Insert', 'integra_model');

        $limits = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($productModel, '(POST) api/Product');

        if (isset($limits['message'])) {
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($limits['message']));
            $productModel->setAvailable(0);
            $productModel->save();
            $this->_redirect('*/*/');
        } else {
            $alreadyRequested = $productModel->getRequestedHour();
            $requested = Novapc_Integracommerce_Helper_IntegrationData::integrateProduct($alreadyRequested, $limits);

            $requestTime = Novapc_Integracommerce_Helper_Data::currentDate(null, 'string');
            $productModel->setStatus($requestTime);
            $productModel->setRequestedHour($requested);

            $productModel->save();

            $queueCollection = Mage::getModel('integracommerce/update')->getCollection();
            $queueCount = $queueCollection->getSize();

            if ($queueCount >= 1) {
                Mage::getSingleton('core/session')->addWarning(
                    Mage::helper('integracommerce')->__(self::FALSE_FAIL_MSG)
                );
            } else {
                Mage::getSingleton('core/session')->addSuccess(
                    Mage::helper('integracommerce')->__(self::SUCCESS_MESSAGE)
                );
            }

            $this->_redirect('*/*/');
        }
    }

    public function massUpdateAction()
    {
        $productModel = Mage::getModel('integracommerce/integration')->load('Product Update', 'integra_model');

        $limits = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($productModel, '(PUT) api/Product');

        if (isset($limits['message'])) {
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($limits['message']));
            $productModel->setAvailable(0);
            $productModel->save();
            $this->_redirect('*/*/');
        } else {
            $alreadyRequested = $productModel->getRequestedHour();
            $requested = Novapc_Integracommerce_Helper_IntegrationData::forceUpdate($alreadyRequested, $limits);

            $requestTime = Novapc_Integracommerce_Helper_Data::currentDate(null, 'string');

            $productModel->setStatus($requestTime);
            $productModel->setRequestedHour($requested);

            $productModel->save();

            $queueCollection = Mage::getModel('integracommerce/update')->getCollection();
            $queueCount = $queueCollection->getSize();

            if ($queueCount >= 1) {
                Mage::getSingleton('core/session')->addWarning(
                    Mage::helper('integracommerce')->__(self::FALSE_FAIL_MSG)
                );
            } else {
                Mage::getSingleton('core/session')->addSuccess(
                    Mage::helper('integracommerce')->__(self::SUCCESS_MESSAGE)
                );
            }

            $this->_redirect('*/*/');
        }
    }

    public function checklimitAction()
    {
        $environment = Mage::getStoreConfig('integracommerce/general/environment', Mage::app()->getStore());
        $url = 'https://' . $environment . '.integracommerce.com.br/api/EndPointLimit';

        $response = Novapc_Integracommerce_Helper_Data::callCurl('GET', $url);

        $httpCode = (int) $response['httpCode'];
        if ($httpCode == 200) {
            $requestData = array();
            unset($response['httpCode']);
            foreach ($response as $limit) {
                $requestData[] = array(
                    'name'   => $limit['Name'],
                    'minute' => $limit['RequestsByMinute'],
                    'hour'   => $limit['RequestsByHour']
                );
            }

            Mage::getModel('integracommerce/request')
                ->getCollection()
                ->bulkInsert($requestData);

            Mage::getSingleton('core/session')->addSuccess(
                Mage::helper('integracommerce')->__('Limites de Requisições salvos.')
            );
        } else {
            Mage::getSingleton('core/session')->addError(
                Mage::helper('integracommerce')->__('Erro ao conectar, verifique as credenciais da API.')
            );
        }

        $this->_redirectReferer();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('integracommerce/adminhtml_integration_grid')->toHtml()
        );
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('integracommerce/integration');
    }
}