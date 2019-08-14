<?php


class Paw_Payanyway_Model_Contact extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_contact';
    protected $_paymentMethod	= 'CONTACT';
	
    protected $_isInvoice       = true;
}
