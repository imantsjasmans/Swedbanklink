<?php
class Custom_Swedbanklink_Block_Standard_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {	
        $this->setTemplate('swedbanklink/standard/form.phtml');
        parent::_construct();
    }
}
