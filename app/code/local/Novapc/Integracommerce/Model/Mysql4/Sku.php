<?php

class Novapc_Integracommerce_Model_Mysql4_Sku extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('integracommerce/sku','entity_id');
    }
}