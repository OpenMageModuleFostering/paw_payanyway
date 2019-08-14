<?php


class Paw_Payanyway_Block_Invoice extends Mage_Core_Block_Template
{
	public function getPaymentAction()
	{
        $storeId = Mage::app()->getStore()->getId();
        return Mage::getStoreConfig('payanyway/settings/payment_action', $storeId);
	}

	public function getInvoice()
	{
        require_once ('Paw/Payanyway/controllers/Moneta/MonetaWebService.php');

		$params = $this->getRequest()->getParams();
		$order = Mage::getModel('sales/order')->loadByIncrementId($params['MNT_TRANSACTION_ID']);
		
		$methodInstance = $order->getPayment()->getMethodInstance();
		
		$login = $methodInstance->getConfigParam('payanyway_login');
		$password = $methodInstance->getConfigParam('payanyway_password');
		$payment_method = $methodInstance->getCode();
		
        switch ($methodInstance->getConfigParam('payment_action'))
        {
            case "demo.moneta.ru":
                $service = new MonetaWebService("https://demo.moneta.ru/services.wsdl", $login, $password);
                break;
            case "www.payanyway.ru":
                $service = new MonetaWebService("https://www.moneta.ru/services.wsdl", $login, $password);
                break;
        }

        try
        {
            // получить данные счета
            $request = new MonetaInvoiceRequest();
			
			if (isset($params['paymentSystem_accountId']))
				$request->payer = $params['paymentSystem_accountId'];
            $request->payee = $params['MNT_ID'];
            $request->amount = $params['MNT_AMOUNT'];
            $request->clientTransaction = $params['MNT_TRANSACTION_ID'];
			if ($payment_method == 'payanyway_post')
			{
				$operationInfo = new MonetaOperationInfo();
				$a1 = new MonetaKeyValueAttribute();
				$a1->key = 'mailofrussiaindex';
				$a1->value = $params['additionalParameters_mailofrussiaSenderIndex'];
				$operationInfo->addAttribute($a1);
				$a2 = new MonetaKeyValueAttribute();
				$a2->key = 'mailofrussiaregion';
				$a2->value = $params['additionalParameters_mailofrussiaSenderRegion'];
				$operationInfo->addAttribute($a2);
				$a3 = new MonetaKeyValueAttribute();
				$a3->key = 'mailofrussiaaddress';
				$a3->value = $params['additionalParameters_mailofrussiaSenderAddress'];
				$operationInfo->addAttribute($a3);
				$a4 = new MonetaKeyValueAttribute();
				$a5->key = 'mailofrussianame';
				$a5->value = $params['additionalParameters_mailofrussiaSenderName'];
				$operationInfo->addAttribute($a5);
				$request->operationInfo = $operationInfo;
			}
			elseif ($payment_method == 'payanyway_euroset')
			{
				$operationInfo = new MonetaOperationInfo();
				$a1 = new MonetaKeyValueAttribute();
				$a1->key = 'rapidamphone';
				$a1->value = $params['additionalParameters_rapidaPhone'];
				$operationInfo->addAttribute($a1);
				$request->operationInfo = $operationInfo;
			}

            $response = $service->Invoice($request);
			if ($payment_method == 'payanyway_euroset')
			{
				$response1 = $service->GetOperationDetailsById($response->transaction);
				foreach ($response1->operation->attribute as $attr)
				{
					if ($attr->key == 'rapidatid')
					{
						$transaction_id = $attr->value;
					}
				}
			}
			else
			{
				$transaction_id = $response->transaction;
			}
			
			$invoice = array( 'status' => $response->status,
							  'system' => $payment_method,
							  'transaction' => str_pad($transaction_id, 10, "0", STR_PAD_LEFT),
							  'amount' => $params['MNT_AMOUNT']." ".$params['MNT_CURRENCY_CODE'],
							  'unitid' => $params['paymentSystem_unitId']);
        }
        catch (Exception $e)
        {
			$invoice = array( 'status' => 'FAILED',
							  'error_message' => $e->getMessage());
			
			if($order->canCancel()) {
				$order->cancel();
				$order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $e->getMessage());
				$order->save();
			}				
        }
		
        return $invoice;
	}
}
