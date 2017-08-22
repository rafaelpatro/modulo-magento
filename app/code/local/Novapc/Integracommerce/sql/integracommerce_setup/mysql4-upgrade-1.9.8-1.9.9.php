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

if (!$installer->getConnection()->tableColumnExists($tableName, 'requested_hour')) {
    $installer->run("ALTER TABLE  `" . $tableName . "` ADD  `requested_hour` int( 11 ) NULL DEFAULT 0");
}

if (!$installer->getConnection()->tableColumnExists($tableName, 'requested_day')) {
    $installer->run("ALTER TABLE  `" . $tableName . "` ADD  `requested_day` int( 11 ) NULL DEFAULT 0");
}

if (!$installer->getConnection()->tableColumnExists($tableName, 'requested_week')) {
    $installer->run("ALTER TABLE  `" . $tableName . "` ADD  `requested_week` int( 11 ) NULL DEFAULT 0");
}

if (!$installer->getConnection()->tableColumnExists($tableName, 'available')) {
    $installer->run("ALTER TABLE  `" . $tableName . "` ADD  `available` int( 1 ) NULL DEFAULT 1");
}

$installer->endSetup();