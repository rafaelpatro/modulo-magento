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

class Novapc_Integracommerce_Model_Resource_Order_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('integracommerce/order');
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

    public function addIdsToFilter($ordersIds = null, $limit = null, $offset = null)
    {
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns('magento_order_id');
        if (!empty($ordersIds)) {
            $stringIds = implode(",", $ordersIds);
            $idsSelect->where('entity_id in (' . $stringIds . ')');
        }

        $idsSelect->limit($limit, $offset);
        $idsSelect->resetJoinLeft();

        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }

    public function orderStatusFilter($status)
    {
        $stateTable = Mage::getSingleton('core/resource')->getTableName('sales/order_status_state');
        $collection = Mage::getResourceModel('sales/order_status_collection');
        $collection->getSelect()->joinLeft(
            array('state_table' => $stateTable),
            'main_table.status=state_table.status',
            array('state', 'is_default')
        );

        $collection->getSelect()->where('state_table.status=?', $status);

        return $collection;
    }

    public function deleteOrders($ordersIds)
    {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $write->delete(
            $this->getMainTable(),
            array('entity_id IN (?)' => $ordersIds)
        );
    }
}