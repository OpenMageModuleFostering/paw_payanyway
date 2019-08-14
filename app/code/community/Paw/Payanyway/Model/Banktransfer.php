<?php


class Paw_Payanyway_Model_Banktransfer extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_banktransfer';
    protected $_paymentMethod	= 'BANKTRANSFER';
	
    protected $_isInvoice       = true;
}
