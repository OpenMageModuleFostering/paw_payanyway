<?php


class Paw_Payanyway_Model_Gorod extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_gorod';
    protected $_paymentMethod	= 'GOROD';
	
    protected $_isInvoice       = true;
}
