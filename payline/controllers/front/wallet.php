<?php

class PaylineWalletModuleFrontController extends ModuleFrontController
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
		$this->addJqueryPlugin(array('typewatch', 'fancybox', 'autocomplete'));
		$this->addCSS(__PS_BASE_URI__.'modules/payline/views/css/my-account-'. Tools::substr(_PS_VERSION_, 0, 3) .'.css', 'all');
	}

	public function postProcess(){
		//We check action
		//Add card or create wallet
		if (Tools::isSubmit('createMyWallet'))
		{
			if($iframe = $this->paylineModuleInstance->createWallet())
				$this->context->smarty->assign(array('iframe' => $iframe));
		}

		//Delete Card or update
		if (Tools::getIsset('id_card') && Tools::getValue('id_card')) {
			if (Tools::getIsset('delete') && Tools::getValue('delete')) {
				if (!$this->paylineModuleInstance->deleteCard((int)Tools::getValue('id_card')))
					$this->context->smarty->assign('error', $this->paylineModuleInstance->getL("ERROR:you can't delete this card."));
			} else {
				if ($iframe = $this->paylineModuleInstance->updateWallet((int)Tools::getValue('id_card')))
					$this->context->smarty->assign(array('iframe' => $iframe));
			}
		}

		//Delete Wallet
		if (Tools::isSubmit('deleteMyWallet'))
		{
			if(!$this->paylineModuleInstance->deleteWallet())
				Tools::redirectLink($this->context->link->getModuleLink('payline', 'wallet', array('error' => 'true')));
			else
				Tools::redirectLink($this->context->link->getModuleLink('payline', 'wallet'));
		}

		if(Tools::getValue('success'))
			$this->context->smarty->assign('success', $this->paylineModuleInstance->getL("Operation successfully."));

		if(Tools::getValue('error'))
			$this->context->smarty->assign('error', $this->paylineModuleInstance->getL("ERROR:you can't delete this wallet."));

	}

	protected function assignWallet(){
		if($this->paylineModuleInstance->getWalletId((int)($this->context->cookie->id_customer)))
		{
			$cardData = $this->paylineModuleInstance->getMyCards((int)($this->context->cookie->id_customer));
			if(is_array($cardData))
				$this->context->smarty->assign(array(
								'cardData' => $cardData));
		}

		if(Configuration::get('PAYLINE_WALLET_PERSONNAL_DATA') OR Configuration::get('PAYLINE_SUBSCRIBE_ENABLE'))
			$this->context->smarty->assign(array(
								'updateData' => true));

	}


	/**
	 * Assign template vars related to page content
	 * @see FrontController::initContent()
	 */
	public function initContent()
	{
		$this->setTemplate(Tools::substr(_PS_VERSION_, 0, 3).'/my-wallet.tpl');
		parent::initContent();
		$this->assignWallet();
	}
}
