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

    public function massExecuteAction()
    {
        $params = (array) $this->getRequest()->getParam('integracommerce_integration');
        $modelId = (int) $params[0];
        $exportModel = Mage::getModel('integracommerce/integration')->load($modelId);
        $modelType = (string) $exportModel->getIntegraModel();

        if ($modelType == 'Category') {
            $message = Mage::getModel('integracommerce/observer')->categoryExport();
        } elseif ($modelType == 'Product Insert') {
            $message = Mage::getModel('integracommerce/observer')->productExport();
        } elseif ($modelType == 'Product Update') {
            $message = Mage::getModel('integracommerce/observer')->productUpdate();
        }

        if (!empty($message)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($message));
        } else {
            if ($modelType !== 'Category') {
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
            } else {
                Mage::getSingleton('core/session')->addSuccess(
                    Mage::helper('integracommerce')->__(self::SUCCESS_MESSAGE)
                );
            }
        }

        $this->_redirect('*/*/');
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
                $minute = (int) $limit['RequestsByMinute'];
                $hour = $minute * 60;
                $requestData[] = array(
                    'name'   => $limit['Name'],
                    'minute' => $minute,
                    'hour'   => $hour
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