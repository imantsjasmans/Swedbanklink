<?php
class Custom_Swedbanklink_Block_Standard_Error extends Mage_Core_Block_Template
{

    public function __construct()
    { 
        
        $this->setTemplate('swedbanklink/standard/error.phtml');
        parent::__construct();

    }
    
     public function getRealOrderId()
    {
        return Mage::getSingleton('checkout/session')->getLastRealOrderId();
    }
    
    public function getContinueShoppingUrl()
    {
        return Mage::getUrl('onepagecheckout');
    }
    
     public function getPayAgainUrl()
    {
        return Mage::getUrl('swedbanklink/standard/redirect');
    }
    
     public function getCancelUrl()
    {
        return Mage::getUrl('swedbanklink/standard/cancel');
    }
}
