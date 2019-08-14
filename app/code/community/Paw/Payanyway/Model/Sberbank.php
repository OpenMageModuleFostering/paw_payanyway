<?php


class Paw_Payanyway_Model_Sberbank extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_sberbank';
    protected $_paymentMethod	= 'SBERBANK';
	
    protected $_isInvoice       = true;
}
