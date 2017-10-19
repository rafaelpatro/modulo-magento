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
    $requestTable = $tablePrefix . 'npcintegra_request_limit';
} else {
    $requestTable = 'npcintegra_request_limit';
}

try {
    if (!$this->getConnection()->isTableExists($requestTable)) {
        $installer->run(
            "CREATE TABLE IF NOT EXISTS `". $requestTable . "` (
          `entity_id` int(11) AUTO_INCREMENT PRIMARY KEY,
          `name` varchar(245) NOT NULL,
          `minute` int(11) NOT NULL,
          `hour` int(11) NOT NULL
          )"
        );
    }
} catch (Exception $e) {
    Mage::log($e->getMessage(), null, 'Integracommerce_InstallError.log');
}


$installer->endSetup();