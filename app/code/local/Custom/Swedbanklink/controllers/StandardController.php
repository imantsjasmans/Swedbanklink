<?php
class Custom_Swedbanklink_StandardController extends Mage_Core_Controller_Front_Action
{
    
    protected function _expireAjax() 
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    private function _getCheckout() 
    {
        return Mage::getSingleton('checkout/session');
    }

    private function _getPaymentMethod()
    {
        return Mage::getSingleton('swedbanklink/standard');
    }

    public function redirectAction()
    {
        $this->_getCheckout()->setSwedbanklinkQuoteId($this->_getCheckout()->getQuoteId());
        $this->getResponse()->setBody($this->getLayout()->createBlock('swedbanklink/standard_redirect')->toHtml());   
        $this->_getCheckout()->unsQuoteId();
        $this->_getCheckout()->unsRedirectUrl();             
    }
    
    
    public function responseAction() {
        
        if($this->_getPaymentMethod()->processRequest($this->getRequest())){
            if ($this->getRequest()->getParam('VK_AUTO') != 'Y') {
                if ($this->_getCheckout()) {
                    if ($this->getRequest()->getParam('VK_SERVICE') == '1101') {
                        $this->_getCheckout()->setQuoteId($this->_getCheckout()->getSwedbanklinkQuoteId(TRUE));
                        $this->_getCheckout()->getQuote()->setIsActive(FALSE)->save();
                        $this->_redirect($this->_getPaymentMethod()->getSuccessPagePath());
                    } else {
                        $this->_getCheckout()->setQuoteId($this->_getCheckout()->getSwedbanklinkQuoteId(TRUE));
                        $this->_getCheckout()->addError(Mage::helper('swedbanklink')->__('The order has been canceled.'));
                        $this->_redirect($this->_getPaymentMethod()->getCartPagePath());
                    }  
                } else {
                    $this->_redirect('');
                }
            }       
        } else {
            $this->_redirect('');
        }          
    }
    

}
