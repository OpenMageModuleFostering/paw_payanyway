<?php

class Paw_Payanyway_Model_Dengimail extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_dengimail';
    protected $_paymentMethod	= 'DENGIMAIL';
	
    protected $_isInvoice       = false;
}
