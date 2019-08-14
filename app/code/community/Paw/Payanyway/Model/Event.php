<?php


/**
 * Payanyway notification processor model
 */
class Paw_Payanyway_Model_Event
{
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
     * Event response code
     * @var int
     */
	protected $_responseCode = 500;

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
		
		header("Content-type: application/xml");
		echo $this->_getXMLResponse($this->_responseCode);
		exit;
	}
	
	public function invoiceEvent()
	{
        $this->_validateEventData(false);
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
		
		if ($fullCheck == false) {
			if (!$this->_order->getId()) {
				Mage::throwException('Order not found.');
			}

			if (0 !== strpos($this->_order->getPayment()->getMethodInstance()->getCode(), 'payanyway_')) {
				Mage::throwException('Unknown payment method.');
			}
		}

        // make additional validation
        if ($fullCheck && $this->_order->getId())
		{
			if(isset($params['MNT_ID']) && isset($params['MNT_TRANSACTION_ID']) && isset($params['MNT_AMOUNT']) && isset($params['MNT_CURRENCY_CODE']) && isset($params['MNT_TEST_MODE']) && isset($params['MNT_SIGNATURE']))
			{
				if ($this->_checkSignature())
				{
					$amount = (float) $params['MNT_AMOUNT'];
					if ( !isset($params['MNT_COMMAND']) && ($this->_order->getGrandTotal() == $amount) )
					{
						$this->_createInvoice();
						$this->_order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, Mage::helper('payanyway')->__('Payment has been completed.'));
						// save transaction ID
						$this->_order->getPayment()->setLastTransId($this->getEventData('MNT_TRANSACTION_ID'));
						// send new order email
						$this->_order->sendNewOrderEmail();
						$this->_order->setEmailSent(true);
						$this->_order->save();

						$this->_responseCode = 200;
					}
					else
					{
						$status = $this->_order->getStatus();
						switch($params['MNT_COMMAND']) {
							case "CHECK":
								if ($status == Mage_Sales_Model_Order::STATE_PROCESSING || $status == Paw_Payanyway_Model_Abstract::STATE_PAYANYWAY_PENDING)
									$this->_responseCode = 402;
								break;
							case "CANCELLED_CREDIT":
								/*отмена зачисления*/
								$this->_processCancel( Mage::helper('payanyway')->__('Canceled by payment system.') );
								$this->_responseCode = 200;
								break;
							default:
								$this->_responseCode = 200;
								break;
						}
					}
				}
			}
        }
    }
	
	private function _checkSignature()
	{
		$eventData = $this->_eventData;
		$params = '';
		
		if (isset($eventData['MNT_COMMAND'])) $params .= $eventData['MNT_COMMAND'];
		$params .= $eventData['MNT_ID'] . $eventData['MNT_TRANSACTION_ID'];
		if (isset($eventData['MNT_OPERATION_ID'])) $params .= $eventData['MNT_OPERATION_ID'];
		if (isset($eventData['MNT_AMOUNT'])) $params .= $eventData['MNT_AMOUNT'];
		$params .= $eventData['MNT_CURRENCY_CODE'];
		if (isset($eventData['MNT_SUBSCRIBER_ID'])) $params .= $eventData['MNT_SUBSCRIBER_ID'];
		$params .= $eventData['MNT_TEST_MODE'];

		$signature = md5($params . $this->_order->getPayment()->getMethodInstance()->getDataintegrityCode());

		if(strcasecmp($signature, $eventData['MNT_SIGNATURE'] ) == 0) {
			return true;
		}
		return false;
	}
	
	
	private function _getXMLResponse($resultCode)
	{
		$params = $this->_eventData;
		$signature = md5($resultCode . $params['MNT_ID'] . $params['MNT_TRANSACTION_ID'] . $this->_order->getPayment()->getMethodInstance()->getDataintegrityCode());
		$result = '<?xml version="1.0" encoding="UTF-8" ?>';
		$result .= '<MNT_RESPONSE>';
		$result .= '<MNT_ID>' . $params['MNT_ID'] . '</MNT_ID>';
		$result .= '<MNT_TRANSACTION_ID>' . $params['MNT_TRANSACTION_ID'] . '</MNT_TRANSACTION_ID>';
		$result .= '<MNT_RESULT_CODE>' . $resultCode . '</MNT_RESULT_CODE>';
		$result .= '<MNT_SIGNATURE>' . $signature . '</MNT_SIGNATURE>';
		$result .= '</MNT_RESPONSE>';
		return $result;
	}
	
}
