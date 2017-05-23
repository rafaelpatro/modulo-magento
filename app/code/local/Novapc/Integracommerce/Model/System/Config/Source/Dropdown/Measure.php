<?php

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Measure
{
    public function toOptionArray()
    {
        return array(          
            array(
                'value' => '1',
                'label' => 'Centimetro',
            ),
            array(
                'value' => '2',
                'label' => 'Metro',
            ),
            array(
                'value' => '3',
                'label' => 'Milimetro',
            ),
        );
    }
}