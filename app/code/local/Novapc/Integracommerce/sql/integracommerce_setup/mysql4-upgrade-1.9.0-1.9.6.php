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
    "TRUNCATE TABLE `npcintegra_integration`;

    ALTER TABLE `npcintegra_integration` MODIFY `status` timestamp NULL DEFAULT NULL;
    
    INSERT INTO `npcintegra_integration` (`integra_model`, `status`) VALUES ('Category', NULL);
    INSERT INTO `npcintegra_integration` (`integra_model`, `status`) VALUES ('Product Insert', NULL);
    INSERT INTO `npcintegra_integration` (`integra_model`, `status`) VALUES ('Product Update', NULL);"
);

$installer->endSetup();