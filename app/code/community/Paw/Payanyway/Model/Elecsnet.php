<?php


class Paw_Payanyway_Model_Elecsnet extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_elecsnet';
    protected $_paymentMethod	= 'ELECSNET';
	
    protected $_isInvoice       = true;
}
