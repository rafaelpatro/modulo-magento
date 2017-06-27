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
 
$installer->run(
    "ALTER TABLE  `sales_flat_order` ADD  `integracommerce_id` VARCHAR( 255 ) NULL DEFAULT NULL;

    ALTER TABLE `sales_flat_order` ADD UNIQUE INDEX `integracommerce_id_UNIQUE` (`integracommerce_id` ASC);
    
    ALTER TABLE  `sales_flat_quote` ADD  `integracommerce_id` VARCHAR( 255 ) NULL DEFAULT NULL;
    
    ALTER TABLE `sales_flat_quote` ADD UNIQUE INDEX `integracommerce_id_UNIQUE` (`integracommerce_id` ASC);
    
    INSERT INTO `npcintegra_integration` (`integra_model`, `status`) VALUES ('Category', 0);
    
    INSERT INTO `npcintegra_integration` (`integra_model`, `status`) VALUES ('Product', 0);"
);
 
$installer->endSetup();

