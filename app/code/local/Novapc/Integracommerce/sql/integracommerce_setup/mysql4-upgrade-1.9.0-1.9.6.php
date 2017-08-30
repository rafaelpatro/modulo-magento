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
    "TRUNCATE TABLE `". $tableName . "`;

    ALTER TABLE `" . $tableName . "` MODIFY `status` timestamp NULL DEFAULT NULL;
    
    INSERT INTO `" . $tableName . "` (`integra_model`, `status`) VALUES ('Category', NULL);
    INSERT INTO `" . $tableName . "` (`integra_model`, `status`) VALUES ('Product Insert', NULL);
    INSERT INTO `" . $tableName . "` (`integra_model`, `status`) VALUES ('Product Update', NULL);"
);

$installer->endSetup();