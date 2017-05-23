<?php

class Novapc_Integracommerce_Model_Mysql4_Queue extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('integracommerce/queue','entity_id');
    }
}