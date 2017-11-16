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

class Novapc_Integracommerce_Adminhtml_ReportController extends Mage_Adminhtml_Controller_Action
{


    public function indexAction() 
    {
        $this->loadLayout();
        $this->_setActiveMenu('integracommerce');
        $this->renderLayout();
    
    }

    /**
     * Product grid for AJAX request
     */
    public function gridAction()
    {
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
        $this->_addContent(
            $this->getLayout()
            ->createBlock('integracommerce/adminhtml_report_edit')
        )
            ->_addLeft(
                $this->getLayout()
                ->createBlock('integracommerce/adminhtml_report_edit_tabs')
            );
        $this->renderLayout();
    }

    public function deleteAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try
            {
                $errorQueue = Mage::getModel('integracommerce/update')
                    ->load($this->getRequest()->getParam('id'), 'product_id');
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

    protected function massDeleteAction()
    {
        $itensIds = (array) $this->getRequest()->getParam('integracommerce_report');

        $collection = Mage::getModel('integracommerce/update')
            ->getCollection()
            ->deleteItens($itensIds);

        $this->_redirect('*/*/');
    }

    protected function massResetAction()
    {
        $itensIds = (array) $this->getRequest()->getParam('integracommerce_report');

        $collection = Mage::getModel('integracommerce/update')
            ->getCollection()
            ->addFieldToFilter('entity_id', $itensIds);

        foreach ($collection as $item) {
            $item->setRequestedTimes(0);
            $item->save();
        }

        $this->_redirect('*/*/');
    }

    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('integracommerce/report');
    }
}