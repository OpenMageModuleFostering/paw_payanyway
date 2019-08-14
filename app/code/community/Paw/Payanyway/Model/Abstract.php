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

	const STATE_PAYANYWAY_PENDING       = 'pending_payanyway';

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
     * Capture payment through PayAnyWay api
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
     * Check if method is offline one
     *
     * @return boolean
     */
	public function isInvoice()
	{
		return $this->_isInvoice;
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
			return Mage::getUrl('payanyway/processing/invoice');
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

    private static function cleanProductName($value)
    {
        $result = preg_replace('/[^0-9a-zA-Zа-яА-Я ]/ui', '', htmlspecialchars_decode($value));
        $result = trim(mb_substr($result, 0, 20));
        return $result;
    }

    /**
     * prepare params array to send it to gateway page via POST
     *
     * @return array
     */
    public function getFormFields()
    {
        $params = array();

        $order_id = $this->getOrder()->getRealOrderId();
		
		$mntId = $this->getConfigParam('mnt_id');
		$mntDataintegrityCode = $this->getConfigParam('mnt_dataintegrity_code');
		$amount = number_format($this->getOrder()->getGrandTotal(), 2, '.', '');
        $orderItems = $this->getOrder()->getAllItems();

        if ($this->getConfigParam('mnt_kassa') == '1')
        {
            $inventory = [];
            /** @var Mage_Sales_Model_Order_Item $orderItem */
            foreach ($orderItems as $orderItem)
                $inventory[] = [
                    'n' => self::cleanProductName($orderItem->getName()),
                    'p' => number_format($orderItem->getProduct()->getFinalPrice(), 2, '.', ''),
                    'q' => $orderItem->getQtyToInvoice(),
                    't' => '1105'
                ];

            $shippingAmount = $this->getOrder()->getShippingAmount();
            if ($shippingAmount > 0)
                $inventory[] = [
                    'n' => 'Доставка',
                    'p' => number_format($shippingAmount, 2, '.', ''),
                    'q' => '1',
                    't' => '1105'
                ];

            $productsTotal = 0;
            foreach ($inventory AS $item)
                $productsTotal = $productsTotal + floatval($item['p']) * floatval($item['q']);

            if (floatval($productsTotal) != floatval($amount))
            {
                $discountRate = floatval($amount) / floatval($productsTotal);
                $newInventory = [];
                $newInventoryTotal = 0;
                foreach ($inventory AS $item)
                {
                    $item['p'] = number_format(floor(floatval($item['p']) * $discountRate * 100) * 0.01, 2, '.', '');
                    $newInventory[] = $item;
                    $newInventoryTotal = $newInventoryTotal + floatval($item['p']) * floatval($item['q']);
                }
                if ($newInventoryTotal < floatval($amount))
                    $newInventory[] = [
                        'n' => 'Корректировка',
                        'p' => number_format($amount - $newInventoryTotal, 2, '.', ''),
                        'q' => '1',
                        't' => '1105'
                    ];
                $inventory = $newInventory;
            }

            $kassaData = ['customer' => $this->getOrder()->getCustomerEmail(), 'items' => $inventory];

            $jsonData = preg_replace_callback('/\\\\u(\w{4})/', function ($matches)
            {
                return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
            }, json_encode($kassaData));
            $jsonData = str_replace('"', "'", $jsonData);
            $params['MNT_CUSTOM1'] = '1';
            $params['MNT_CUSTOM2'] = $jsonData;
        }

        $currencyCode = $this->getOrder()->getOrderCurrencyCode();
		$mntTestMode = $this->getConfigParam('mnt_test_mode');
		$mntSignature = md5($mntId.$order_id.$amount.$currencyCode.$mntTestMode.$mntDataintegrityCode);
		$unitId = $this->getMethodParam('unitid');

        $params['MNT_ID']				= $mntId;
        $params['MNT_TRANSACTION_ID']	= $order_id;
        $params['MNT_AMOUNT']			= $amount;
        $params['MNT_CURRENCY_CODE']	= $currencyCode;
        $params['MNT_TEST_MODE']		= $mntTestMode;
        $params['MNT_SIGNATURE']		= $mntSignature;
        $params['MNT_SUCCESS_URL']		= Mage::getUrl('payanyway/processing/success');
        $params['MNT_FAIL_URL']			= Mage::getUrl('payanyway/processing/cancel');

		if (!empty($unitId))
			$params['paymentSystem.unitId'] = $unitId;
		
		if (isset($this->_accountId))
			$params['paymentSystem.accountId'] = $this->_accountId;
		
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
	 * Instantiate state and set it to state object
	 * //
	 *
	 * @param string $paymentAction
	 * @param object $stateObject
	 * @return \Mage_Payment_Model_Abstract|void
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
