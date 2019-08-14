<?php

class Paw_Payanyway_Block_Info extends Mage_Payment_Block_Info
{
    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payanyway/info.phtml');
    }

    /**
     * Returns code of payment method
     *
     * @return string
     */
    public function getMethodCode()
    {
        return $this->getInfo()->getMethodInstance()->getCode();
    }

    /**
     * Build PDF content of info block
     *
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('payanyway/pdf/info.phtml');
        return $this->toHtml();
    }
	
    /**
     * Return payment logo image src
     *
     * @param string $payment Payment Code
     * @return string|bool
     */
    public function getPaymentImageSrc($payment)
    {
		$src = $this->getInfo()->getMethodInstance()->getMethodParam("logotype");
		
        $imageFilename = Mage::getDesign()
            ->getFilename('images' . DS . 'payanyway' . DS . $src, array('_type' => 'skin'));

        if (is_file($imageFilename))
            return $this->getSkinUrl('images/payanyway/' . $src);

        return $this->getSkinUrl('images/payanyway/payanyway.jpg');
    }
	
}
