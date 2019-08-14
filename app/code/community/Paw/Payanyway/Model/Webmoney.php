<?php

class Paw_Payanyway_Model_Webmoney extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_webmoney';
    protected $_paymentMethod	= 'WEBMONEY';
	
    protected $_isInvoice       = false;
}
