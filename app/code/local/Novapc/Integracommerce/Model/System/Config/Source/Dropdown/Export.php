<?php

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Export
{
    public function toOptionArray()
    {
        return array(          
            array(
                'value' => '1',
                'label' => 'Selecionar Produtos',
            ),
            array(
                'value' => '2',
                'label' => 'Todos os Produtos',
            ),
        );
    }
}