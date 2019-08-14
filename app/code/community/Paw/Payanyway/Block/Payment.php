<?php

class Paw_Payanyway_Block_Payment extends Mage_Core_Block_Template
{
    /**
     * Return Payment logo src
     *
     * @return string
     */
    public function getPayanywayLogoSrc()
    {
        return $this->getSkinUrl('images/payanyway/payanyway.png');
    }
	
    /**
     * Return checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Return order instance
     *
     * @return Mage_Sales_Model_Order|null
     */
    protected function _getOrder()
    {
        if ($this->getOrder()) {
            return $this->getOrder();
        } elseif ($orderIncrementId = $this->_getCheckout()->getLastRealOrderId()) {
            return Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
        } else {
            return null;
        }
    }
	
    /**
     * Get Form data by using ogone payment api
     *
     * @return array
     */
    public function getFormData()
    {
		$additionalInformation = $this->_getOrder()->getPayment()->getAdditionalInformation();
        return array_merge($this->_getOrder()->getPayment()->getMethodInstance()->getFormFields(), $additionalInformation);
    }
	
    /**
     * Getting gateway url
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->_getOrder()->getPayment()->getMethodInstance()->getUrl();
    }
}
