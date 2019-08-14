<?php


class Paw_Payanyway_PayanywayController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Retrieve Payanyway helper
     *
     * @return Paw_Payanyway_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('payanyway');
    }

}
