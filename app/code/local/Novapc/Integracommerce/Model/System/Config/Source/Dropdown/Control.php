<?php

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Control
{
    public function toOptionArray()
    {
        return array(          
            array(
                'value' => '1',
                'label' => 'Código',
            ),
            array(
                'value' => '2',
                'label' => 'SKU',
            ),
        );
    }
}