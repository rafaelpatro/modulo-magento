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

        $message = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($categoryModel, 'post');

        if (isset($message)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($message));
            $categoryModel->setAvailable(0);
            $categoryModel->save();
            $this->_redirect('*/*/');
        } else {
            $alreadyRequested = $categoryModel->getRequestedHour();
            $requestedDay = $categoryModel->getRequestedDay();
            $requestedWeek = $categoryModel->getRequestedWeek();
            $requestedInitial = $categoryModel->getInitialHour();
            $requestedHour = Novapc_Integracommerce_Helper_IntegrationData::integrateCategory($alreadyRequested);

            if ($alreadyRequested == $requestedHour) {
                $requested = 0;
                $requestedHour = 0;
            } else {
                $requested = $requestedHour - $alreadyRequested;
            }

            $requestedDay = $requestedDay + $requested;
            $requestedWeek = $requestedWeek + $requested;
            $requestTime = Novapc_Integracommerce_Helper_Data::currentDate(null, 'string');

            $categoryModel->setStatus($requestTime);
            $categoryModel->setRequestedHour($requestedHour);
            $categoryModel->setRequestedDay($requestedDay);
            $categoryModel->setRequestedWeek($requestedWeek);

            if (empty($requestedInitial)) {
                $categoryModel->setInitialHour($requestTime);
            }

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

        $message = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($productModel, 'post');

        if (isset($message)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($message));
            $productModel->setAvailable(0);
            $productModel->save();
            $this->_redirect('*/*/');
        } else {
            $alreadyRequested = $productModel->getRequestedHour();
            $requestedDay = $productModel->getRequestedDay();
            $requestedWeek = $productModel->getRequestedWeek();
            $requestedInitial = $productModel->getInitialHour();
            $requestedHour = Novapc_Integracommerce_Helper_IntegrationData::integrateProduct($alreadyRequested);

            if ($alreadyRequested == $requestedHour) {
                $requested = 0;
                $requestedHour = 0;
            } else {
                $requested = $requestedHour - $alreadyRequested;
            }

            $requestedDay = $requestedDay + $requested;
            $requestedWeek = $requestedWeek + $requested;
            $requestTime = Novapc_Integracommerce_Helper_Data::currentDate(null, 'string');

            $productModel->setStatus($requestTime);
            $productModel->setRequestedHour($requestedHour);
            $productModel->setRequestedDay($requestedDay);
            $productModel->setRequestedWeek($requestedWeek);

            if (empty($requestedInitial)) {
                $productModel->setInitialHour($requestTime);
            }

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

        $message = Novapc_Integracommerce_Helper_IntegrationData::checkRequest($productModel, 'put');

        if (isset($message)) {
            Mage::getSingleton('core/session')->addError(Mage::helper('integracommerce')->__($message));
            $productModel->setAvailable(0);
            $productModel->save();
            $this->_redirect('*/*/');
        } else {
            $alreadyRequested = $productModel->getRequestedHour();
            $requestedDay = $productModel->getRequestedDay();
            $requestedWeek = $productModel->getRequestedWeek();
            $requestedInitial = $productModel->getInitialHour();
            $requestedHour = 100;
            $queueIds = Mage::getModel('integracommerce/update')
                ->getCollection()
                ->getProductIds();
            $requestedHour = Novapc_Integracommerce_Helper_IntegrationData::forceUpdate($alreadyRequested, $queueIds);

            if ($alreadyRequested == $requestedHour) {
                $requested = 0;
                $requestedHour = 0;
            } else {
                $requested = $requestedHour - $alreadyRequested;
            }

            $requestedDay = $requestedDay + $requested;
            $requestedWeek = $requestedWeek + $requested;
            $requestTime = Novapc_Integracommerce_Helper_Data::currentDate(null, 'string');

            $productModel->setStatus($requestTime);
            $productModel->setRequestedHour($requestedHour);
            $productModel->setRequestedDay($requestedDay);
            $productModel->setRequestedWeek($requestedWeek);

            if (empty($requestedInitial)) {
                $productModel->setInitialHour($requestTime);
            }

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