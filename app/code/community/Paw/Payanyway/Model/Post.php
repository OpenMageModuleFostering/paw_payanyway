<?php


class Paw_Payanyway_Model_Post extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_post';
    protected $_paymentMethod	= 'POST';
	
    protected $_isInvoice       = true;
}
