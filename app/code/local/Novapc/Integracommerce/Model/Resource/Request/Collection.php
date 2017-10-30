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

class Novapc_Integracommerce_Model_Resource_Request_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('integracommerce/request');
    }

    public function bulkInsert($requestData)
    {
        $rows = array();
        foreach ($requestData as $request) {
            $rows[] = array(
                'name'   => $request['name'],
                'minute' => $request['minute'],
                'hour'   => $request['hour']
            );
        }

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');

        $write->insertOnDuplicate(
            $this->getMainTable(),
            $rows,
            array('name', 'minute', 'hour')
        );

        $write->commit();
    }
}