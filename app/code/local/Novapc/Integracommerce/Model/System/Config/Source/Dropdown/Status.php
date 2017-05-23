<?php

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Status
{
    public function toOptionArray()
    {
        $orderStatusCollection = Mage::getModel('sales/order_status')->getResourceCollection()->getData();
        $retornArray = array();
        $retornArray = array(
            '-1'=>'Please Select..'
        );        
        foreach ($orderStatusCollection as $orderStatus) {
            $retornArray[] = array (
                'value' => $orderStatus['status'], 'label' => $orderStatus['label']
            );
        }

        return $retornArray;
    }
}