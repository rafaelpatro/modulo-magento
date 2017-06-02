<?php

class Novapc_Integracommerce_Model_Mysql4_Update_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
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
        if (is_null($select)) {
            $select = clone $this->getSelect();
        }
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);
        $select->reset(Zend_Db_Select::COLUMNS);

        return $select;
    }

    public function getAllIds($limit = null, $offset = null)
    {
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns('product_id');
        $idsSelect->limit($limit, $offset);
        $idsSelect->resetJoinLeft();

        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }
}