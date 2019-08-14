<?php

class Paw_Payanyway_Model_Faktura extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_faktura';
    protected $_paymentMethod	= 'FAKTURA';
	
    protected $_isInvoice       = false;
}
