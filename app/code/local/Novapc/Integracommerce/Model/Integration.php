<?php

class Novapc_Integracommerce_Model_Integration extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('integracommerce/integration');
    }
}