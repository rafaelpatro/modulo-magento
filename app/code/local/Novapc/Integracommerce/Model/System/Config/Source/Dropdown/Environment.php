<?php

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Environment
{
    public function toOptionArray()
    {
        return array(          
            array(
                'value' => '1',
                'label' => 'Homologação',
            ),
            array(
                'value' => '2',
                'label' => 'Produção',
            ),
        );
    }
}