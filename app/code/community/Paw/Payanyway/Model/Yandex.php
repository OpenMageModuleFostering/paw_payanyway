<?php

class Paw_Payanyway_Model_Yandex extends Paw_Payanyway_Model_Abstract
{
    /**
     * unique internal payment method identifier
     */
    protected $_code			= 'payanyway_yandex';
    protected $_paymentMethod	= 'YANDEX';
	
    protected $_isInvoice       = false;
}
