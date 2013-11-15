<?php
class Custom_Swedbanklink_Block_Standard_Redirect extends Mage_Core_Block_Template 
{
    protected function _construct()
    {	
    	parent::_construct();
        $this->setTemplate('swedbanklink/standard/redirect.phtml');  
    }

    protected function _getOrder() {
	    if ($this->getOrder())
	    	return $this->getOrder();
	    if ($orderIncrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId())
	    	return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
	}

	public function getForm() {

		$methodInstance = $this->_getOrder()->getPayment()->getMethodInstance();
		$form = new Varien_Data_Form;
		$form->
		setId('swedbanklink_form')->
		setName('swedbanklink_form')->
		setAction($methodInstance->getRedirectUrl())->
		setMethod('post')->
		setUseContainer(TRUE);

		foreach ($methodInstance->getRedirectionFormData() as $name => $value)
			$form->addField($name, 'hidden', array('name' => $name, 'value' => $value));

		return $form;
	}
    
}
