<?php


class Paw_Payanyway_Model_Comepay extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_comepay';
    protected $_paymentMethod	= 'COMEPAY';
	
    protected $_isInvoice       = true;
}
