<?php
class Custom_Swedbanklink_Model_Mysql4_Transaction extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
        $this->_init('swedbanklink/transaction', 'transaction_id');
    }
}