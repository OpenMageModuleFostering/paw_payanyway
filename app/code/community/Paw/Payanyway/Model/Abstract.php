<?php


abstract class Paw_Payanyway_Model_Abstract extends Mage_Payment_Model_Method_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code = 'payanyway_abstract';
    protected $_paymentMethod    = 'abstract';

    protected $_formBlockType = 'payanyway/form';
    protected $_infoBlockType = 'payanyway/info';

    protected $_canUseInternal         = false;
    protected $_canUseForMultishipping = false;
	
    protected $_isInvoice              = true;

    protected $_order;

    const XML_PATH_PAYMENTACTION		= 'payanyway/settings/payment_action';
    const XML_PATH_MNTID				= 'payanyway/settings/mnt_id';
    const XML_PATH_MNTDATAINTEGRITYCODE = 'payanyway/settings/mnt_dataintegrity_code';
    const XML_PATH_MNTTESTMODE			= 'payanyway/settings/mnt_test_mode';
    const XML_PATH_PAYANYWAYLOGIN       = 'payanyway/settings/payanyway_login';
    const XML_PATH_PAYANYWAYPASSWORD    = 'payanyway/settings/payanyway_password';

    /**
     * Get order model
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }

    /**
     * Return url for redirection after order placed
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('payanyway/processing/payment');
    }

    /**
     * Capture payment through Moneybookers api
     *
     * @param Varien_Object $payment
     * @param decimal $amount
     * @return Paw_Payanyway_Model_Abstract
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $payment->setStatus(self::STATUS_APPROVED)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(0);

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param Varien_Object $payment
     * @return Paw_Payanyway_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        $payment->setStatus(self::STATUS_DECLINED)
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(1);

        return $this;
    }

    /**
     * Return url of payment method
     *
     * @return string
     */
    public function getUrl()
    {
		if (!$this->_isInvoice)
		{
			 return "https://".$this->getConfigParam('payment_action')."/assistant.htm"; 
		}
		else
		{
			return Mage::getUrl('payanyway/processing/invoice');;
		}
    }
	
	public function getDataintegrityCode()
	{
        $storeId = Mage::app()->getStore()->getId();
		return Mage::getStoreConfig(self::XML_PATH_MNTDATAINTEGRITYCODE, $storeId);
	}
	
	public function getConfigParam($param)
	{
        $storeId = Mage::app()->getStore()->getId();
		return Mage::getStoreConfig('payanyway/settings/'.$param, $storeId);
		
	}

	public function getMethodParam($param)
	{
        $storeId = Mage::app()->getStore()->getId();
		return Mage::getStoreConfig('payment/'.$this->_code.'/'.$param, $storeId);
		
	}

    /**
     * prepare params array to send it to gateway page via POST
     *
     * @return array
     */
    public function getFormFields()
    {
        $order_id = $this->getOrder()->getRealOrderId();
		
		$mntId = $this->getConfigParam('mnt_id');
		$mntDataintegrityCode = $this->getConfigParam('mnt_dataintegrity_code');
		$amount = number_format($this->getOrder()->getGrandTotal(), 2, '.', '');
		$currencyCode = $this->getOrder()->getOrderCurrencyCode();
		$mntTestMode = $this->getConfigParam('mnt_test_mode');
		$mntSignature = md5($mntId.$order_id.$amount.$currencyCode.$mntTestMode.$mntDataintegrityCode);
		$unitId = $this->getMethodParam('unitid');

        $params = array(
			'MNT_ID'					=> $mntId,
			'MNT_TRANSACTION_ID'		=> $order_id,
			'MNT_AMOUNT'				=> $amount,
			'MNT_CURRENCY_CODE'			=> $currencyCode,
			'MNT_TEST_MODE'				=> $mntTestMode,
			'MNT_SIGNATURE'				=> $mntSignature,
			'MNT_SUCCESS_URL'			=> Mage::getUrl('payanyway/processing/success'),
			'MNT_FAIL_URL'				=> Mage::getUrl('payanyway/processing/cancel')
        );
		if (!empty($unitId))
			$params['paymentSystem.unitId'] = $unitId;
		
		if ($this->_code !== 'payanyway') {
			$params['followup'] = 'true';
			$params['javascriptEnabled'] = 'true';
		}

        return $params;
    }
    /**
     * Get initialized flag status
     * @return true
     */
    public function isInitializeNeeded()
    {
        return true;
    }

    /**
     * Instantiate state and set it to state onject
     * //@param
     * //@param
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
        $stateObject->setIsNotified(false);
    }

    /**
     * Get config action to process initialization
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $paymentAction = $this->getConfigData('payment_action');
        return empty($paymentAction) ? true : $paymentAction;
    }
}
