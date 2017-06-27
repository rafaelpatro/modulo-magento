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

class Novapc_Integracommerce_Model_System_Config_Source_Dropdown_Status
{
    public function toOptionArray()
    {
        $orderStatusCollection = Mage::getModel('sales/order_status')->getResourceCollection()->getData();
        $retornArray = array();
        $retornArray = array(
            'keepstatus'=>'Por favor selecione...'
        );        
        foreach ($orderStatusCollection as $orderStatus) {
            if ($orderStatus['status'] == 'pending') {
                continue;
            }

            $retornArray[] = array (
                'value' => $orderStatus['status'], 'label' => $orderStatus['label']
            );
        }

        return $retornArray;
    }
}