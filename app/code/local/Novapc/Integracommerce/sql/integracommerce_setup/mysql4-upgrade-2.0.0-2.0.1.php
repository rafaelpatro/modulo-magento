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

$tablePrefix = Mage::getConfig()->getTablePrefix();
if (!empty($tablePrefix)) {
    $integrationTable = $tablePrefix . 'npcintegra_integration';
    $queueTable = $tablePrefix . 'npcintegra_order_queue';
} else {
    $integrationTable = 'npcintegra_integration';
    $queueTable = 'npcintegra_order_queue';
}

$installer->run(
    "ALTER TABLE  `" . $integrationTable . "` ADD `initial_hour` timestamp NULL DEFAULT CURRENT_TIMESTAMP;
    ALTER TABLE  `" . $queueTable . "` ADD `initial_hour` timestamp NULL DEFAULT CURRENT_TIMESTAMP;"
);

$installer->endSetup();