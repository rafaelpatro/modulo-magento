<?php
/**
 * Novapc Integracommerce
 * 
 * @category     Novapc
 * @package      Novapc_Integracommerce
 * @copyright    Copyright (c) 2016 Novapc (http://www.novapc.com.br/)
 * @author       NovaPC
 * @version      Release: 1.0.0 
 */

class Novapc_Integracommerce_Adminhtml_IntegrationController extends Mage_Adminhtml_Controller_Action
{

	
    public function indexAction() 
    {
        //$this->_initAction();
        //$this->renderLayout();
        $this->loadLayout();
        $this->_setActiveMenu('integracommerce');
        $this->renderLayout();
    
    }

    public function massCategoryAction() {
        $api_user = Mage::getStoreConfig('integracommerce/general/api_user',Mage::app()->getStore());
        $api_password = Mage::getStoreConfig('integracommerce/general/api_password',Mage::app()->getStore());
        $authentication = base64_encode($api_user . ':' . $api_password);
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());

        Novapc_Integracommerce_Helper_IntegrationData::integrateCategory($authentication);

        $categoryModel = Mage::getModel('integracommerce/integration')->load('Category', 'integra_model');
        $categoryModel->setStatus(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        $categoryModel->save();

        Mage::getSingleton('core/session')->addSuccess(Mage::helper('integracommerce')->__("Synchronization completed."));

        $this->_redirect('*/*/');
    }

    public function massInsertAction() {
        $api_user = Mage::getStoreConfig('integracommerce/general/api_user',Mage::app()->getStore());
        $api_password = Mage::getStoreConfig('integracommerce/general/api_password',Mage::app()->getStore());
        $authentication = base64_encode($api_user . ':' . $api_password);
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());

        Novapc_Integracommerce_Helper_IntegrationData::integrateProduct($authentication);

        $productModel = Mage::getModel('integracommerce/integration')->load('Product Insert', 'integra_model');
        $productModel->setStatus(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        $productModel->save();

        $queueCollection = Mage::getModel('integracommerce/update')->getCollection();
        $queueCount = $queueCollection->getSize();

        if ($queueCount >= 1) {
            Mage::getSingleton('core/session')->addWarning(Mage::helper('integracommerce')->__("Existem itens no Relatório, por favor, verifique para mais informações."));
        } else {
            Mage::getSingleton('core/session')->addSuccess(Mage::helper('integracommerce')->__("Synchronization completed."));
        }

        $this->_redirect('*/*/');
    }

    public function massUpdateAction() {
        $api_user = Mage::getStoreConfig('integracommerce/general/api_user',Mage::app()->getStore());
        $api_password = Mage::getStoreConfig('integracommerce/general/api_password',Mage::app()->getStore());
        $authentication = base64_encode($api_user . ':' . $api_password);
        $environment = Mage::getStoreConfig('integracommerce/general/environment',Mage::app()->getStore());

        Novapc_Integracommerce_Helper_IntegrationData::forceUpdate($authentication);

        $productModel = Mage::getModel('integracommerce/integration')->load('Product Update', 'integra_model');
        $productModel->setStatus(Mage::getModel('core/date')->date('Y-m-d H:i:s'));
        $productModel->save();

        $queueCollection = Mage::getModel('integracommerce/update')->getCollection();
        $queueCount = $queueCollection->getSize();

        if ($queueCount >= 1) {
            Mage::getSingleton('core/session')->addWarning(Mage::helper('integracommerce')->__("Existem itens no Relatório, por favor, verifique para mais informações."));
        } else {
            Mage::getSingleton('core/session')->addSuccess(Mage::helper('integracommerce')->__("Synchronization completed."));
        }

        $this->_redirect('*/*/');
    }

    /**
     * Product grid for AJAX request
     */
    public function gridAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('integracommerce/adminhtml_integration_grid')->toHtml()
        );
    }
}