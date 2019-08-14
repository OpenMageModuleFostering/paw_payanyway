<?php


class Paw_Payanyway_Model_Euroset extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_euroset';
    protected $_paymentMethod	= 'EUROSET';
	protected $_accountId		= 136;
	
    protected $_isInvoice       = true;
}
