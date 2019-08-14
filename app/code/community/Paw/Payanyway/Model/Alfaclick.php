<?php

class Paw_Payanyway_Model_Alfaclick extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_alfaclick';
    protected $_paymentMethod	= 'ALFACLICK';
	
    protected $_isInvoice       = false;
}
