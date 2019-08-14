<?php


class Paw_Payanyway_Model_Ciberpay extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_ciberpay';
    protected $_paymentMethod	= 'CIBERPAY';
	
    protected $_isInvoice       = true;
}
