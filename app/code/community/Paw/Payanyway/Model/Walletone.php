<?php

class Paw_Payanyway_Model_Walletone extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_walletone';
    protected $_paymentMethod	= 'WALLETONE';
	
    protected $_isInvoice       = false;
}
