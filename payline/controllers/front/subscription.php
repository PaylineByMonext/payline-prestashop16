<?php

class PaylineSubscriptionModuleFrontController extends ModuleFrontController
{
	public $auth = true;
	public $authRedirection = 'my-account';
	public $ssl = true;
	private $paylineModuleInstance;

	public function __construct() {
		parent::__construct();
		$this->display_column_left = false;
		$this->display_column_right = false;
		$this->paylineModuleInstance = Module::getInstanceByName('payline');
	}

	public function setMedia(){
		parent::setMedia();
		$this->addCSS(__PS_BASE_URI__.'modules/payline/views/css/my-account-'. Tools::substr(_PS_VERSION_, 0, 3) .'.css', 'all');
	}

	public function postProcess(){
		//We check action
		if(Tools::isSubmit('submitUnsubscribeY'))
		{
			if(Tools::getValue('paymentRecordId'))
			{
				if($this->paylineModuleInstance->disablePaymentRecord(Tools::getValue('paymentRecordId')))
				{
					//It is verified that there is not another payment file linked to this order
					$order_id = $this->paylineModuleInstance->getOrderIdByPaymentRecord(Tools::getValue('paymentRecordId'));

					if($order_id)
						$lastPaymentRecordId = $this->paylineModuleInstance->getLastPaymentRecordByIdCart(Tools::getValue('paymentRecordId'),$order_id);

					if(isset($lastPaymentRecordId))
						$this->paylineModuleInstance->disablePaymentRecord($lastPaymentRecordId);

					if(Tools::getValue('message'))
					{
						$customer = new Customer($cookie->id_customer);
						$mail_var_list = array(
								'{email}' => $customer->email,
								'{message}' => nl2br(stripslashes(Tools::getValue('message'))),
								'{paymentRecordId}' => Tools::getValue('paymentRecordId'));
						Mail::Send($cookie->id_lang, 'unsubscribe', $this->paylineModuleInstance->getL('Unsubscribing message'),
						$mail_var_list, strval(Configuration::get('PS_SHOP_EMAIL')), strval(Configuration::get('PS_SHOP_NAME')), strval(Configuration::get('PS_SHOP_EMAIL')), ($customer->id ? $customer->firstname.' '.$customer->lastname : ''),NULL,NULL,_PS_MODULE_DIR_ . 'payline/mails/');
					}
					Tools::redirectLink($this->context->link->getModuleLink('payline', 'subscription', array('success' => 'true')));
				}
				Tools::redirectLink($this->context->link->getModuleLink('payline', 'subscription', array('error' => 'true')));
			}
			else
				Tools::redirectLink($this->context->link->getModuleLink('payline', 'subscription', array('error' => 'true')));
		}

		if(Tools::isSubmit('submitSuspendY'))
		{
			if(Tools::getValue('paymentRecordId'))
			{
				$idPaylineSubscribe = $this->paylineModuleInstance->verifyPaymentRecordByUserId(Tools::getValue('paymentRecordId'));

				if($idPaylineSubscribe)
				{
					$pendingNumber = $this->paylineModuleInstance->getSumPendingSubscribe($idPaylineSubscribe);
					if($pendingNumber < Configuration::get('PAYLINE_SUBSCRIBE_NUMBER_PENDING') AND Configuration::get('PAYLINE_SUBSCRIBE_NUMBER_PENDING') > 0)
					{
						$paylineVars = array();
						$paylineVars['date'] = Tools::getValue('dateStart');

						$subscribeData = $this->paylineModuleInstance->getPaylineSubscribeData(Tools::getValue('paymentRecordId'));
						if($subscribeData)
						{
							$paylineVars['contractNumber'] 	= $subscribeData[0]['contractNumber'];
							$paylineVars['cardInd'] 		= $subscribeData[0]['cardInd'];
							$paylineVars['id_cart'] 		= $subscribeData[0]['id_cart'];
							$paylineVars['periodicity'] 	= $subscribeData[0]['periodicity'];
						}

						$paymentRecordObj = $this->paylineModuleInstance->getPaymentRecord(Tools::getValue('paymentRecordId'));
						if(isset($paymentRecordObj))
						{
							if(sizeof($paymentRecordObj['billingRecordList']) > 0)
							{
								foreach($paymentRecordObj['billingRecordList']['billingRecord'] as $key=>$value)
								{
									$paylineVars['subscribeNumber'] = sizeof($paymentRecordObj['billingRecordList']['billingRecord']);
									$paylineVars['amount'] = $paymentRecordObj['recurring']['firstAmount'];
									if($paymentRecordObj['billingRecordList']['billingRecord'][$key]->status)
										$paylineVars['subscribeNumber'] = $paylineVars['subscribeNumber']-1;
								}
							}

						}

						$result = $this->paylineModuleInstance->regenerateRecurrentWalletPayment($paylineVars);

						if($result['result']['code'] == "02500" OR $result['result']['code'] == "00000" OR $result['result']['code'] == "02501")
						{
							//It is verified that there is not another payment file linked to this order
							$order_id = $this->paylineModuleInstance->getOrderIdByPaymentRecord(Tools::getValue('paymentRecordId'));

							if($order_id)
								$lastPaymentRecordId = $this->paylineModuleInstance->getLastPaymentRecordByIdCart(Tools::getValue('paymentRecordId'),$order_id);

							//We update paylineSubscribe
							$this->paylineModuleInstance->updatePaylineSubscribe(Tools::getValue('paymentRecordId'),$result['paymentRecordId']);


							if(isset($lastPaymentRecordId) AND $lastPaymentRecordId)
								$lastPaymentRecordObj = $this->paylineModuleInstance->getPaymentRecord($lastPaymentRecordId);

							if(isset($lastPaymentRecordObj))
							{
								if(sizeof($lastPaymentRecordObj['billingRecordList']) > 0)
								{
									$paylineVars['date'] = $this->paylineModuleInstance->getNextDateSubscribe($this->paylineModuleInstance->convertFrenchDate($paylineVars['date']),(int)($paylineVars['subscribeNumber']-1));
									foreach($lastPaymentRecordObj['billingRecordList']['billingRecord'] as $key=>$value)
									{
										$paylineVars['subscribeNumber'] = sizeof($lastPaymentRecordObj['billingRecordList']['billingRecord']);
										$paylineVars['amount'] = $lastPaymentRecordObj['recurring']['firstAmount'];
										if($lastPaymentRecordObj['billingRecordList']['billingRecord'][$key]->status)
											$paylineVars['subscribeNumber'] = $paylineVars['subscribeNumber']-1;
									}
								}

								$resultNextScheduler = $this->paylineModuleInstance->regenerateRecurrentWalletPayment($paylineVars);

								if($resultNextScheduler['result']['code'] == "02500" OR $resultNextScheduler['result']['code'] == "00000" OR $resultNextScheduler['result']['code'] == "02501")
								{
									//We update paylineSubscribe
									$this->paylineModuleInstance->updatePaylineSubscribe($lastPaymentRecordId,$resultNextScheduler['paymentRecordId']);
									if($resultNextScheduler['paymentRecordId'])
										$this->paylineModuleInstance->disablePaymentRecord($lastPaymentRecordId,$resultNextScheduler['paymentRecordId'],true);
								}
							}

							if($result['paymentRecordId'])
							{
								if($this->paylineModuleInstance->disablePaymentRecord(Tools::getValue('paymentRecordId'),$result['paymentRecordId'],true))
									Tools::redirectLink($this->context->link->getModuleLink('payline', 'subscription', array('success' => 'true')));
							}
						}
					}
					else
						Tools::redirectLink($this->context->link->getModuleLink('payline', 'subscription', array('error' => 'true')));
				}
				Tools::redirectLink($this->context->link->getModuleLink('payline', 'subscription', array('error' => 'true')));
			}
			else
				Tools::redirectLink($this->context->link->getModuleLink('payline', 'subscription', array('error' => 'true')));
		}


		if(Tools::getValue('success'))
			$this->context->smarty->assign('success', $this->paylineModuleInstance->getL("Operation successfully."));

		if(Tools::getValue('error'))
			$this->context->smarty->assign('error', $this->paylineModuleInstance->getL("ERROR:you can't update this subscription."));

	}

	protected function assignSubscriptions(){
		$subscribeVars = array();
		//Get Subscribe
		$subscribes = $this->paylineModuleInstance->getPaylineSubscribeByCustomer();
		if($subscribes)
		{
			$i = 0;
			foreach($subscribes as $subscribe)
			{
				$subscribeVars[$i]['id_payline_subscribe'] = $subscribe['id_payline_subscribe'];
				$subscribeVars[$i]['paymentRecordId'] = $subscribe['paymentRecordId'];
				$subscribeVars[$i]['pendingNumber'] = (int)($this->paylineModuleInstance->getSumPendingSubscribe($subscribe['id_payline_subscribe']));
				$subscribeVars[$i]['state'] = $this->paylineModuleInstance->getLastOrderSate($subscribe['id_payline_subscribe']);
				$ordersSubscribe = $this->paylineModuleInstance->getOrderBySubscribeId($subscribe['id_payline_subscribe']);
				if($subscribeVars[$i]['state'] != -1)
				{
					$paymentRecordObj = $this->paylineModuleInstance->getPaymentRecord($subscribe['paymentRecordId']);
					if($paymentRecordObj)
					{
						if(sizeof($paymentRecordObj['billingRecordList']) > 0)
						{
							foreach($paymentRecordObj['billingRecordList']['billingRecord'] as $key=>$value)
							{
								
								if(!$paymentRecordObj['billingRecordList']['billingRecord'][$key]->status  && !isset($subscribeVars[$i]['nextDate']))
								{
									$subscribeVars[$i]['nextDate'] = $this->paylineModuleInstance->convertFrenchDate($paymentRecordObj['billingRecordList']['billingRecord'][$key]->date);
									$subscribeVars[$i]['amount'] = $paymentRecordObj['billingRecordList']['billingRecord'][$key]->amount/100;
									$subscribeVars[$i]['2nextDate'] = $this->paylineModuleInstance->getNextDateSubscribe($subscribeVars[$i]['nextDate']);
								}
							}

							if(!isset($subscribeVars[$i]['nextDate']))
							{
								$subscribeVars[$i]['nextDate'] = '';
								$subscribeVars[$i]['amount'] = '';
							}
						}
						else
						{
							$subscribeVars[$i]['nextDate'] = '';
							$subscribeVars[$i]['amount'] = '';
						}
					}
				}
				else
				{
					$subscribeVars[$i]['nextDate'] = '';
					$subscribeVars[$i]['amount'] = '';
				}

				if(sizeof($ordersSubscribe) > 0  &&  $ordersSubscribe)
				{
					foreach($ordersSubscribe as $orderSubscribe)
					{
						$subscribeVars[$i]['orders'][$orderSubscribe['id_order']] = $orderSubscribe;
						if(!isset($subscribeVars[$i]['currency']))
							$subscribeVars[$i]['currency'] = $orderSubscribe['id_currency'];
						if(!isset($subscribeVars[$i]['startDate']))
							$subscribeVars[$i]['startDate'] = $orderSubscribe['date_add'];
					}
				}
				else
					$subscribeVars[$i]['currency'] = '';
				$i++;
			}

		}

		$this->context->smarty->assign(array(
				'paylineNumberPending' => (int)(Configuration::get('PAYLINE_SUBSCRIBE_NUMBER_PENDING')),
				'subscribes' => $subscribeVars,
				'invoiceAllowed' => (int)(Configuration::get('PS_INVOICE'))));
	}


	/**
	 * Assign template vars related to page content
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		$this->setTemplate(Tools::substr(_PS_VERSION_, 0, 3).'/my-subscribe.tpl');
		parent::initContent();
		$this->assignSubscriptions();
	}
}
