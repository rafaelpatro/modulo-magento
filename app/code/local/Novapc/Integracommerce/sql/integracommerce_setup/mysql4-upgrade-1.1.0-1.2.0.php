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
    $tableOrder = $tablePrefix . 'sales_flat_order';
    $tableQuote = $tablePrefix . 'sales_flat_quote';
    $tableIntegra = $tablePrefix . 'npcintegra_integration';
} else {
    $tableOrder = 'sales_flat_order';
    $tableQuote = 'sales_flat_quote';
    $tableIntegra = 'npcintegra_integration';
}

$installer->run(
    "ALTER TABLE  `" . $tableOrder . "` ADD  `integracommerce_id` VARCHAR( 255 ) NULL DEFAULT NULL;

    ALTER TABLE `" . $tableOrder . "` ADD UNIQUE INDEX `integracommerce_id_UNIQUE` (`integracommerce_id` ASC);
    
    ALTER TABLE  `" . $tableQuote . "` ADD  `integracommerce_id` VARCHAR( 255 ) NULL DEFAULT NULL;
    
    ALTER TABLE `" . $tableQuote . "` ADD UNIQUE INDEX `integracommerce_id_UNIQUE` (`integracommerce_id` ASC);
    
    INSERT INTO `" . $tableIntegra . "` (`integra_model`, `status`) VALUES ('Category', 0);
    
    INSERT INTO `" . $tableIntegra . "` (`integra_model`, `status`) VALUES ('Product', 0);"
);
 
$installer->endSetup();

