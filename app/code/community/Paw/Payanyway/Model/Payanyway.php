<?php

class Paw_Payanyway_Model_Payanyway extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway';
    protected $_paymentMethod	= 'PAYANYWAY';
	
    protected $_isInvoice       = false;
}
