<?php

class Paw_Payanyway_Model_Moneta extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_moneta';
    protected $_paymentMethod	= 'MONETA';
	
    protected $_isInvoice       = false;
}
