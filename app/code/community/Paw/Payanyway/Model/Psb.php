<?php

class Paw_Payanyway_Model_Psb extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_psb';
    protected $_paymentMethod	= 'PSB';
	
    protected $_isInvoice       = false;
}
