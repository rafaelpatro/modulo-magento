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
    "INSERT INTO `". $tableName ."` 
    (`nbm_origin`, `nbm_number`, `warranty`, `brand`, `height`, `width`, `length`, `weight`, `ean`, `ncm`, `isbn`) 
    VALUES (NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);"
);

$installer->endSetup();