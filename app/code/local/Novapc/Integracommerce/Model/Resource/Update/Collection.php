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

class Novapc_Integracommerce_Model_Resource_Update_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('integracommerce/update');
    }

    protected function _getClearSelect()
    {
        return $this->_buildClearSelect();
    }

    protected function _buildClearSelect($select = null)
    {
        if (empty($select)) {
            $select = clone $this->getSelect();
        }

        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);
        $select->reset(Zend_Db_Select::COLUMNS);

        return $select;
    }

    public function getProductIds($limit = null, $offset = null)
    {
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns('product_id');
        $idsSelect->limit($limit, $offset);
        $idsSelect->resetJoinLeft();

        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }

    public function bulkInsert($productsIds)
    {
        if (count($productsIds) == 0) {
            return;
        }

        $rows = array();
        foreach ($productsIds as $productId) {
            $rows[] = array(
                'product_id'      => $productId,
                'product_body'    => null,
                'product_error'   => null,
                'sku_body'        => null,
                'sku_error'       => null,
                'price_body'      => null,
                'price_error'     => null,
                'stock_body'      => null,
                'stock_error'     => null,
                'requested_times' => 0
            );
        }

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $write->insertOnDuplicate(
            $this->getMainTable(),
            $rows,
            array('product_id')
        );
    }

    public function deleteItens($productsIds)
    {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $write->delete(
            $this->getMainTable(),
            array('entity_id IN (?)' => $productsIds)
        );
    }

    public function resetItens($productsIds)
    {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $write->delete(
            $this->getMainTable(),
            array('entity_id IN (?)' => $productsIds)
        );
    }
}