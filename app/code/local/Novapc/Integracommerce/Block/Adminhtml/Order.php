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

class Novapc_Integracommerce_Block_Adminhtml_Order extends Mage_Adminhtml_Block_Widget_Container
{

    /**
     * Set template
     */
      public function __construct()
      {
        parent::__construct();
      }

    /**
     * Prepare button and grid
     *
     * @return Mage_Adminhtml_Block_
     */
    protected function _prepareLayout()
    {
        $this->setChild('grid', $this->getLayout()->createBlock('integracommerce/adminhtml_order_grid', 'order.grid'));
        return parent::_prepareLayout();
    }

    /**
     * Render grid
     *
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }

    /**
     * Check whether it is single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        if (!Mage::app()->isSingleStoreMode()) {
               return false;
        }

        return true;
    }
}
