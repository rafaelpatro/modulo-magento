DROP TABLE npcintegra_integration;
DROP TABLE npcintegra_order;
DROP TABLE npcintegra_attributes;
DROP TABLE npcintegra_order_queue;
DROP TABLE npcintegra_product_queue;
DROP TABLE npcintegra_sku_attributes;

DELETE FROM `core_config_data` WHERE `path` LIKE 'integracommerce%';
DELETE FROM `core_resource` WHERE `code` = 'integracommerce_setup';
DELETE FROM `eav_attribute` WHERE `attribute_code` like 'integracommerce%';
DELETE FROM `sales_order_status` WHERE `status` = 'delivered';
DELETE FROM `sales_order_status` WHERE `status` = 'shipexception';
DELETE FROM `sales_order_status_label` WHERE `status` = 'delivered';
DELETE FROM `sales_order_status_label` WHERE `status` = 'shipexception';
DELETE FROM `sales_order_status_state` WHERE `status` = 'delivered';
DELETE FROM `sales_order_status_state` WHERE `status` = 'shipexception';

ALTER TABLE `sales_flat_order` DROP COLUMN `integracommerce_id`;
ALTER TABLE `sales_flat_quote` DROP COLUMN `integracommerce_id`;
