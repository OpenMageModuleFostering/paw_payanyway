<?php

class Paw_Payanyway_Model_Moneymail extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_moneymail';
    protected $_paymentMethod	= 'MONEYMAIL';
	
    protected $_isInvoice       = false;
}
