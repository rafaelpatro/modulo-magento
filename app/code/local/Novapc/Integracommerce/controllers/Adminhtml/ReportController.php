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

class Novapc_Integracommerce_Adminhtml_ReportController extends Mage_Adminhtml_Controller_Action
{

	
    public function indexAction() 
    {
        //$this->_initAction();
        //$this->renderLayout();
        $this->loadLayout();
        $this->_setActiveMenu('integracommerce');
        $this->renderLayout();
    
    }

    /**
     * Product grid for AJAX request
     */
    public function gridAction() {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('integracommerce/adminhtml_report_grid')->toHtml()
        );
    }

    public function editAction()
    {
        $productQueueId = $this->getRequest()->getParam('id');
        $queueModel = Mage::getModel('integracommerce/update')->load($productQueueId, 'product_id');
        Mage::register('report_data', $queueModel);
        $this->loadLayout();
        $this->_addContent($this->getLayout()
            ->createBlock('integracommerce/adminhtml_report_edit'))
            ->_addLeft($this->getLayout()
                ->createBlock('integracommerce/adminhtml_report_edit_tabs')
            );
        $this->renderLayout();
    }

    public function deleteAction()
    {
        if($this->getRequest()->getParam('id') > 0)
        {
            try
            {
                $errorQueue = Mage::getModel('integracommerce/update')->load($this->getRequest()->getParam('id'), 'product_id');
                $errorQueue->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess('Item excluido com sucesso.');
                $this->_redirect('*/*/');
            }
            catch (Exception $e)
            {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    protected function cleanAction()
    {
        $collection = Mage::getModel('integracommerce/update')->getCollection();

        foreach ($collection as $item) {
            $item->delete();
        }

        $this->_redirect('*/*/');
    }

}