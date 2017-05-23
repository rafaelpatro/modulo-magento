<?php

class Novapc_Integracommerce_Model_Mysql4_Update extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('integracommerce/update','entity_id');
    }
}