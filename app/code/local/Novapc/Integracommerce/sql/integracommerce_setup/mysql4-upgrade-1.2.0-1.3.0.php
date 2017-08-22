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
    $tableName = $tablePrefix . 'npcintegra_attributes';
} else {
    $tableName = 'npcintegra_attributes';
}

$installer->run(
    " CREATE TABLE IF NOT EXISTS `" . $tableName . "` (
      `entity_id` int(11) AUTO_INCREMENT PRIMARY KEY,
      `nbm_origin` varchar(245) NULL DEFAULT NULL,
      `nbm_number` varchar(245) NULL DEFAULT NULL,
      `warranty` varchar(245) NULL DEFAULT NULL,
      `brand` varchar(245) NULL DEFAULT NULL,
      `height` varchar(245) NULL DEFAULT NULL,
      `width` varchar(245) NULL DEFAULT NULL,
      `length` varchar(245) NULL DEFAULT NULL,
      `weight` varchar(245) NULL DEFAULT NULL,
      `ean` varchar(245) NULL DEFAULT NULL,
      `ncm` varchar(245) NULL DEFAULT NULL,
      `isbn` varchar(245) NULL DEFAULT NULL
      )"
);

$installer->endSetup();