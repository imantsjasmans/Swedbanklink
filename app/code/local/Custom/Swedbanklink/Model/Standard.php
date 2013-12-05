<?php

class Custom_Swedbanklink_Model_Standard extends Mage_Payment_Model_Method_Abstract {

  protected $_code          = 'swedbanklink';
  protected $_formBlockType = 'swedbanklink/standard_form';
  protected $_order;

  private function getOrder() 
  {
    if (!$this->_order)
      $this->_order = $this->getInfoInstance()->getOrder();
    return $this->_order;
  }

  public function getOrderPlaceRedirectUrl() 
  {
    return Mage::getUrl('swedbanklink/standard/redirect', array('_secure' => true));
  }

  public function getRedirectUrl() 
  {
    return htmlspecialchars($this->getConfigData('redirect_url'));
  }

  public function getSuccessPagePath()
  {
    return $this->getConfigData('success_page');  
  }

  public function getCartPagePath()
  {
    return $this->getConfigData('cart_page');  
  }

  public function getMerchantId()
  {
    return $this->getConfigData('merchant_id');     
  }

  private function _createSignature($data) 
  {
    $key_file = $this->getConfigData('privatekey_location');
    $key_pwd  = $this->getConfigData('privatekey_password');
    if (file_exists($key_file) && is_readable($key_file)) {
      $fp = fopen($key_file, "r");
      $priv_key = fread($fp, 8192);
      fclose($fp);
      $pkeyid = openssl_get_privatekey($priv_key, $key_pwd);
      openssl_sign($data, $signature, $pkeyid); 
      return base64_encode($signature);
    } else {
      return;
    }
  }

  private function _verifySignature($mac, $signature) 
  {
    $key_file = $this->getConfigData('publickey_location');
    if (file_exists($key_file) && is_readable($key_file)) {
      $cert = file_get_contents($key_file);
      $key = openssl_get_publickey($cert);
      $ok = openssl_verify($mac, $signature, $key);
      openssl_free_key($key);
      return $ok;
    } else {
      return false;
    }
  }

  public function getRedirectionFormData() 
  {
    mb_internal_encoding("utf-8");
    
    // Set variables
    $VK_SERVICE   = '1002';
    $VK_VERSION   = '008';
    $VK_SND_ID    = $this->getMerchantId();
    $VK_STAMP     = $this->getOrder()->getRealOrderId();
    $VK_AMOUNT    = round($this->getOrder()->getGrandTotal(), 2);
    $VK_CURR      = $this->getOrder()->getOrderCurrencyCode();
    $VK_REF       = $this->getOrder()->getRealOrderId();
    $VK_MSG       = Mage::helper('swedbanklink')->__('Order ID: %s', $this->getOrder()->getRealOrderId());
    $VK_RETURN    = Mage::getUrl('swedbanklink/standard/response', array('_secure' => true));
    $VK_ENCODING  = 'UTF-8';
    $VL_LANG      = (in_array($this->getConfigData('language_code'), array('LAT','ENG','RUS')))?$this->getConfigData('language_code'):'ENG';

    // Prapareing data
    $data = str_pad(mb_strlen($VK_SERVICE), 3, '0', STR_PAD_LEFT).$VK_SERVICE;
    $data .= str_pad(mb_strlen($VK_VERSION), 3, '0', STR_PAD_LEFT).$VK_VERSION;
    $data .= str_pad(mb_strlen($VK_SND_ID), 3, '0', STR_PAD_LEFT).$VK_SND_ID;
    $data .= str_pad(mb_strlen($VK_STAMP), 3, '0', STR_PAD_LEFT).$VK_STAMP;
    $data .= str_pad(mb_strlen($VK_AMOUNT), 3, '0', STR_PAD_LEFT).$VK_AMOUNT;
    $data .= str_pad(mb_strlen($VK_CURR), 3, '0', STR_PAD_LEFT).$VK_CURR;
    $data .= str_pad(mb_strlen($VK_REF), 3, '0', STR_PAD_LEFT).$VK_REF;
    $data .= str_pad(mb_strlen($VK_MSG), 3, '0', STR_PAD_LEFT).$VK_MSG;
    
    $VK_MAC = $this->_createSignature($data);

    $this->getOrder()->addStatusToHistory($this->getOrder()->getStatus(),Mage::helper('swedbanklink')->__('Customer was redirected to Swedbank'))->save();

    return array(
      'VK_SERVICE'  => $VK_SERVICE,
      'VK_VERSION'  => $VK_VERSION,
      'VK_SND_ID'   => $VK_SND_ID,
      'VK_STAMP'    => htmlspecialchars($VK_STAMP),
      'VK_AMOUNT'   => $VK_AMOUNT,
      'VK_CURR'     => $VK_CURR,
      'VK_REF'      => $VK_REF,
      'VK_MSG'      => htmlspecialchars($VK_MSG) ,
      'VK_MAC'      => $VK_MAC,
      'VK_RETURN'   => htmlspecialchars($VK_RETURN),
      'VK_ENCODING' => $VK_ENCODING,
      'VL_LANG'     => $VL_LANG,

    );
  }

  public function processRequest($request) 
  {
    mb_internal_encoding("utf-8");
    
    $VK_SERVICE   = $request->getParam('VK_SERVICE');
    $VK_VERSION   = $request->getParam('VK_VERSION');
    $VK_SND_ID    = $request->getParam('VK_SND_ID');
    $VK_REC_ID    = $request->getParam('VK_REC_ID');
    $VK_STAMP     = $request->getParam('VK_STAMP');
    $VK_T_NO      = $request->getParam('VK_T_NO');
    $VK_AMOUNT    = $request->getParam('VK_AMOUNT');
    $VK_CURR      = $request->getParam('VK_CURR');
    $VK_REC_ACC   = $request->getParam('VK_REC_ACC');
    $VK_REC_NAME  = $request->getParam('VK_REC_NAME');
    $VK_SND_ACC   = $request->getParam('VK_SND_ACC');   
    $VK_SND_NAME  = $request->getParam('VK_SND_NAME');
    $VK_REF       = $request->getParam('VK_REF');
    $VK_MSG       = $request->getParam('VK_MSG');
    $VK_T_DATE    = $request->getParam('VK_T_DATE');
    $VK_MAC       = $request->getParam('VK_MAC');
    $VK_AUTO      = $request->getParam('VK_AUTO');

    $ok = false;

    if ($this->getMerchantId() == $VK_REC_ID) {
      if($VK_SERVICE == '1101') {
        $mac = str_pad(mb_strlen($VK_SERVICE), 3, '0', STR_PAD_LEFT).$VK_SERVICE;
        $mac .= str_pad(mb_strlen($VK_VERSION), 3, '0', STR_PAD_LEFT).$VK_VERSION;
        $mac .= str_pad(mb_strlen($VK_SND_ID), 3, '0', STR_PAD_LEFT).$VK_SND_ID;
        $mac .= str_pad(mb_strlen($VK_REC_ID), 3, '0', STR_PAD_LEFT).$VK_REC_ID;
        $mac .= str_pad(mb_strlen($VK_STAMP), 3, '0', STR_PAD_LEFT).$VK_STAMP;
        $mac .= str_pad(mb_strlen($VK_T_NO), 3, '0', STR_PAD_LEFT).$VK_T_NO;
        $mac .= str_pad(mb_strlen($VK_AMOUNT), 3, '0', STR_PAD_LEFT).$VK_AMOUNT;
        $mac .= str_pad(mb_strlen($VK_CURR), 3, '0', STR_PAD_LEFT).$VK_CURR;
        $mac .= str_pad(mb_strlen($VK_REC_ACC), 3, '0', STR_PAD_LEFT).$VK_REC_ACC;
        $mac .= str_pad(mb_strlen($VK_REC_NAME), 3, '0', STR_PAD_LEFT).$VK_REC_NAME;
        $mac .= str_pad(mb_strlen($VK_SND_ACC), 3, '0', STR_PAD_LEFT).$VK_SND_ACC;
        $mac .= str_pad(mb_strlen($VK_SND_NAME), 3, '0', STR_PAD_LEFT).$VK_SND_NAME;
        $mac .= str_pad(mb_strlen($VK_REF), 3, '0', STR_PAD_LEFT).$VK_REF;
        $mac .= str_pad(mb_strlen($VK_MSG), 3, '0', STR_PAD_LEFT).$VK_MSG;
        $mac .= str_pad(mb_strlen($VK_T_DATE), 3, '0', STR_PAD_LEFT).$VK_T_DATE;
        $ok = $this->_verifySignature($mac, base64_decode($VK_MAC));

      } else if ($VK_SERVICE == '1901') {
        $mac = str_pad(mb_strlen($VK_SERVICE), 3, '0', STR_PAD_LEFT).$VK_SERVICE;
        $mac .= str_pad(mb_strlen($VK_VERSION), 3, '0', STR_PAD_LEFT).$VK_VERSION;
        $mac .= str_pad(mb_strlen($VK_SND_ID), 3, '0', STR_PAD_LEFT).$VK_SND_ID;
        $mac .= str_pad(mb_strlen($VK_REC_ID), 3, '0', STR_PAD_LEFT).$VK_REC_ID;
        $mac .= str_pad(mb_strlen($VK_STAMP), 3, '0', STR_PAD_LEFT).$VK_STAMP;
        $mac .= str_pad(mb_strlen($VK_REF), 3, '0', STR_PAD_LEFT).$VK_REF;
        $mac .= str_pad(mb_strlen($VK_MSG), 3, '0', STR_PAD_LEFT).$VK_MSG;
        $ok = $this->_verifySignature($mac, base64_decode($VK_MAC)); 

      } else {
        $ok = false;
      }

    } else {
      $ok = false;
    }

    if ($ok) { // Signature is OK

      /* Save transaction to database */
      $transaction = Mage::getModel('swedbanklink/transaction');
      $data = array(      
        'vk_service'  => $VK_SERVICE,
        'vk_version'  => $VK_VERSION,
        'vk_snd_id'   => $VK_SND_ID,
        'vk_rec_id'   => $VK_REC_ID,
        'vk_stamp'    => $VK_STAMP,
        'vk_t_no'     => $VK_T_NO,
        'vk_amount'   => $VK_AMOUNT,
        'vk_curr'     => $VK_CURR,
        'vk_rec_acc'  => $VK_REC_ACC,
        'vk_rec_name' => $VK_REC_NAME,
        'vk_snd_acc'  => $VK_SND_ACC,
        'vk_snd_name' => $VK_SND_NAME,
        'vk_ref'      => $VK_REF,
        'vk_msg'      => $VK_MSG,
        'vk_t_date'   => $VK_T_DATE,
      );
      $transaction->setData($data);
      $transaction->save();

      /* Change Order status */   
      $order  = Mage::getModel('sales/order')->loadByIncrementId($VK_REF); 
      if ($order->getId() && $order->getState() == Mage_Sales_Model_Order::STATE_NEW  && $order->getPayment()->getMethod() == 'swedbanklink') {                   

        if($VK_SERVICE == '1101') { // Successfull payment
          $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PROCESSING, Mage::helper('swedbanklink')->__('The payment has been accepted.')." ".Mage::helper('swedbanklink')->__('Transaction no.:').$VK_T_NO.", ".Mage::helper('swedbanklink')->__('Amount:').$VK_AMOUNT." ".$VK_CURR);
          $order->save(); 

          if($order->canInvoice()) {
            $invoice = $order->prepareInvoice();
            $invoice->register();
            Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();

            $invoice->sendEmail(true, '');

           // $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_COMPLETE);
            $order->save();
          }
        } else { // payment failed
          $order->registerCancellation()->save();
        }
      }
      return true;

    } else {
      return false;
    }

  }

}
