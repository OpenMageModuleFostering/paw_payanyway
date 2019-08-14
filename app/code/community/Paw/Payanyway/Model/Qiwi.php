<?php

class Paw_Payanyway_Model_Qiwi extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_qiwi';
    protected $_paymentMethod	= 'QIWI';
	
    protected $_isInvoice       = false;
}
