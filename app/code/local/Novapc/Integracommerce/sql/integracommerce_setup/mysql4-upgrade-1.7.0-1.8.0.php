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
    $tableName = $tablePrefix . 'npcintegra_queue';
} else {
    $tableName = 'npcintegra_queue';
}

$installer->run(
    "CREATE TABLE IF NOT EXISTS `". $tableName . "` (
      `entity_id` int(11) AUTO_INCREMENT PRIMARY KEY,
      `identificator` varchar(245) NULL DEFAULT NULL,
      `sent_json` varchar(600) NOT NULL,
      `created_at` timestamp NULL DEFAULT NULL,
      `last_update` timestamp NULL DEFAULT NULL,
      `type` varchar(100) NULL DEFAULT NULL,
      `done` int(1) NULL DEFAULT NULL
      )"
);

$installer->endSetup();