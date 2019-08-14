<?php

class Paw_Payanyway_ProcessingController extends Mage_Core_Controller_Front_Action
{
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
     * Show orderPlaceRedirect page which contains the Payanyway iframe.
     */
    public function paymentAction()
    {
        try {
            $session = $this->_getCheckout();

            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());
            if (!$order->getId()) {
                Mage::throwException('No order for processing found');
            }
            $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                Mage::helper('payanyway')->__('The customer was redirected to Payanyway.')
            );
            $order->save();

            $session->setPayanywayQuoteId($session->getQuoteId());
            $session->setPayanywayRealOrderId($session->getLastRealOrderId());
            $session->getQuote()->setIsActive(false)->save();
            $session->clear();

            $this->loadLayout();
            $this->renderLayout();
        } catch (Exception $e){
            Mage::logException($e);
            parent::_redirect('checkout/cart');
        }
    }

    /**
     * Action to which the customer will be returned when the payment is made.
     */
    public function successAction()
    {
        $event = Mage::getModel('payanyway/event')
                 ->setEventData($this->getRequest()->getParams());
        try {
            $quoteId = $event->successEvent();
            $this->_getCheckout()->setLastSuccessQuoteId($quoteId);
            $this->_redirect('checkout/onepage/success');
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckout()->addError($e->getMessage());
        } catch(Exception $e) {
            Mage::logException($e);
        }
        $this->_redirect('checkout/cart');
    }

    /**
     * Action to which the customer will be returned if the payment process is
     * cancelled.
     * Cancel order and redirect user to the shopping cart.
     */
    public function cancelAction()
    {
        $event = Mage::getModel('payanyway/event')
                 ->setEventData($this->getRequest()->getParams());
        $message = $event->cancelEvent();

        // set quote to active
        $session = $this->_getCheckout();
        if ($quoteId = $session->getPayanywayQuoteId()) {
            $quote = Mage::getModel('sales/quote')->load($quoteId);
            if ($quote->getId()) {
                $quote->setIsActive(true)->save();
                $session->setQuoteId($quoteId);
            }
        }
        $session->addError($message);
        $this->_redirect('checkout/cart');
    }

	public function callbackAction()
	{
        $event = Mage::getModel('payanyway/event')
            ->setEventData($this->getRequest()->getParams());
		$event->callbackEvent();
	}
	
	public function invoiceAction()
	{
        $event = Mage::getModel('payanyway/event')
            ->setEventData($this->getRequest()->getParams());
		$event->invoiceEvent();

		$this->loadLayout();
		
		$block = $this->getLayout()->getBlock('payanyway_invoice');
		$invoice = $block->getInvoice();
		
		if ($invoice['status'] === 'FAILED') {
			// set quote to active
			$session = $this->_getCheckout();
			if ($quoteId = $session->getPayanywayQuoteId()) {
				$quote = Mage::getModel('sales/quote')->load($quoteId);
				if ($quote->getId()) {
					$quote->setIsActive(true)->save();
					$session->setQuoteId($quoteId);
				}
			}
			$session->addError($invoice['error_message']);
			$this->_redirect('checkout/cart');
		} else {
			$block->setData("invoice", $invoice);
			$this->renderLayout();
		}
	}

    /**
     * Set redirect into responce. This has to be encapsulated in an JavaScript
     * call to jump out of the iframe.
     *
     * @param string $path
     * @param array $arguments
     */
    protected function _redirect($path, $arguments=array())
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('payanyway/redirect')
                ->setRedirectUrl(Mage::getUrl($path, $arguments))
                ->toHtml()
        );
        return $this;
    }
}
