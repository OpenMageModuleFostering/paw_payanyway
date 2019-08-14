<?php

class Paw_Payanyway_Model_Plastic extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_plastic';
    protected $_paymentMethod	= 'PLASTIC';
	
    protected $_isInvoice       = false;
}
