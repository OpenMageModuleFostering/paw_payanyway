<?php


/**
 * Used in creating options for Yes|No config value selection
 *
 */
class Paw_Payanyway_Model_Paymentaction
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'www.payanyway.ru', 'label'=>'www.payanyway.ru'),
            array('value' => 'demo.moneta.ru', 'label'=>'demo.moneta.ru'),
        );
    }

}
