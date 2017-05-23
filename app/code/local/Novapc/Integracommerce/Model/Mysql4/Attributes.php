<?php

class Novapc_Integracommerce_Model_Mysql4_Attributes extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('integracommerce/attributes','entity_id');
    }
}