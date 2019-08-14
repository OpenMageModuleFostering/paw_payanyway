<?php


class Paw_Payanyway_Model_Forward extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_forward';
    protected $_paymentMethod	= 'FORWARD';
	
    protected $_isInvoice       = true;
}
