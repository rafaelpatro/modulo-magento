<?php

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Configprod
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => '4',
                'label' => 'Selecione uma opção...',
            ),            
            array(
                'value' => '1',
                'label' => 'Produto Único',
            ),
            array(
                'value' => '2',
                'label' => 'Por Variação',
            ),
        );
    }
}