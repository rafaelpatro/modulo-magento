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
    $oldQueue = $tablePrefix . 'npcintegra_queue';
    $newQueue = $tablePrefix . 'npcintegra_order_queue';
    $orderTable = $tablePrefix . 'npcintegra_order';
} else {
    $oldQueue = 'npcintegra_queue';
    $newQueue = 'npcintegra_order_queue';
    $orderTable = 'npcintegra_order';
}

$installer->run(
    "RENAME TABLE `" . $oldQueue . "` TO `" . $newQueue . "`;

    ALTER TABLE `" . $newQueue . "` CHANGE `identificator` `integra_model` varchar(50) NULL DEFAULT NULL;
    ALTER TABLE `" . $newQueue . "` CHANGE `sent_json` `status` timestamp NULL DEFAULT NULL;
    ALTER TABLE `" . $newQueue . "` CHANGE `created_at` `requested_hour` int( 11 ) NULL DEFAULT 0;
    ALTER TABLE `" . $newQueue . "` CHANGE `last_update` `requested_day` int( 11 ) NULL DEFAULT 0;
    ALTER TABLE `" . $newQueue . "` CHANGE `type` `requested_week` int( 11 ) NULL DEFAULT 0;
    ALTER TABLE `" . $newQueue . "` CHANGE `done` `available` int( 1 ) NULL DEFAULT 0;
    
    ALTER TABLE  `" . $orderTable . "` ADD  `mage_error` varchar(1000) NULL DEFAULT NULL;
    ALTER TABLE  `" . $orderTable . "` ADD  `integra_error` varchar(1000) NULL DEFAULT NULL;
    
    INSERT INTO `" . $newQueue . "` 
    (`integra_model`, `status`,`requested_hour` , `requested_day`, `requested_week`, `available`) 
    VALUES ('Order', NULL, NULL, NULL, NULL, NULL);
    
    INSERT INTO `" . $newQueue . "` 
    (`integra_model`, `status`,`requested_hour` , `requested_day`, `requested_week`, `available`) 
    VALUES ('Orderid', NULL, NULL, NULL, NULL, NULL);"
);

$installer->endSetup();