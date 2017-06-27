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

$installer = $this; 
$installer->startSetup();
 
// Required tables
$statusTable = $installer->getTable('sales/order_status');
$statusStateTable = $installer->getTable('sales/order_status_state');
 
// Insert statuses
$installer->getConnection()->insertArray(
    $statusTable,
    array(
        'status',
        'label'
    ),
    array(
        array('status' => 'delivered', 'label' => 'Entregue Integracommerce'),
        array('status' => 'shipexception', 'label' => 'Falha no Envio Integracommerce')
    )
);
 
// Insert states and mapping of statuses to states
$installer->getConnection()->insertArray(
    $statusStateTable,
    array(
        'status',
        'state',
        'is_default'
    ),
    array(
        array(
            'status' => 'delivered',
            'state' => 'complete',
            'is_default' => 0
        ),
        array(
            'status' => 'shipexception',
            'state' => 'complete',
            'is_default' => 0
        )
    )
);

$installer->endSetup();