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

if (!$installer->getConnection()->tableColumnExists('npcintegra_integration', 'requested_hour')) {
    $installer->run("ALTER TABLE  `npcintegra_integration` ADD  `requested_hour` int( 11 ) NULL DEFAULT 0");
}

if (!$installer->getConnection()->tableColumnExists('npcintegra_integration', 'requested_day')) {
    $installer->run("ALTER TABLE  `npcintegra_integration` ADD  `requested_day` int( 11 ) NULL DEFAULT 0");
}

if (!$installer->getConnection()->tableColumnExists('npcintegra_integration', 'requested_week')) {
    $installer->run("ALTER TABLE  `npcintegra_integration` ADD  `requested_week` int( 11 ) NULL DEFAULT 0");
}

if (!$installer->getConnection()->tableColumnExists('npcintegra_integration', 'available')) {
    $installer->run("ALTER TABLE  `npcintegra_integration` ADD  `available` int( 1 ) NULL DEFAULT 1");
}

$installer->endSetup();