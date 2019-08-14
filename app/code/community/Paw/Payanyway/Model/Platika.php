<?php


class Paw_Payanyway_Model_Platika extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_platika';
    protected $_paymentMethod	= 'PLATIKA';
	
    protected $_isInvoice       = true;
}
