<?php
class Custom_Swedbanklink_Model_Mysql4_Transaction_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('swedbanklink/transaction');
    }

    /*
    public function addCompleteFilter($complete)
    {
        $this->getSelect()
            ->where('complete = ?', $complete);
        return $this;
    }
    */
}