<?php


class Paw_Payanyway_Model_Novoplat extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_novoplat';
    protected $_paymentMethod	= 'NOVOPLAT';
	
    protected $_isInvoice       = true;
}
