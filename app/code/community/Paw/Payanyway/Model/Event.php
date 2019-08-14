<?php


/**
 * Payanyway notification processor model
 */
class Paw_Payanyway_Model_Event
{
    const PAYANYWAY_STATUS_FAIL = -2;
    const PAYANYWAY_STATUS_CANCEL = -1;
    const PAYANYWAY_STATUS_PENDING = 0;
    const PAYANYWAY_STATUS_SUCCESS = 2;

    /*
     * @param Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     * Event request data
     * @var array
     */
    protected $_eventData = array();

    /**
     * Enent request data setter
     * @param array $data
     * @return Paw_Payanyway_Model_Event
     */
    public function setEventData(array $data)
    {
        $this->_eventData = $data;
        return $this;
    }

    /**
     * Event request data getter
     * @param string $key
     * @return array|string
     */
    public function getEventData($key = null)
    {
        if (null === $key) {
            return $this->_eventData;
        }
        return isset($this->_eventData[$key]) ? $this->_eventData[$key] : null;
    }

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Process cancelation
     */
    public function cancelEvent() {
        try {
            $this->_validateEventData(false);
            $this->_processCancel('Payment was canceled.');
            return Mage::helper('payanyway')->__('The order has been canceled.');
        } catch (Mage_Core_Exception $e) {
            return $e->getMessage();
        } catch(Exception $e) {
            Mage::logException($e);
        }
        return '';
    }

    /**
     * Validate request and return QuoteId
     * Can throw Mage_Core_Exception and Exception
     *
     * @return int
     */
    public function successEvent(){
        $this->_validateEventData(false);
        return $this->_order->getQuoteId();
    }
	
	public function callbackEvent()
	{
        $this->_validateEventData(true);
		$this->_processSale(self::PAYANYWAY_STATUS_SUCCESS, Mage::helper('payanyway')->__('Payment has been completed.'));
		die('SUCCESS');
	}
	
	public function invoiceEvent()
	{
        $params = $this->_validateEventData(false);
		return '';
	}

    /**
     * Processed order cancelation
     * @param string $msg Order history message
     */
    protected function _processCancel($msg)
    {
        $this->_order->cancel();
        $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $msg);
        $this->_order->save();
    }

    /**
     * Processes payment confirmation, creates invoice if necessary, updates order status,
     * sends order confirmation to customer
     * @param string $msg Order history message
     */
    protected function _processSale($status, $msg)
    {
        switch ($status) {
            case self::PAYANYWAY_STATUS_SUCCESS:
                $this->_createInvoice();
                $this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $msg);
                // save transaction ID
                $this->_order->getPayment()->setLastTransId($this->getEventData('MNT_TRANSACTION_ID'));
                // send new order email
                $this->_order->sendNewOrderEmail();
                $this->_order->setEmailSent(true);
                break;
            case self::PAYANYWAY_STATUS_PENDING:
                $this->_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, true, $msg);
                // save transaction ID
                $this->_order->getPayment()->setLastTransId($this->getEventData('MNT_TRANSACTION_ID'));
                break;
        }
        $this->_order->save();
    }

    /**
     * Builds invoice for order
     */
    protected function _createInvoice()
    {
        if (!$this->_order->canInvoice()) {
            return;
        }
        $invoice = $this->_order->prepareInvoice();
        $invoice->register()->capture();
        $this->_order->addRelatedObject($invoice);
    }

    /**
     * Checking returned parameters
     * Thorws Mage_Core_Exception if error
     * @param bool $fullCheck Whether to make additional validations such as payment status, transaction signature etc.
     *
     * @return array  $params request params
     */
    protected function _validateEventData($fullCheck = true)
    {
        // get request variables
        $params = $this->_eventData;
        if (empty($params)) {
            Mage::throwException('Request does not contain any elements.');
        }

        // check order ID
        if (empty($params['MNT_TRANSACTION_ID'])
            || ($fullCheck == false && $this->_getCheckout()->getPayanywayRealOrderId() != $params['MNT_TRANSACTION_ID'])
        ) {
            Mage::throwException('Missing or invalid order ID.');
        }
        // load order for further validation
        $this->_order = Mage::getModel('sales/order')->loadByIncrementId($params['MNT_TRANSACTION_ID']);
        if (!$this->_order->getId()) {
            Mage::throwException('Order not found.');
        }

        if (0 !== strpos($this->_order->getPayment()->getMethodInstance()->getCode(), 'payanyway_')) {
            Mage::throwException('Unknown payment method.');
        }

        // make additional validation
        if ($fullCheck) {
			
			if(isset($params['MNT_ID']) && isset($params['MNT_TRANSACTION_ID']) && isset($params['MNT_OPERATION_ID'])
			   && isset($params['MNT_AMOUNT']) && isset($params['MNT_CURRENCY_CODE']) && isset($params['MNT_TEST_MODE'])
			   && isset($params['MNT_SIGNATURE']))
			{
				$mntDataintegrityCode = $this->_order->getPayment()->getMethodInstance()->getDataintegrityCode();
				$mntSignature = md5("{$params['MNT_ID']}{$params['MNT_TRANSACTION_ID']}{$params['MNT_OPERATION_ID']}{$params['MNT_AMOUNT']}{$params['MNT_CURRENCY_CODE']}{$params['MNT_TEST_MODE']}".$mntDataintegrityCode);
				if ($mntSignature !== $params['MNT_SIGNATURE'])
				{
					die('FAIL');
				}
			}
			else
			{
				die('FAIL');
			}
        }
        return $params;
    }
}
