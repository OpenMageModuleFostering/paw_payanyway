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
		$payment_server = $methodInstance->getConfigParam('payment_action');

        switch ($payment_server)
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
	        $totalAmount = $params['MNT_AMOUNT']." ".$params['MNT_CURRENCY_CODE'];
	        $fee = "-";

	        // запрос стоимости и комиссии
	        if (isset($params['paymentSystem_accountId'])) {
		        $transactionRequestType = new MonetaForecastTransactionRequest();
		        $transactionRequestType->payer = $params['paymentSystem_accountId'];
		        $transactionRequestType->payee = $params['MNT_ID'];
		        $transactionRequestType->amount = $params['MNT_AMOUNT'];
		        $transactionRequestType->clientTransaction = $params['MNT_TRANSACTION_ID'];
		        $forecast = $service->ForecastTransaction($transactionRequestType);
		        $totalAmount = number_format($forecast->payerAmount,2,'.','')." ".$forecast->payerCurrency;
		        $fee = number_format($forecast->payerFee,2,'.','')." ".$forecast->payerCurrency;
	        }

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
				$a4->key = 'mailofrussianame';
				$a4->value = $params['additionalParameters_mailofrussiaSenderName'];
				$operationInfo->addAttribute($a4);
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
							  'ctid' => $params['MNT_TRANSACTION_ID'],
							  'transaction' => str_pad($transaction_id, 10, "0", STR_PAD_LEFT),
							  'operation' => $response->transaction,
							  'amount' => $totalAmount,
							  'fee' => $fee,
							  'unitid' => $params['paymentSystem_unitId'],
							  'payment_server' => $payment_server);
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
