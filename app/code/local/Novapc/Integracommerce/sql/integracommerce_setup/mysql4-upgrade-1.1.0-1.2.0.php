<?php
 
$installer = $this;
 
$installer->startSetup();
 
$installer->run("

ALTER TABLE  `sales_flat_order` ADD  `integracommerce_id` VARCHAR( 255 ) NULL DEFAULT NULL;

ALTER TABLE `sales_flat_order` ADD UNIQUE INDEX `integracommerce_id_UNIQUE` (`integracommerce_id` ASC);

ALTER TABLE  `sales_flat_quote` ADD  `integracommerce_id` VARCHAR( 255 ) NULL DEFAULT NULL;

ALTER TABLE `sales_flat_quote` ADD UNIQUE INDEX `integracommerce_id_UNIQUE` (`integracommerce_id` ASC);

INSERT INTO `npcintegra_integration` (`integra_model`, `status`) VALUES ('Category', 0);

INSERT INTO `npcintegra_integration` (`integra_model`, `status`) VALUES ('Product', 0);
 
");
 
$installer->endSetup();

