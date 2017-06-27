<?php
/**
 * PHP version 5
 * Novapc Integracommerce
 *
 * @category  Magento
 * @package   Novapc_Integracommerce
 * @author    Novapc <novapc@novapc.com.br>
 * @copyright 2017 Integracommerce
 * @license   https://opensource.org/licenses/osl-3.0.php PHP License 3.0
 * @version   GIT: 1.0
 * @link      https://github.com/integracommerce/modulo-magento
 */

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Environment
{
    public function toOptionArray()
    {
        return array(          
            array(
                'value' => 'api',
                'label' => 'Homologação',
            ),
            array(
                'value' => 'in',
                'label' => 'Produção',
            ),
        );
    }
}