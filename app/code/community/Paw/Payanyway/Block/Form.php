<?php

class Paw_Payanyway_Block_Form extends Mage_Payment_Block_Form
{

    /**
     * Constructor. Set template.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('payanyway/form.phtml');
    }
	
	protected function _prepareLayout() 
	{
        $section = $this->getAction()->getRequest()->getParam('section', false);
        if ($section == 'payanyway') {
			$this->getLayout()
				->getBlock('head')
				->addJs('mage/frontend/validation.js');
		}
		parent::_prepareLayout();
	}

    /**
     * Return payment logo image src
     *
     * @param string $payment Payment Code
     * @return string|bool
     */
    public function getPaymentImageSrc($payment)
    {
		$src = $this->getMethod()->getMethodParam("logotype");
		
        $imageFilename = Mage::getDesign()
            ->getFilename('images' . DS . 'payanyway' . DS . $src, array('_type' => 'skin'));

        if (is_file($imageFilename))
            return $this->getSkinUrl('images/payanyway/' . $src);

        return $this->getSkinUrl('images/payanyway/payanyway.jpg');
    }

}
