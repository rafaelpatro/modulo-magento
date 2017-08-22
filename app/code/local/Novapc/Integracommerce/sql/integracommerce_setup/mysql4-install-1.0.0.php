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
    $tableName = $tablePrefix . 'npcintegra_integration';
} else {
    $tableName = 'npcintegra_integration';
}

$installer->run(
    "CREATE TABLE IF NOT EXISTS `" . $tableName . "` (
      `entity_id` int(11) AUTO_INCREMENT PRIMARY KEY,
      `integra_model` varchar(245) NULL DEFAULT NULL,
      `status` int(1) NULL DEFAULT NULL
      )"
);
 
$installer->endSetup();

