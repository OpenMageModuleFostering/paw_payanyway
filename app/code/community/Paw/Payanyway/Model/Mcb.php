<?php


class Paw_Payanyway_Model_Mcb extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_mcb';
    protected $_paymentMethod	= 'MCB';
	
    protected $_isInvoice       = true;
}
