<?php

/**
 * Payline module for Monext
 * @see http://www.payline.com
 * @author Monext <support@payline.com>
 */

if (!defined('_PS_VERSION_')) {

	exit;

} // if

if (!defined('_PS_BASE_URL_')){

	define('_PS_BASE_URL_', Tools::getShopDomain(true));

}  // if

/**
 * Main module class for Payline payment
 */
class Payline extends PaymentModule {

# CLASS CONSTANTS

	const ERROR_TYPE_BUYER 		= 1;
	const ERROR_TYPE_MERCHANT	= 2;
	const ERROR_TYPE_UNKNOWN	= 3;

	const API_VERSION			= 9;

	const MODE_WEBCASH			= 'webcash';
	const MODE_DIRECT			= 'direct';
	const MODE_WALLET			= 'wallet';
	const MODE_RECURRING		= 'recurring';
	const MODE_SUBSCRIBE		= 'subscribe';
	const MODE_DIRECT_DEBIT		= 'directdebit';

	const RS_VALID_SIMPLE  		= 'valid';
	const RS_VALID_WALLET  		= 'wallet';
	const RS_SUBSCRIBE_REDIRECT = 'rec_redirect';
	const RS_RECURRING_APPROVED = 'rec_approved';
	
	const WIDGET_COLUMN		= 'column';
	const WIDGET_TAB		= 'tab';
	
	private $sBaseUrl;

# PUBLIC VARIABLES

	/**
	 * @var array $aErrors : array get error
	 */
	public $aErrors = null;

# PROTECTED VARIABLES

	/**
	 * Different result sets to simplify testing
	 * @var array
	 */
	protected $aResultSets = array(
		self::RS_VALID_SIMPLE		=> array('00000'),
		self::RS_VALID_WALLET 		=> array('00000', '02500', '02501'), // valid or valid with warning
		self::RS_SUBSCRIBE_REDIRECT => array('02319', '02306'), //02319 Payment cancelled by Buyer - 02306 Operation in progress
		self::RS_RECURRING_APPROVED	=> array('00000', '02500', '02501', '04003'), // valid or valid with warning
	);

	/**
	 * Modes for subscription dates
	 * @var array
	 */
	protected $aDifferedModes = array(
		10 => array('unit' => 'day',   'multiplier' => 1),
		20 => array('unit' => 'day',   'multiplier' => 7),
		30 => array('unit' => 'day',   'multiplier' => 16),
		40 => array('unit' => 'month', 'multiplier' => 1),
		50 => array('unit' => 'month', 'multiplier' => 1.5),
		60 => array('unit' => 'month', 'multiplier' => 3),
		70 => array('unit' => 'month', 'multiplier' => 6), // originally was 700, error?
		80 => array('unit' => 'month', 'multiplier' => 12),
		90 => array('unit' => 'month', 'multiplier' => 24),
	);

	/**
	 * Instance of PaylineSDK object
	 * @var paylineSDK
	 */
	protected $oPaylineSDK = null;

	/**
	 * Default configuration variables, used by install
	 * @var array
	 */
	protected $aDefaultConfig = array(
		'PAYLINE_AUTORIZE_WALLET_CARD'			=> 'CB,VISA,MASTERCARD,AMEX',
		'PAYLINE_MERCHANT_ID'					=> '',
		'PAYLINE_ACCESS_KEY'					=> '',
		'PAYLINE_POS'							=> '',
		'PAYLINE_CONTRACT_NUMBER'				=> '',
		'PAYLINE_CONTRACT_LIST'					=> '',
		'PAYLINE_PROXY_HOST'					=> '',
		'PAYLINE_PROXY_PORT'					=> '',
		'PAYLINE_PROXY_LOGIN'					=> '',
		'PAYLINE_PROXY_PASSWORD'				=> '',
		'PAYLINE_PRODUCTION'					=> 0,
		'PAYLINE_SECURITY_MODE'					=> 'SSL',
		'PAYLINE_NB_DAYS_DIFFERED'				=> '0',
		'PAYLINE_DEBUG_MODE'					=> 'FALSE',
		'PAYLINE_WEB_CASH_TPL_URL'				=> '',
		'PAYLINE_WEB_CASH_CUSTOM_CODE'			=> '',
		'PAYLINE_WEB_CASH_ENABLE'				=> 0,
		'PAYLINE_WEB_CASH_MODE'					=> 'CPT',
		'PAYLINE_WEB_CASH_ACTION'				=> '101',
		'PAYLINE_WEB_CASH_VALIDATION'			=> '',
		'PAYLINE_WEB_CASH_BY_WALLET'			=> '',
		'PAYLINE_WEB_CASH_UX'         			=> '',
		'PAYLINE_RECURRING_TPL_URL'				=> '',
		'PAYLINE_RECURRING_CUSTOM_CODE'			=> '',
		'PAYLINE_RECURRING_ENABLE'				=> 0,
		'PAYLINE_RECURRING_ACTION'				=> '101',
		'PAYLINE_RECURRING_BY_WALLET'			=> '',
		'PAYLINE_RECURRING_NUMBER'				=> '2',
		'PAYLINE_RECURRING_PERIODICITY'			=> '10',
		'PAYLINE_RECURRING_FIRST_WEIGHT'		=> '0',
		'PAYLINE_RECURRING_TRIGGER'				=> '0',
		'PAYLINE_RECURRING_MODE'				=> 'NX',
		'PAYLINE_DIRECT_ENABLE'					=> 0,
		'PAYLINE_DIRECT_ACTION'					=> '101',
		'PAYLINE_DIRECT_VALIDATION'				=> '',
		'PAYLINE_WALLET_ENABLE'					=> 0,
		'PAYLINE_WALLET_ACTION'					=> '101',
		'PAYLINE_WALLET_VALIDATION'				=> '',
		'PAYLINE_WALLET_PERSONNAL_DATA'			=> '',
		'PAYLINE_WALLET_PAYMENT_DATA'			=> '',
		'PAYLINE_WALLET_CUSTOM_CODE'			=> '',
		'PAYLINE_WALLET_TPL_URL'				=> '',
		'PAYLINE_SUBSCRIBE_ENABLE'				=> 0,
		'PAYLINE_SUBSCRIBE_ACTION'				=> '101',
		'PAYLINE_SUBSCRIBE_START_DATE'			=> '1',
		'PAYLINE_SUBSCRIBE_GIFT_ACTIVE'			=> 0,
		'PAYLINE_SUBSCRIBE_AMOUNT_GIFT'			=> '0',
		'PAYLINE_SUBSCRIBE_TYPE_GIFT'			=> 'amount',
		'PAYLINE_SUBSCRIBE_PERIODICITY'			=> '30',
		'PAYLINE_SUBSCRIBE_NUMBER'				=> '2',
		'PAYLINE_SUBSCRIBE_DAY'					=> '1',
		'PAYLINE_SUBSCRIBE_MODE'				=> 'REC',
		'PAYLINE_SUBSCRIBE_NUMBER_PENDING'		=> '0',
		'PAYLINE_DIRDEBIT_ENABLE'				=> 0,
		'PAYLINE_DIRDEBIT_ACTION'				=> '101',
		'PAYLINE_DIRDEBIT_START_DATE'			=> 0,
		'PAYLINE_DIRDEBIT_GIFT_ACTIVE'			=> 0,
		'PAYLINE_DIRDEBIT_AMOUNT_GIFT'			=> '0',
		'PAYLINE_DIRDEBIT_PERIODICITY'			=> '30',
		'PAYLINE_DIRDEBIT_NUMBER'				=> '2',
		'PAYLINE_DIRDEBIT_CONTRACT'				=> '',
		'PAYLINE_DIRDEBIT_DAY'					=> '1',
		'PAYLINE_DIRDEBIT_MODE'					=> 'CPT',
		'PAYLINE_WALLET_TITLE'					=> array(),
		'PAYLINE_WALLET_SUBTITLE'				=> array(),
		'PAYLINE_DIRECT_TITLE'					=> array(),
		'PAYLINE_DIRECT_SUBTITLE'				=> array(),
		'PAYLINE_RECURRING_TITLE'				=> array(),
		'PAYLINE_RECURRING_SUBTITLE'			=> array(),
		'PAYLINE_SUBSCRIBE_TITLE'				=> array(),
		'PAYLINE_SUBSCRIBE_SUBTITLE'			=> array(),
		'PAYLINE_DIRDEBIT_TITLE'				=> array(),
		'PAYLINE_DIRDEBIT_SUBTITLE'				=> array(),
		// Exclusive and product list
		'PAYLINE_SUBSCRIBE_EXCLUSIVE'			=> 0,
		'PAYLINE_DIRDEBIT_EXCLUSIVE'			=> 0,
		'PAYLINE_SUBSCRIBE_PLIST'				=> '',
		'PAYLINE_DIRDEBIT_PLIST'				=> '',
	);

	/**
	 * Array configuration fields (product list)
	 * @var array
	 */
	protected $aArrayConfig =  array('PAYLINE_SUBSCRIBE_PLIST',  'PAYLINE_DIRDEBIT_PLIST');

	/**
	 * Multilang configuration fields (mostly block titles)
	 * @var array
	 */
	protected $aMultilangConfig =  array('PAYLINE_WALLET_TITLE',  'PAYLINE_WALLET_SUBTITLE', 'PAYLINE_DIRECT_TITLE', 'PAYLINE_DIRECT_SUBTITLE', 'PAYLINE_RECURRING_TITLE',
			'PAYLINE_RECURRING_SUBTITLE', 'PAYLINE_SUBSCRIBE_TITLE', 'PAYLINE_SUBSCRIBE_SUBTITLE', 'PAYLINE_DIRDEBIT_TITLE', 'PAYLINE_DIRDEBIT_SUBTITLE');

	/**
	 * Default hooks, used by install
	 * @var array
	 */
	protected $aDefaultHooks = array('header', 'AdminOrder', 'customerAccount', 'myAccountBlock', 'paymentReturn', 'payment', 'updateOrderStatus', 'actionObjectOrderSlipAddAfter');

	/**
	 * Default order statuts
	 * @var array
	 */
	protected $aDefaultOrderStatuses = array(
		'_ID_ORDER_STATE_NX' => array(
			'lang' => array(
				'en' => 'Partially paid with Payline',
				'fr' => 'Payé partiellement via Payline',
			),
			'send_email' 	=> false,
			'color' 		=> '#BBDDEE',
			'hidden' 		=> false,
			'delivery'		=> false,
			'logable' 		=> true,
			'invoice'		=> true,
			'logo'			=> 'paylineLogo',
		),
		'_ID_STATE_ALERT_SCHEDULE' => array(
			'lang' => array(
				'en' => 'Alert scheduler',
				'fr' => 'Alerte échéancier',
			),
			'send_email' 	=> false,
			'color' 		=> '#ffcdcf',
			'hidden' 		=> false,
			'delivery'		=> false,
			'logable' 		=> true,
			'invoice'		=> true,
			'logo'			=> 'paylineLogo',
		),
		'_ID_STATE_ERROR_SCHEDULE' => array(
			'lang' => array(
				'en' => 'Error scheduler',
				'fr' => 'Erreur échéancier',
			),
			'send_email' 	=> false,
			'color' 		=> '#ff9395',
			'hidden' 		=> false,
			'delivery'		=> false,
			'logable' 		=> true,
			'invoice'		=> true,
			'logo'			=> 'paylineLogo',
		),
		'_ID_STATE_AUTO_SIMPLE' => array(
			'lang' => array(
				'en' => 'Authorized payment',
				'fr' => 'Paiement autorisé',
			),
			'send_email' 	=> true,
			'color' 		=> '#dfe0ff',
			'hidden' 		=> false,
			'delivery'		=> false,
			'logable' 		=> true,
			'invoice'		=> true,
			'logo'			=> 'paylineLogo',
			'template'		=> 'payment',
		),
		'_ID_ORDER_STATE_SUBSCRIBE' => array(
			'lang' => array(
					'en' => 'Pending subscription via Payline',
					'fr' => 'En attente d\'echeance d\'abonnement via Payline',
			),
			'send_email' 	=> false,
			'color' 		=> '#caffe8',
			'hidden' 		=> false,
			'delivery'		=> false,
			'logable' 		=> false,
			'invoice'		=> false,
			'logo'			=> 'paylineLogo',
			'template'		=> 'payment',
			'paid'			=> true
		),
		'_ID_ORDER_STATE_DIRDEBIT' => array(
			'lang' => array(
				'en' => 'Scheduled payment',
				'fr' => 'Paiement programmé',
			),
			'send_email' 	=> true,
			'color' 		=> '#caffe8',
			'hidden' 		=> false,
			'delivery'		=> false,
			'logable' 		=> false,
			'invoice'		=> false,
			'logo'			=> 'paylineLogo',
			'template'		=> 'payment',
			'paid'			=> false
		),
		'_ID_ORDER_STATE_PENDING' => array(
			'lang' => array(
				'en' => 'Pending partner',
				'fr' => 'En attente du partenaire',
			),
		    'send_email' 	=> true,
		    'color' 		=> '#4169E1',
		    'hidden' 		=> false,
		    'delivery'		=> false,
		    'logable' 		=> true,
		    'invoice'		=> true,
		    'logo'			=> 'paylineLogo',
		    'template'		=> 'payment'
		)
	);

	/**
	 * Default tabs
	 * @var array
	 */
	protected $aDefaultTabs = array(
		'AdminPayline' => array(
			'lang' => array(
				'en' => 'Payline\'s orders',
				'fr' => 'Commandes Payline',
			),
			'parent' => 10, // id of parent tab (Orders), go in BO check id of concerned tab
			'logo'		=> 'paylineLogo',
		),
	);


	/**
	 *
	 * @var array of <string: mode => array of <string: PaylineSdk property name => string: presta config var name| array (string: presta config var name, strin : query to append)>>
	 */
	protected $aUrlsConfig = array(
		self::MODE_WEBCASH => array(
			'returnURL' 				=> '%%URL%%modules/payline/validation.php',
			'cancelURL' 				=> '%%URL%%modules/payline/validation.php',
			'notificationURL' 			=> '%%URL%%modules/payline/notification.php',
			'customPaymentPageCode' 	=> 'PAYLINE_WEB_CASH_CUSTOM_CODE',
			'customPaymentTemplateURL'	=> 'PAYLINE_WEB_CASH_TPL_URL'
		),
		self::MODE_RECURRING => array(
			'returnURL' 				=> '%%URL%%modules/payline/validation_nx.php',
			'cancelURL' 				=> '%%URL%%modules/payline/validation.php',
			'notificationURL' 			=> '%%URL%%modules/payline/notification.php',
			'customPaymentPageCode' 	=> 'PAYLINE_RECURRING_CUSTOM_CODE',
			'customPaymentTemplateURL' 	=> 'PAYLINE_RECURRING_TPL_URL'
		),
		self::MODE_SUBSCRIBE => array(
			'returnURL' 				=> '%%URL%%modules/payline/validation_subscribe.php',
			'cancelURL' 				=> '%%URL%%modules/payline/validation.php',
			'notificationURL' 			=> '%%URL%%modules/payline/notification.php',
			'customPaymentPageCode' 	=> '',
			'customPaymentTemplateURL' 	=> ''
		),
		self::MODE_DIRECT_DEBIT => array(
			'returnURL' 				=> '%%URL%%modules/payline/validation.php',
			'cancelURL' 				=> '%%URL%%modules/payline/validation.php',
			'notificationURL' 			=> '%%URL%%modules/payline/notification.php',
			'customPaymentPageCode' 	=> '',
			'customPaymentTemplateURL' 	=> ''
		),
		self::MODE_DIRECT => array(
			'returnURL' 				=> '%%URL%%modules/payline/validation.php',
			'cancelURL' 				=> '%%URL%%modules/payline/validation.php',
			'notificationURL' 			=> '%%URL%%modules/payline/notification.php',
			'customPaymentPageCode' 	=> '',
			'customPaymentTemplateURL' 	=> ''
		),
		self::MODE_WALLET => array(
			'returnURL' 				=> array('%%URL%%modules/payline/validation.php', '?walletInterface=true'),
			'cancelURL' 				=> array('%%URL%%modules/payline/validation.php', '?walletInterface=true'),
			'notificationURL' 			=> array('%%URL%%modules/payline/notification.php', '?walletInterface=true'),
			'customPaymentPageCode' 	=> 'PAYLINE_WALLET_CUSTOM_CODE',
			'customPaymentTemplateURL' 	=> 'PAYLINE_WALLET_TPL_URL'
		)
	);
	

# CLASS METHODS

	# MAGIC METHODS

	/**
	 * Class constructor
	 * @refactor
	 */
	public function __construct() {
		require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'paylineSDK.php');

		$this->name 		= 'payline';
		$this->tab 			= 'payments_gateways';
		$this->version 		= paylineSDK::KIT_VERSION;
		$this->author 		= 'Monext';
		// $this->need_instance = 0;

		$this->controllers = array('wallet', 'subscription');
		parent::__construct();

		$this->displayName 	= 'Payline';
		$this->description 	= $this->l('Pay with secure payline gateway');
		$this->confirmUninstall = $this->l('Are you sure you want to remove it ? Be careful, all your configuration and your data will be lost');
		$this->warning=$this->verifyConfiguration();
		
		$forceSSL = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));
		$this->sBaseUrl = ($forceSSL ? 'https://' . $this->context->shop->domain_ssl : 'http://' . $this->context->shop->domain) . $this->context->shop->getBaseURI();
		// update module version

		Shop::addTableAssociation('payline_card',  array('type' => 'shop'));

	}

	# INSTALL / UNINSTALL

	/**
	 * install() method installs all mandatory structure (DB or Files) => sql queries and update values and hooks registered
	 * @refactor
	 * @return bool
	 */
	public function	install() {

		return (parent::install() &&
				$this->installSql() &&
				$this->installConfig() &&
				$this->installTabs() &&
				$this->installOrderStatuses()
				);

	}

	/**
	 * @refactor
	 * @return boolean
	 */
	public function	uninstall()	{

		return parent::uninstall() &&
				$this->uninstallSql() &&
				// $this->uninstallOrderStatuses() &&
				$this->uninstallConfig() &&
				$this->uninstallTabs();

	}//uninstall

	/**
	 * Executes queries in install/sql/uninstall.sql
	 * @return boolean True on success
	 */
	protected function uninstallSql(){

		$sFilePath = implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'install', 'sql', 'uninstall.sql'));

		return $this->executeSqlFile($sFilePath);

	}

	/**
	 * @refactor
	 */
	protected function uninstallOrderStatuses(){
		// set return execution
		$bReturn = true;

		// loop on each admin tab
		foreach ($this->aDefaultOrderStatuses as $sConfigName => $aStatus) {
			// get ID
			$iStateId = Configuration::get('PAYLINE' . $sConfigName);

			if (!empty($iStateId)) {
				// instantiate
				$oState = new OrderState($iStateId);

				// use case - check delete
				if (false == $oState->delete()) {
					$bReturn = false;
				}
				else {
					if (!defined('_PS_IMG_DIR')) {
						define('_PS_IMG_DIR', _PS_ROOT_DIR_ . '/img/');
					}
					if (file_exists(_PS_IMG_DIR_ . 'os/' . (int)$iStateId . '.gif')) {
						@unlink(_PS_IMG_DIR_ . 'os/' . (int)$iStateId . '.gif');
					}
				}
				unset($oState,$iStateId);
			}
		}

		return $bReturn;

	}

	/**
	 * @refactor
	 */
	protected function uninstallConfig(){

		// set return execution
		$bReturn = true;

		// delete global config
		foreach ($this->aDefaultConfig as $sKeyName => $mVal) {
			if (!Configuration::deleteByName($sKeyName)) {
				$bReturn = false;
			}
		}


		return $bReturn;


	}

	/**
	 * @refactor
	 */
	protected function uninstallTabs(){
		// set return execution
		$bReturn = true;

		// loop on each admin tab
		foreach ($this->aDefaultTabs as $sAdminClassName => $aTab) {
			// get ID
			$iTabId = Tab::getIdFromClassName($sAdminClassName);

			if (!empty($iTabId)) {
				// instantiate
				$oTab = new Tab($iTabId);

				// use case - check delete
				if (false == $oTab->delete()) {
					$bReturn = false;
				}
				else {
					if (!defined('_PS_IMG_DIR')) {
						define('_PS_IMG_DIR', _PS_ROOT_DIR_ . '/img/');
					}
					if (file_exists(_PS_IMG_DIR . 't/' . $sAdminClassName . '.gif')) {
						@unlink(_PS_IMG_DIR . 't/' . $sAdminClassName . '.gif');
					}
				}
				unset($oTab);
			}
		}

		return $bReturn;

	}

   /**
    * Reads and executes contents of sql file
	* @param string $sFilePath Path to sql file, with queries separated by ';'
	* @return boolean True if all queries executed succesfully, false otherwise
	*/
	protected function executeSqlFile($sFilePath){

		$sSqlString = (file_exists($sFilePath) && is_readable($sFilePath) ? (string)file_get_contents($sFilePath) : '');

		$sSqlString = str_replace('%%PREFIX%%', _DB_PREFIX_, $sSqlString);

		$aQueries = preg_split('#;\s*[\r\n]+#', $sSqlString);

		foreach (array_filter(array_map('trim', $aQueries)) as $sQuery){

			$mResult = Db::getInstance()->execute($sQuery);

            if ($mResult === false){

            	Logger::addLog('Query FAILED ' . $sQuery);

            	return false;

           } // if

		} // foreach

        return true;

	} // executeSqlFile

	/**
	 * Installs sql files
	 * @return boolean True if all files were executed successfully
	 */
	protected function installSql(){

		foreach (array('uninstall', 'install') as $sSqlFileName) {

			$sFilePath = implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'install', 'sql', $sSqlFileName . '.sql'));

			if (!$this->executeSqlFile($sFilePath)){

				return false;

			} // if

		} // foreach

		return true;

	}

	/**
	 * @refactor
	 */
	protected function installOrderStatuses(){
		// declare return
		$bReturn = true;


		$oState = new OrderState();

		// set variables
		$aTmpLang = array();

		// get available languages
		$aLangs = Language::getLanguages(true);

		$aTmpMailLang = array();
		// loop on each admin tab
		foreach ($this->aDefaultOrderStatuses as $sConfigName => $aStatus) {
			foreach ($aLangs as $aLang) {
				$aTmpLang[$aLang['id_lang']] = array_key_exists($aLang['iso_code'], $aStatus['lang'])? $aStatus['lang'][$aLang['iso_code']] : $aStatus['lang']['en'];
				if(isset($aStatus['template']))
					$aTmpMailLang[$aLang['id_lang']] = $aStatus['template'];
			}
			
			// Update state instead of creating a new one
			$currentIdOrderState = Configuration::get('PAYLINE' . $sConfigName);
			
			$oState 				= new OrderState($currentIdOrderState);
			$oState->name          	= $aTmpLang;
			$oState->send_email 	= $aStatus['send_email'];
			$oState->color			= $aStatus['color'];
			$oState->hidden 		= $aStatus['hidden'];
			$oState->delivery 		= $aStatus['delivery'];
			$oState->logable 		= $aStatus['logable'];
			$oState->invoice 		= $aStatus['invoice'];
			$oState->template 		= $aTmpMailLang;


			// save admin tab
			if (false == $oState->save()) {
				$bReturn = false;
			}

			// use case - copy icon Status
			if (file_exists(_PS_MODULE_DIR_ . $this->name . '/img/' . $aStatus['logo'] . '.gif')) {
				@copy(_PS_MODULE_DIR_ . $this->name . '/img/' . $aStatus['logo'] . '.gif', _PS_IMG_DIR_ . 'os/'.(int)$oState->id. '.gif');
			}

			if (!Configuration::updateValue('PAYLINE' . $sConfigName, (int)$oState->id)) {
				$bReturn = false;
			}

			unset($oState);
		}

		// destruct

		return $bReturn;
	}

	/**
	 * @refactor
	 */
	protected function installTabs(){
		// declare return
		$bReturn = true;

		$oTab = new Tab();

		// set variables
		$aTmpLang = array();

		// get available languages
		$aLangs = Language::getLanguages(true);

		// loop on each admin tab
		foreach ($this->aDefaultTabs as $sAdminClassName => $aTab) {
			foreach ($aLangs as $aLang) {
				$aTmpLang[$aLang['id_lang']] = array_key_exists($aLang['iso_code'], $aTab['lang'])? $aTab['lang'][$aLang['iso_code']] : $aTab['lang']['en'];
			}
			$oTab->name          = $aTmpLang;
			$oTab->class_name    = $sAdminClassName;
			$oTab->module        = $this->name;
			$oTab->id_parent     = $aTab['parent'];

			// use case - copy icon tab
			if (file_exists(_PS_MODULE_DIR_ . $oTab->module . '/img/' . $aTab['logo'] . '.gif')) {
				@copy(_PS_MODULE_DIR_ . $oTab->module . '/img/' . $aTab['logo'] . '.gif', _PS_IMG_DIR_ . 't/' . $sAdminClassName . '.gif');
			}

			// save admin tab
			if (false == $oTab->save()) {
				$bReturn = false;
			}
		}

		return $bReturn;
	}

	/**
	 * @refactor
	 */
	protected function installConfig(){

		// declare return
		$bReturn = true;

		// update each constant used in module admin & display
		foreach ($this->aDefaultConfig as $sKeyName => $mVal) {
			if (!Configuration::updateValue($sKeyName, $mVal)) {
				$bReturn = false;
			}
		}
		// register each hooks
		foreach ($this->aDefaultHooks as  $sName) {
			if (!$this->registerHook($sName)) {
				$bReturn = false;
			}
		}


		return $bReturn;

	}

	# HOOKS

	/**
	 * Adds necessary css files
	 */
	public function hookHeader() {

		$this->context->controller->addCSS($this->_path . 'css/payline.css', 'all');
		if(in_array(Configuration::get('PAYLINE_WEB_CASH_UX'),array(Payline::WIDGET_COLUMN,Payline::WIDGET_TAB))){
		    if(Configuration::get('PAYLINE_PRODUCTION') == '1'){
		        $this->context->controller->addCSS(paylineSDK::PROD_WDGT_CSS, 'all');
		        $this->context->controller->addJS(paylineSDK::PROD_WDGT_JS);
		    }else{
		        $this->context->controller->addCSS(paylineSDK::HOMO_WDGT_CSS, 'all');
		        $this->context->controller->addJS(paylineSDK::HOMO_WDGT_JS);
		    }
		}

	} // hookHeader

	/**
	 * @refactor
	 * @param unknown_type $params
	 * @return Ambigous <Ambigous, string, void, string>
	 */
	public function hookPayment($params)
	{
		$customer 				= new Customer(intval($this->context->cookie->id_customer));

		$paymentFrom = '<form action="'.$this->_path.'redirect.php" method="post" name="WebPaymentPayline" id="WebPaymentPayline" class="payline-form">';
		$paymentFrom .= '<input type="hidden" name="contractNumber" id="contractNumber" value="" />';
		$paymentFrom .= '<input type="hidden" name="type" id="type" value="" />';
		$paymentFrom .= '<input type="hidden" name="mode" id="mode" value="" /></form>';

		//We retrieve all contract list
		$contracts = $this->getPaylineContracts();

		$cards = array();
		$cardsNX = array();
		$directDebit = array();
		if (Configuration::get('PAYLINE_DIRDEBIT_ENABLE') && Configuration::get('PAYLINE_DIRDEBIT_CONTRACT') != '')
			$directDebit[] = array('contract' => Configuration::get('PAYLINE_DIRDEBIT_CONTRACT'), 'type' => 'SDD', 'logo' => 'http://demo.payline.com/~product/logos/p_logo_sdd.png');

		if ($contracts) {

			$cardAuthorizeByWalletNx = explode(',',Configuration::get('PAYLINE_AUTORIZE_WALLET_CARD'));

			foreach($contracts as $contract){

				if($contract['primary']){
					// Cofidis 3xCB should be shown only between 100 and 1000 euros 
					if (in_array($contract['type'],array('3XCB_L','3XCB'))) {
						$orderTotal = round($this->context->cart->getOrderTotal()*100);
						if ($orderTotal > 10000 && $orderTotal < 100000){
							$cards[] = array('contract' => $contract['contract'], 'type' => $contract['type'], 'label' => $contract['label'], 'logo' => $contract['logo']);
						} else {
							$this->log("Cart total = $orderTotal => contract ".$contract['contract']." (type ".$contract['type'].") is not shown in cash web payment");
						}
					} else {
						$cards[] = array('contract' => $contract['contract'], 'type' => $contract['type'], 'label' => $contract['label'], 'logo' => $contract['logo']);
					}
					if(in_array($contract['type'],$cardAuthorizeByWalletNx)){

						$cardsNX[] = array('contract' => $contract['contract'], 'type' => $contract['type'], 'logo' => $contract['logo']);

					} // if

				} // if

			} // foreach

		} // if

		$cardData = Configuration::get('PAYLINE_WALLET_ENABLE') ? $this->getMyCards($customer->id) : '';
		
		$paylineRecurring = false;
		if(Configuration::get('PAYLINE_RECURRING_ENABLE') && $this->context->cart->getOrderTotal() > Configuration::get('PAYLINE_RECURRING_TRIGGER')){
			$paylineRecurring = true;
		}
		
		$this->context->smarty->assign(array(
                'sBaseUrl'			=> $this->sBaseUrl,
				'cards'				=> $cards,
				'cardsNX'			=> $cardsNX,
				'directDebitContract'=> $directDebit,
				'cardData' 			=> $cardData,
				'payline' 			=> $paymentFrom,
				'paylineWebcash' 	=> Configuration::get('PAYLINE_WEB_CASH_ENABLE'),
				'paylineWidget' 	=> 0,
				'paylineRecurring'  => $paylineRecurring, //CB/VISA/MASTERCARD/AMEX
				'paylineDirect' 	=> Configuration::get('PAYLINE_DIRECT_ENABLE'), //CB/VISA/MASTERCARD/AMEX
				'paylineWallet' 	=> Configuration::get('PAYLINE_WALLET_ENABLE'), //CB/VISA/MASTERCARD/AMEX
				'paylineSubscribe' 	=> Configuration::get('PAYLINE_SUBSCRIBE_ENABLE'), //CB/VISA/MASTERCARD/AMEX
				'paylineDirDebit' 	=> Configuration::get('PAYLINE_DIRDEBIT_ENABLE'), // SDD
				'paylineDirDebitNb'	=> Configuration::get('PAYLINE_DIRDEBIT_NUMBER'), // SDD
				'paylineProduction' => (int)Configuration::get('PAYLINE_PRODUCTION')
			)
		);
		
		if (in_array(Configuration::get('PAYLINE_WEB_CASH_UX'),array(Payline::WIDGET_COLUMN,Payline::WIDGET_TAB))) {
		    $this->context->smarty->assign(array('paylineWidget' => 1));
		    $this->context->smarty->assign(array('widgetTemplate' => Configuration::get('PAYLINE_WEB_CASH_UX')));
		    	
		    // call to doWebPayment is done here in order to provide token for widget div
		    $vars = array(
		        'contractNumber'	=> Configuration::get('PAYLINE_CONTRACT_NUMBER'),
		        'type'				=> '',
		        'mode'				=> 'webCash',
		        'widget'			=> 1
		    );
		    $doWebPaymentRequest = $this->doWebPaymentRequest($vars);
		    $result = $this->parseWsResult($this->getPaylineSDK()->doWebPayment($doWebPaymentRequest));
		    if(isset($result) && $result['result']['code'] == '00000'){
		        $this->saveToken((int)$this->context->cart->id, $result['token']);
		        $this->context->smarty->assign(array('widgetToken' => $result['token']));
		    }
		    elseif(isset($result)) {
		        echo 'ERROR : '.$result['result']['code']. ' '.$result['result']['longMessage'].' <BR/>';
		    }
		}

		if (Configuration::get('PAYLINE_SUBSCRIBE_ENABLE')) {
			if (!Configuration::get('PAYLINE_SUBSCRIBE_EXCLUSIVE')) {
				// Non-Exclusive method, check if products in cart are correct
				$exclusiveProductList = $this->getProductList('PAYLINE_SUBSCRIBE_PLIST', true);
				if (is_array($exclusiveProductList) && sizeof($exclusiveProductList)) {
					$cartProductList = $this->context->cart->getProducts();
					$cartIntegrity = true;
					if (is_array($cartProductList)) {
						foreach ($cartProductList as $cartProduct) {
							if (!in_array($cartProduct['id_product'], $exclusiveProductList)) {
								$cartIntegrity = false;
								break;
							}
						}
					}
					if (!$cartIntegrity) {
						// We have to disable this method, no product are eligible
						$this->context->smarty->assign(array(
							'paylineSubscribe' => false,
						));
					}
				}
			} else {
				// Exclusive method, check if products in cart are correct
				$cartProductList = $this->context->cart->getProducts();
				$exclusiveProductList = $this->getProductList('PAYLINE_SUBSCRIBE_PLIST', true);
				$cartIntegrity = false;
				$cartFullIntegrity = true;
				$breakingIntegrityList = array();
				// We have at least, one product OK
				if (is_array($cartProductList)) {
					foreach ($cartProductList as $cartProduct) {
						if (in_array($cartProduct['id_product'], $exclusiveProductList)) {
							$cartIntegrity = true;
						} else {
							$cartFullIntegrity = false;
							$breakingIntegrityList[] = $cartProduct['id_product'];
						}
					}
				}
				if (!$cartIntegrity) {
					// We have to disable this method, no product are eligible
					$this->context->smarty->assign(array(
						'paylineSubscribe' => false,
					));
				} else if (!$cartFullIntegrity) {
					// We have to disable payment via Payline, wrong cart content
					$breakingProductList = array();
					foreach ($breakingIntegrityList as $idProduct) {
						$product = new Product($idProduct, false, $this->context->cookie->id_lang);
						$breakingProductList[] = $product->name;
					}
					$this->context->smarty->assign(array(
						'paylineWrongIntegrity' => true,
						'paylineBreakingIntegrityList' => $breakingProductList,
					));
				} else if ($cartIntegrity && $cartFullIntegrity) {
					// We have to hide any other methods...
					$this->context->smarty->assign(array(
						'paylineWebcash' 	=> false,
						'paylineRecurring'  => false,
						'paylineDirect' 	=> false,
						'paylineWallet' 	=> false,
					));
				}
			}
		}

		if (Configuration::get('PAYLINE_DIRDEBIT_ENABLE')) {
			if (!Configuration::get('PAYLINE_DIRDEBIT_EXCLUSIVE')) {
				// Non-Exclusive method, check if products in cart are correct
				$exclusiveProductList = $this->getProductList('PAYLINE_DIRDEBIT_PLIST', true);
				if (is_array($exclusiveProductList) && sizeof($exclusiveProductList)) {
					$cartProductList = $this->context->cart->getProducts();
					$cartIntegrity = true;
					if (is_array($cartProductList)) {
						foreach ($cartProductList as $cartProduct) {
							if (!in_array($cartProduct['id_product'], $exclusiveProductList)) {
								$cartIntegrity = false;
								break;
							}
						}
					}
					if (!$cartIntegrity) {
						// We have to disable this method, no product are eligible
						$this->context->smarty->assign(array(
							'paylineDirDebit' => false,
						));
					}
				}
			} else {
				// Exclusive method, check if products in cart are correct
				$cartProductList = $this->context->cart->getProducts();
				$exclusiveProductList = $this->getProductList('PAYLINE_DIRDEBIT_PLIST', true);
				$cartIntegrity = false;
				$cartFullIntegrity = true;
				$breakingIntegrityList = array();
				// We have at least, one product OK
				if (is_array($cartProductList)) {
					foreach ($cartProductList as $cartProduct) {
						if (in_array($cartProduct['id_product'], $exclusiveProductList)) {
							$cartIntegrity = true;
						} else {
							$cartFullIntegrity = false;
							$breakingIntegrityList[] = $cartProduct['id_product'];
						}
					}
				}
				if (!$cartIntegrity) {
					// We have to disable this method, no product are eligible
					$this->context->smarty->assign(array(
						'paylineDirDebit' => false,
					));
				} else if (!$cartFullIntegrity) {
					// We have to disable payment via Payline, wrong cart content
					$breakingProductList = array();
					foreach ($breakingIntegrityList as $idProduct) {
						$product = new Product($idProduct, false, $this->context->cookie->id_lang);
						$breakingProductList[] = $product->name;
					}
					$this->context->smarty->assign(array(
						'paylineWrongIntegrity' => true,
						'paylineBreakingIntegrityList' => $breakingProductList,
					));
				} else if ($cartIntegrity && $cartFullIntegrity) {
					// We have to hide any other methods...
					$this->context->smarty->assign(array(
						'paylineWebcash' 	=> false,
						'paylineRecurring'  => false,
						'paylineDirect' 	=> false,
						'paylineWallet' 	=> false,
					));
				}
			}
		}


		foreach (array_keys($this->aDefaultConfig) as $sConfigKeyName) {

			if (in_array($sConfigKeyName, $this->aMultilangConfig)){

				$this->context->smarty->assign($sConfigKeyName, Configuration::get($sConfigKeyName, $this->context->cookie->id_lang));

			} else {

				$this->context->smarty->assign($sConfigKeyName, Configuration::hasKey($sConfigKeyName) ? Configuration::get($sConfigKeyName) : $this->aDefaultConfig[$sConfigKeyName]);

			} // if

		}

		$this->removePaylineGifts($this->context->cart);

		$orderTotal	= $this->context->cart->getOrderTotal();

		if ( Configuration::get('PAYLINE_SUBSCRIBE_ENABLE') && Configuration::get('PAYLINE_SUBSCRIBE_GIFT_ACTIVE')){

			$lastOrderTotal = $orderTotal;

			$orderTotal = $orderTotal - $this->getSubscribeReductionAmount($this->context->cart);

		}

		if($paylineRecurring){
			$nxAmount = 0;
			$firstNxAmount = 0;
			if(Configuration::get('PAYLINE_RECURRING_FIRST_WEIGHT')>0){
				$firstNxAmount = round($this->context->cart->getOrderTotal()*Configuration::get('PAYLINE_RECURRING_FIRST_WEIGHT'));
				$nxAmount = round(($this->context->cart->getOrderTotal()*100-$firstNxAmount)/(Configuration::get('PAYLINE_RECURRING_NUMBER')-1));
				$delta = $this->context->cart->getOrderTotal()*100-($firstNxAmount+($nxAmount*(Configuration::get('PAYLINE_RECURRING_NUMBER')-1)));
				$this->log('Echeancier Nx',array('firstNxAmount'=>$firstNxAmount,'nxAmount'=>$nxAmount,'delta'=>$delta));
				$firstNxAmount += $delta;
			}else{
				$nxAmount = round($this->context->cart->getOrderTotal()*100/Configuration::get('PAYLINE_RECURRING_NUMBER'));
				$firstNxAmount 	= $this->context->cart->getOrderTotal()*100 - ($nxAmount * (Configuration::get('PAYLINE_RECURRING_NUMBER')-1));
			}
			$nxAmount = $nxAmount/100;
			$firstNxAmount = $firstNxAmount/100;
			$this->context->smarty->assign(array(
											'nxAmount' => $nxAmount,
											'firstNxAmount' => $firstNxAmount,
											'nxNumber' => Configuration::get('PAYLINE_RECURRING_NUMBER')-1
			)
			);
		}


		if(isset($lastOrderTotal) && $lastOrderTotal!=$orderTotal){

			$this->context->smarty->assign(array('orderAmount' => $lastOrderTotal,
					'orderReduceAmount' => $orderTotal));
		}
		else {

			$this->context->smarty->assign(array('orderAmount' => $orderTotal));

		}
		
		return $this->display(dirname(__FILE__), '/views/templates/front/'.Tools::substr(_PS_VERSION_, 0, 3).'/payline.tpl');

	}

	/**
	 * @refactor
	 */
	public function hookPaymentReturn($params){
		if (!$this->active){

			return null;

		} // if

		$this->context->smarty->assign(array('error' =>  Tools::getValue('error') ?  Tools::getValue('error') : 0));

		return $this->fetchTemplate('/views/templates/front/'.Tools::substr(_PS_VERSION_, 0, 3).'/', 'payment_return');
	}

	/**
	 * Hook display on customer account page
	 * Display an additional link on my-account and block my-account
	 * @refactor
	 */
	public function hookCustomerAccount($params)
	{
		if(Configuration::get('PAYLINE_WALLET_ENABLE') OR Configuration::get('PAYLINE_SUBSCRIBE_ENABLE'))
		{
			$contracts = $this->getPaylineContracts();


			$this->context->smarty->assign(array(
					'module_img_dir' => _MODULE_DIR_ . 'payline' . '/'. 'img/'));

			if($contracts)
			{
				$cardAuthorizeByWalletNx = explode(',',Configuration::get('PAYLINE_AUTORIZE_WALLET_CARD'));

				$cards = array();
				foreach($contracts as $contract)
				{
					if(in_array($contract['type'],$cardAuthorizeByWalletNx) && $contract['primary'])
						$cards[] = array('contract' => $contract['contract'], 'type' => $contract['type'], 'logo' => $contract['logo']);
				}
			}

			if(isset($cards) AND sizeof($cards) > 0)
				return $this->fetchTemplate('/views/templates/front/'.Tools::substr(_PS_VERSION_, 0, 3).'/', 'my-account');
			else
				return false;
		}
		else
			return false;
	}

	/**
	 * @refactor
	 * @param unknown_type $params
	 * @return Ambigous <boolean, Ambigous, string, void, string>
	 */
	public function hookMyAccountBlock($params)
	{
		return $this->hookCustomerAccount($params);
	}

	/**
	 * @refactor
	 * @param unknown_type $params
	 * @return string
	 */
	public function hookAdminOrder($params)
	{
		$html='';
		switch (Tools::getValue('payline'))
		{
			case 'captureOk':
				$message = $this->l('Funds have been recovered.');
				break;
			case 'captureError':
				$message = $this->l('Recovery of funds request unsuccessful. Please see log message!');
				break;
			case 'refundOk':
				$message = $this->l('Refund has been made.');
				break;
			case 'refundError':
				$message = $this->l('Refund request unsuccessful. Please see log message!');
				break;
		}
		if (isset($message) AND $message) {
			if (version_compare(_PS_VERSION_, '1.6.0.0', '>=')) {
				$html .= '
				<div class="alert alert-success">
					<button data-dismiss="alert" class="close" type="button">&times;</button>
					'.$message.'
				</div>';
			} else {
				$html .= '
				<br />
				<div class="module_confirmation conf confirm" style="width: 400px;">
					<img src="'._PS_IMG_.'admin/ok.gif" alt="" title="" /> '.$message.'
				</div>';
			}
		}

		$order = new Order($params['id_order']);
		if ($order->module == $this->name) {
			$hasCartRules = (is_array($order->getCartRules()) && sizeof($order->getCartRules()));
			$this->context->controller->addJS($this->_path . 'js/orders.js');
			$html .= '
			<script type="text/javascript">
			var paylineConfirmAlertRefund = "' . ($hasCartRules ? $this->l('This order contains vouchers, please check that the amount to refund is lower than the amount paid!') : $this->l('Do you really want to refund the selected products?')) . '";
			var paylineNoCreditSlipAlert = "' . $this->l('You did not checked credit slip checkbox, if you want Payline to refund, you must check it. Do you want to continue anyway?') . '";
			var paylineVoucherAlert = "' . $this->l('You have checked generate voucher checkbox, if you want Payline to refund, you must uncheck it. Do you want to continue anyway?') . '";
			</script>
			';
			//If order paid by Payline
			if($this->_canRefund((int)$params['id_order']))
			{
				if (version_compare(_PS_VERSION_, '1.6.0.0', '>=')) {
					$html .= '
						<div class="row">
							<div class="col-lg-7">
								<div class="panel">
									<div class="panel-heading"><img src="'._MODULE_DIR_.$this->name.'/logo.gif" alt="" /> '.$this->l('Payline Refund').'</div>
									<div class="row">
										<p><strong>'.$this->l('Information:').'</strong> '.$this->l('Payment accepted').'</p>
										<p><strong>'.$this->l('Information:').'</strong> '.$this->l('When you refund a product, a partial refund is made unless you select "Generate a voucher".').'</p>
										<form method="post" action="'.htmlentities($_SERVER['REQUEST_URI']).'">
											<input type="hidden" name="id_order" value="'.(int)$params['id_order'].'" />
											<input type="submit" class="btn btn-primary center-block" name="submitPaylineRefund" value="'.$this->l('Refund total transaction').'" onclick="if(!confirm(\''.$this->l('Are you sure?').'\'))return false;" />
										</form>
									</div>
								</div>
							</div>
						</div>
						';
				} else {
					$html .= '<br />
						<fieldset style="width:400px;">
							<legend><img src="'._MODULE_DIR_.$this->name.'/logo.gif" alt="" /> '.$this->l('Payline Refund').'</legend>
							<p><strong>'.$this->l('Information:').'</strong> '.$this->l('Payment accepted').'</p>
							<p><strong>'.$this->l('Information:').'</strong> '.$this->l('When you refund a product, a partial refund is made unless you select "Generate a voucher".').'</p>
							<form method="post" action="'.htmlentities($_SERVER['REQUEST_URI']).'">
								<input type="hidden" name="id_order" value="'.(int)$params['id_order'].'" />
								<p class="center"><input type="submit" class="button" name="submitPaylineRefund" value="'.$this->l('Refund total transaction').'" onclick="if(!confirm(\''.$this->l('Are you sure?').'\'))return false;" /></p>
							</form>
						</fieldset>';
				}
				$this->postProcess();
				return $html;
			}

			if($this->_canCapture((int)$params['id_order']))
			{
				if (version_compare(_PS_VERSION_, '1.6.0.0', '>=')) {
					$html .= '
						<div class="row">
							<div class="col-lg-7">
								<div class="panel">
									<div class="panel-heading"><img src="'._MODULE_DIR_.$this->name.'/logo.gif" alt="" /> '.$this->l('Payline Capture').'</div>
									<div class="row">
										<p><strong>'.$this->l('Information:').'</strong> '.$this->l('Authorized payment').'</p>
										<p><strong>'.$this->l('Information:').'</strong> '.$this->l('You can capture this transaction manually').'</p>
										<form method="post" action="'.htmlentities($_SERVER['REQUEST_URI']).'">
											<input type="hidden" name="id_order" value="'.(int)$params['id_order'].'" />
											<input type="submit" class="btn btn-primary center-block" name="submitPaylineCapture" value="'.$this->l('Capture total transaction').'" onclick="if(!confirm(\''.$this->l('Are you sure?').'\'))return false;" />
										</form>
									</div>
								</div>
							</div>
						</div>
					';
				} else {
					$html .= '<br />
						<fieldset style="width:400px;">
							<legend><img src="'._MODULE_DIR_.$this->name.'/logo.gif" alt="" /> '.$this->l('Payline Capture').'</legend>
							<p><strong>'.$this->l('Information:').'</strong> '.$this->l('Authorized payment').'</p>
							<p><strong>'.$this->l('Information:').'</strong> '.$this->l('You can capture this transaction manually').'</p>
							<form method="post" action="'.htmlentities($_SERVER['REQUEST_URI']).'">
								<input type="hidden" name="id_order" value="'.(int)$params['id_order'].'" />
								<p class="center"><input type="submit" class="button" name="submitPaylineCapture" value="'.$this->l('Capture total transaction').'" onclick="if(!confirm(\''.$this->l('Are you sure?').'\'))return false;" /></p>
							</form>
						</fieldset>';

				}
				$this->postProcess();
			}
		}

		return $html;
	}

	/**
	 * @refactor
	 */
	public function hookUpdateOrderStatus($params)
	{
		if($this->_canCapture((int)$params['id_order']))
		{
			//We verify if capture is manual or by status
			switch ($this->_getPaymentByOder((int)$params['id_order']))
			{
				case 'webPayment' :
					$state = Configuration::get('PAYLINE_WEB_CASH_VALIDATION');
					break;

				case 'directPayment' :
					$state = Configuration::get('PAYLINE_DIRECT_VALIDATION');
					break;

				case 'walletPayment' :
					$state = Configuration::get('PAYLINE_WALLET_VALIDATION');
					break;
			}

			if(isset($state) && $state > -1)
			{
				$orderState = $params['newOrderStatus'];
				if($state == $orderState->id)
					$this->_doTotalCapture((int)$params['id_order']);
			}
		}
	}
	
	public function hookActionObjectOrderSlipAddAfter($params) {
		$order = new Order($params['object']->id_order);
		$amount = $params['object']->amount;
		
		if (Context::getContext()->employee->isLoggedBack()
			&& $amount > 0
			&& Validate::isLoadedObject($order)
			&& $order->module == $this->name 
			&& $order->hasBeenPaid()
			&& !Tools::getValue('generateDiscount') && !Tools::getValue('generateDiscountRefund')
			&& $this->_canRefund((int)$order->id))
		{
			$id_transaction = $this->_getTransactionId((int)$order->id);
			if (!$id_transaction) {
				Context::getContext()->controller->errors[] = $this->l('Payline error: unable to process refund (transaction ID is missing)');
				return false;
			}
			
			$contract_number = $this->_getContractNumberByTransaction((int)$order->id);
			if (!$contract_number) {
				Context::getContext()->controller->errors[] = $this->l('Payline error: unable to process refund (contract number is missing)');
				return false;
			}

			// Fix negative amount when amount is taken from order slip...
			if ((float)$order->total_paid_real - $amount < 0) {
				$amount = (float)$order->total_paid_real;
			}

			if ((Tools::getIsset('cancelProduct') && Tools::getIsset('generateCreditSlip') && Tools::getValue('generateCreditSlip')) || Tools::getIsset('partialRefund')) {
				$response = $this->_makeRefund($id_transaction, $contract_number, (int)$order->id, (float)$amount);
				$message = $this->l('Cancel products result:') . "\n";
				if (isset($response) && $response['result']['code'] == '00000') {
					$message .= $this->l('Payline refund successful!') . "\n";
					$message .= $this->l('Amount:') . ' ' . $amount .  "\n";
					$message .= $this->l('Transaction ID:') . ' ' . $response['transaction']['id'];
					$orderInvoice = new OrderInvoice($order->invoice_number);
					if (!Validate::isLoadedObject($orderInvoice))
						$orderInvoice = null;
					$order->addOrderPayment($amount * -1, null, $response['transaction']['id'], null, null, $orderInvoice);
				} else {
					Context::getContext()->controller->errors[] = $this->l('Payline error: unable to process refund, you must do it from your Payline backoffice');
					$message .= $this->l('Payline refund invalid!') . "\n";
					$message .= $this->l('Error code:') . ' ' . $response['result']['code'];
				}
				$this->addPrivateMessage((int)$order->id, $message);
			}
		}
	}

	# POST PROCESS

	/**
	 * @refactor
	 */
	public function postProcess()
	{
		global $currentIndex;

		$sHtml = '';

		if (Tools::isSubmit('submitPaylineRefund'))
		{
			if (!($response = $this->_doTotalRefund((int)(Tools::getValue('id_order')))) OR !sizeof($response))
				$sHtml .= '<p style="color:red;">'.$this->l('Error when making refund request').'</p>';
			else
			{
				if ($response['result']['code'] == '00000')
					Tools::redirectAdmin($currentIndex.'&id_order='.(int)(Tools::getValue('id_order')).'&vieworder&payline=refundOk&token='.Tools::getAdminToken('AdminOrders'.(int)(Tab::getIdFromClassName('AdminOrders')).(int)($this->context->cookie->id_employee)));
				else
					Tools::redirectAdmin($currentIndex.'&id_order='.(int)(Tools::getValue('id_order')).'&vieworder&payline=refundError&token='.Tools::getAdminToken('AdminOrders'.(int)(Tab::getIdFromClassName('AdminOrders')).(int)($this->context->cookie->id_employee)));
			}
		}

		if (Tools::isSubmit('submitPaylineCapture'))
		{
			if (!($response = $this->_doTotalCapture((int)(Tools::getValue('id_order')))) OR !sizeof($response))
				$sHtml .= '<p style="color:red;">'.$this->l('Error when making refund request').'</p>';
			else
			{
				if ($response['result']['code'] == '00000')
					Tools::redirectAdmin($currentIndex.'&id_order='.(int)(Tools::getValue('id_order')).'&vieworder&payline=captureOk&token='.Tools::getAdminToken('AdminOrders'.(int)(Tab::getIdFromClassName('AdminOrders')).(int)($this->context->cookie->id_employee)));
				else
					Tools::redirectAdmin($currentIndex.'&id_order='.(int)(Tools::getValue('id_order')).'&vieworder&payline=captureError&token='.Tools::getAdminToken('AdminOrders'.(int)(Tab::getIdFromClassName('AdminOrders')).(int)($this->context->cookie->id_employee)));
			}
		}

		if(Tools::isSubmit('submitPayline'))
		{
			$aLanguages = Language::getLanguages(false);

			$nDefaultLanguageId = (int)(Configuration::get('PS_LANG_DEFAULT'));


			foreach ($this->aDefaultConfig as $sKeyName => $mVal) {

				if (in_array($sKeyName, $this->aMultilangConfig)){

					$aValues = array();

					$nDefaultFieldName = $sKeyName .'_' . $nDefaultLanguageId;

					foreach ($aLanguages as $aLanguage){

						$nLanguageId = $aLanguage['id_lang'];

						$sMultiLangFieldName = $sKeyName .'_' . $nLanguageId;

						if (isset($_REQUEST[$sMultiLangFieldName])){

							$sValue = $_REQUEST[$sMultiLangFieldName];

						} elseif (isset($_REQUEST[$nDefaultFieldName])) {

							$sValue = $_REQUEST[$nDefaultFieldName];

						} else {

							$sValue = '';

						}

						$aValues[$nLanguageId] =  $sValue;

					}

					Configuration::updateValue($sKeyName, $aValues);

				} else if (in_array($sKeyName, $this->aArrayConfig)) {
					$value = array_key_exists($sKeyName, $_REQUEST) ? $_REQUEST[$sKeyName] : null;
					if ($value) {
						$value = implode(',', $value);
					}
					Configuration::updateValue($sKeyName, $value);
				} else {
				    $value = array_key_exists($sKeyName, $_REQUEST) ? $_REQUEST[$sKeyName] : null;

					Configuration::updateValue($sKeyName, $value);

				}
			}


			foreach (array() as $sKeyName => $mVal) {
				$value = array_key_exists($sKeyName, $_REQUEST) ? $_REQUEST[$sKeyName] : null;
				Configuration::updateValue($sKeyName, $value);
			}


			if(Configuration::get('PAYLINE_WEB_CASH_ACTION') == '101')
				Configuration::updateValue('PAYLINE_WEB_CASH_VALIDATION','');
			if(Configuration::get('PAYLINE_DIRECT_ACTION') == '101')
				Configuration::updateValue('PAYLINE_DIRECT_VALIDATION','');
			if(Configuration::get('PAYLINE_WALLET_ACTION') == '101')
				Configuration::updateValue('PAYLINE_WALLET_VALIDATION','');

			if (Tools::getIsset('paylinePrimaryContractsList') && Tools::getIsset('paylineSecondaryContractsList')){

				$this->_clearContracts();
				foreach ($this->getContractsFromPost() as $aContract)
					$this->updateContract($aContract);
				// foreach

			} // foreach


			$sHtml .= '<div class="conf confirm">'.$this->l('Settings updated').'</div>';

		}

		return $sHtml;

	}

	# VALIDATORS

	protected function addOrderState($oOrder, $nIdOrderState){

		$oOrderHistory = new OrderHistory();

		$oOrderHistory->id_order = (int)$oOrder->id;

		$oOrderHistory->changeIdOrderState($nIdOrderState, (int)$oOrder->id);

		return $oOrderHistory->addWithemail();

	}

	protected function sendNxMails($oOrder, $sMessage) {

		$oCustomer = new Customer($oOrder->id_customer);

		$aMailVars = array(
				'{lastname}'  => $oCustomer->lastname,
				'{firstname}' => $oCustomer->firstname,
				'{id_order}'  => $oOrder->id,
				'{message}'   => $sMessage
		);

		$sCustomerName = $oCustomer->firstname . ' ' . $oCustomer->lastname;

		Mail::Send((int)$oOrder->id_lang, 'order_merchant_comment',	Mail::l('New message regarding your order', (int)$oOrder->id_lang), $aMailVars, $oCustomer->email, $sCustomerName, null, null, null, null, _PS_MAIL_DIR_, true);

		Mail::Send((int)$oOrder->id_lang, 'payment_recurring', $this->getL('Recurring payment is approved'), $aMailVars, $oCustomer->email, $sCustomerName, null, null, null, null, dirname(__FILE__).'/mails/');

	}

	protected function getOrderHistory($oOrder){

		$aResult = array();

		foreach ($oOrder->getHistory($oOrder->id_lang) AS $aRow) {

			$aResult[$aRow['id_order_state']] = $aRow['id_order_state'];

		} // foreach

		return $aResult;


	}

	protected function getNxPaymentsRecap($aBillingRecords, $oCurrency){

		$nPayments = 0;

		$nLastPaymentStatus = null;

		$aMessage = array();

		$aMessage[] = $this->getL(" [Your schedule]");

		$this->log('getNxPaymentsRecap',$aBillingRecords);

		foreach($aBillingRecords as $oPayment){

			$sMessage = sprintf('%s : %1.2f %s', $oPayment->date, $oPayment->amount/100, $oCurrency->sign);

			if ($oPayment->result && $oPayment->result->code){

				if ($this->isResultInSet($oPayment->result->code, self::RS_RECURRING_APPROVED)) {

					$nPayments++;

					$sMessage .= ' - ' . $this->getL("Transaction is approved"). ' : ' . $oPayment->result->code;

				}	else {

					$sMessage .= ' - ' . $this->getL("Transaction is refused") . ' : ' . $oPayment->result->code;

				} // if

				$aMessage[] = $sMessage;

			} // if

			$nLastPaymentStatus = $oPayment->status;

		} // foreach

		$sMessage = implode('<br />', $aMessage);

		return array($nPayments, $nLastPaymentStatus, $sMessage);


	}

	public function validateNx($aData){

		$this->log('validateNx', $aData);

		$sToken = null;
    if (isset($aData['transactionId']) && !empty($aData['transactionId'])){

			$aArgs = array(
					'orderRef' 			=> '',
					'startDate' 		=> '',
					'endDate'			=> '',
					'transactionHistory'=> '',
					'archiveSearch'		=> '',
			);

			# @ws_call
			$aPaymentDetails = $this->getPaylineSDK()->getTransactionDetails(array('transactionId' => $aData['transactionId'], 'version' => self::API_VERSION));

			
			
		} else if  (isset($aData['token']) || !empty($aData['token'])) {

			$sToken = $aData['token'];

			$aPaymentDetails = $this->getPaylineSDK()->getWebPaymentDetails(array('token'=> $sToken, 'version'=> self::API_VERSION));


		} else {

			$this->log('ERROR: Missing data');

			return false;

		}

		$this->log('NX payment details', $aPaymentDetails);

		if($aPaymentDetails){

			$sResultCode = isset($aPaymentDetails['result']['code']) ? $aPaymentDetails['result']['code'] : null;
			$sResultShortMessage = isset($aPaymentDetails['result']['shortMessage']) ? $aPaymentDetails['result']['shortMessage'] : null;
			$sResultLongMessage = isset($aPaymentDetails['result']['longMessage']) ? $aPaymentDetails['result']['longMessage'] : null;

			//02319 Pyment cancelled by Buyer
			if($sResultCode == '02319'){

				Tools::redirectLink(__PS_BASE_URI__.'order.php');

			}

			$aPrivateData = isset($aPaymentDetails['privateDataList']['privateData']) ? $this->convertPrivateDataToArray($aPaymentDetails['privateDataList']['privateData']) : array();

			if (isset($aPrivateData['idCart'])){

				$nCartId = $aPrivateData['idCart'];

			} else if (isset($aData['orderRef'])){

				$nCartId = $this->getCartIdFromOrderReference($aData['orderRef']);

			}

			if (!$nCartId){

				$this->log('No cart id', $aPrivateData);

				return false;

			}


			$oCart = new Cart($nCartId);

			$nOrderId = (int)Order::getOrderByCartId($nCartId);

			// check token <=> id_cart association
			if ($sToken && $this->getIdCartByToken($sToken) != $nCartId) {
				$this->log('getIdCartByToken - cartId for token "'. $sToken .'" is incorrect');
				Tools::redirectLink(__PS_BASE_URI__ . 'order.php');
				return false;
			}

			$oCurrency = new Currency((int)$oCart->id_currency);

			$oCustomer = new Customer((int)$oCart->id_customer);

			$fAmount	 = $aPaymentDetails['payment']['amount'] / 100;

			$this->log('nx data', array('cart'=>$nCartId, 'order'=>$nOrderId, 'rc'=>$sResultCode));

			$aVars = array(
					'transaction_id' 	=> $aPaymentDetails['transaction']['id'],
					'contract_number' 	=> $aPaymentDetails['payment']['contractNumber'],
					'action' 			=> $aPaymentDetails['payment']['action'],
					'mode' 				=> $aPaymentDetails['payment']['mode'],
					'amount' 			=> $aPaymentDetails['payment']['amount'],
					'currency' 			=> $aPaymentDetails['payment']['currency'],
					'by' 				=> 'nxPayment'
			);

			if($this->isResultInSet($sResultCode, self::RS_VALID_WALLET)) {

				if ($aPaymentDetails['billingRecordList']['billingRecord']) {
					
					$aBillingRecord =  $aPaymentDetails['billingRecordList']['billingRecord'];
					
					
				} else {
					
					$aPaymentRecord = $this->getPaylineSDK()->getPaymentRecord(array('contractNumber'=> $aPaymentDetails['payment']['contractNumber'], 'paymentRecordId'=> $aData['paymentRecordId']));
$this->log('aPaymentRecord', $aPaymentRecord);
					
					if ($aPaymentRecord['billingRecordList']['billingRecord']) {
						
						$aBillingRecord = $aPaymentRecord['billingRecordList']['billingRecord'];
						
					}else {
					
						$this->log("ERROR, unable to fetch billingRecordList");
						
						return false;
					
					} // if
					
				} 
				
				list($nPayments, $nLastPaymentStatus, $sMessage) = $this->getNxPaymentsRecap($aBillingRecord, $oCurrency);

				$sMessage = date('Y-m-d H:i:s') . ' : ' . $aVars['transaction_id'] .  '<br />' . $sMessage;

				if(!$nOrderId) {
					// first nx installment is OK
					if ($this->isResultInSet($aBillingRecord[0]->result->code, self::RS_VALID_SIMPLE)) {
						// order doesn't exists yet, this is first installment
						$this->validateOrder($nCartId , Configuration::get('PAYLINE_ID_ORDER_STATE_NX'), $oCart->getOrderTotal(), $this->displayName, $this->getL('Transaction Payline : ') . $aPaymentDetails['transaction']['id'] . ' (NX)', $aVars,'','', $oCustomer->secure_key);
						$nOrderId = (int)Order::getOrderByCartId($nCartId);
						$oOrder = new Order($nOrderId);
						$this->addPublicMessage((int)$oOrder->id, $sMessage);
						$this->sendNxMails($oOrder, $sMessage);
					} else {
						// first nx installment is KO => order is refused
						$this->log("First NX installment refused (transaction ".$aBillingRecord[0]->transaction->id.", code ".$aBillingRecord[0]->result->code.") => order refused");
					}
				} else {

					$oOrder = new Order($nOrderId);

					if($nPayments > 1){

						$this->addPublicMessage((int)$oOrder->id, $sMessage);

					} // if

					$aHistory = $this->getOrderHistory($oOrder);

					//If is not the last recurring payment but the current payment is approved we send and email to customer
					if ($sResultCode === '02500' && !$nLastPaymentStatus && $nPayments > 1 && in_array(Configuration::get('PAYLINE_ID_ORDER_STATE_NX'), $aHistory)) {

						$this->sendNxMails($oOrder, $sMessage);

					} // if

					// If s the last payment approved we change status and ou send an email
					if ($nLastPaymentStatus == 1 && !in_array(_PS_OS_PAYMENT_, $aHistory) && in_array(Configuration::get('PAYLINE_ID_ORDER_STATE_NX'), $aHistory) ) {

						$this->addOrderState($oOrder, _PS_OS_PAYMENT_);

					} // if

				} // if

			} else {
				$this->log('validateNx', array('idOrder' => (int)$nOrderId, 'idCart' => (int)$nCartId, 'resultCode' => $sResultCode));
				// Order already exists
				// We have to ignore some cases
				$pendingCodes = array(
					'02304', // No transaction found for this token
					'02306', // Customer has to fill his payment data
				    '02533', // Customer not redirected to payment page AND session is active
				    '02000', // transaction in progress
				    '02005' // transaction in progress
				);
				
				if ($nOrderId && !in_array($sResultCode, $pendingCodes)) {
					$oOrder = new Order($nOrderId);
					$sMessage .= '<br /> ' . $sResultCode . ' - ' . $this->getL("Transaction is refused");
					$sMessage .= '<br /> ' . $sResultShortMessage . ' - ' . $sResultLongMessage;
					$this->addPublicMessage((int)$oOrder->id, $sMessage);
					if (!in_array(_PS_OS_ERROR_,  $this->getOrderHistory($oOrder))) {
						$this->addOrderState($oOrder, _PS_OS_ERROR_);
					}
				} else {
					// Redirect to cart
					Tools::redirectLink(__PS_BASE_URI__.'order.php');
				}
			}
			$this->redirectToConfirmationPage($oOrder, !$this->isResultInSet($sResultCode, self::RS_VALID_WALLET));
		}

		return false;

	}

	/**
	 * Main entry for subscription validation
	 * @param array $aData Subscription data
	 * @return boolean True if subscription validated correctly
	 */
	public function validateSubscription($aData){

		$this->log('validateSubscription', $aData);

		if (isset($aData['token'])){

			return $this->validateSubscriptionViaToken($aData['token']);

		} elseif (isset($aData['paymentRecordId']) && isset($aData['transactionId']) ) {

			return $this->validateSubscriptionViaPaymentRecord($aData['paymentRecordId'], $aData['transactionId']);

		} else {

			$this->log('ERROR : not enough parameters');

			return false;

		} // if

	} // validateSubscription

	/**
	 * Subcription with given payment record and transaction id
	 * @param string $sPaymentRecordId
	 * @param string $sTransactionId
	 * @return boolean True if succeeded
	 */
	protected function validateSubscriptionViaPaymentRecord($sPaymentRecordId, $sTransactionId){

		// Using principal contract
		$sContractNumber = Configuration::get('PAYLINE_CONTRACT_NUMBER');

		$aLogData = array(
			'paymentRecordId' => $sPaymentRecordId,
			'transactionId'	  => $sTransactionId,
			'contractNumber'  => $sContractNumber,
			'status'		  => '',
			'code'			  => '',
			'id_order'		  => '',
			'id_cart'		  => '',
			'id_new_order'	  => '',
			'id_new_cart'	  => '',
			'message'		  => ''
		);

		// fetching payment record & transaction details

		# @ws_call
		$aPaymentRecord = $this->getPaylineSDK()->getPaymentRecord(array(
			'contractNumber' 	=> $sContractNumber,
			'paymentRecordId' 	=> $sPaymentRecordId)
		);

		if (!$aPaymentRecord){

			$aLogData['message'] = 'Unable to fetch payment record';

			$this->log('ERROR', $aLogData);

			return false;

		} // if

		$this->log('validateSubscriptionViaPaymentRecord : PaymentRecord', $aPaymentRecord);

		// Now we should test if there exists valid transaction attached
		# @ws_call
		$oBillingRecord = $this->getBillingRecordByTransactionId($aPaymentRecord['billingRecordList']['billingRecord'], $sTransactionId);

		if($oBillingRecord == null){

			$aLogData['message'] = 'Unable to find transaction ';

			$this->log('ERROR', $aLogData);

			return false;

		} // if

		# @ws_call
		$aTransactionDetails = $this->getPaylineSDK()->getTransactionDetails(array('transactionId' => $sTransactionId, 'version' => self::API_VERSION));

		if (!$aTransactionDetails){

			$aLogData['message'] = 'Unable to fetch transaction details';

			$this->log('ERROR', $aLogData);

			return false;

		} // if

		$this->log('validateSubscriptionViaPaymentRecord : TransactionDetails', $aTransactionDetails);

		// Fetching first cart id. Normally it should be passed in private data, but apparently that's not case for subscriptions

		$nCartId = $this->getCartIdFromPaymentRecord($aPaymentRecord);

		// So, for subscriptions, we had to use order reference, which luckily for us contains id of first cart

		if (!$nCartId){

			$nCartId = $this->getCartIdFromTransactionDetails($aTransactionDetails);

		} // if

		// Final test. If we don't have our cart id now, we abandon.

		if (!$nCartId){

			$aLogData['message'] = 'Unable to fetch cart id';

			$this->log('ERROR', $aLogData);

			return false;

		} // if

		$aLogData['id_cart'] = $nCartId;

		// Getting id order associated with original cart

		$nOrderId = (int)Order::getOrderByCartId($nCartId);

		if (!$nOrderId) {

			$aLogData['message'] = 'Unable to find order id for given cart';

			$this->log('ERROR', $aLogData);

			return false;

		} // if

		$aLogData['id_order'] = $nOrderId;

		// Now we have transactions details (and we know there is associated with a billing record). We can verify result of transaction

		$sResultCode = isset($aTransactionDetails['result']['code']) ? $aTransactionDetails['result']['code'] : null;
		$sResultShortMessage = isset($aTransactionDetails['result']['shortMessage']) ? $aTransactionDetails['result']['shortMessage'] : null;
		$sResultLongMessage = isset($aTransactionDetails['result']['longMessage']) ? $aTransactionDetails['result']['longMessage'] : null;

		$aLogData['code'] = $sResultCode;

		// Payment refused

		if(!$this->isResultInSet($sResultCode, self::RS_RECURRING_APPROVED)){

			$aLogData['status'] = 'REFUSED';

			$this->log('ERROR', $aLogData);

			$this->addMessage($nOrderId, $this->l('Recurring payment (subscription) refused - %s, transaction id %s', $sResultShortMessage . ' - ' . $sResultLongMessage, $sTransactionId));

			return false;

		} // if

		// Order status

		$nStatusId = ($sResultCode == '02501' ? Configuration::get('PAYLINE_ID_STATE_ALERT_SCHEDULE') : _PS_OS_PAYMENT_);

		$aLogData['status'] = ($sResultCode == '02501' ? 'OK WITH ALERT' : 'OK');

		// Getting subscription id

		$oOldOrder = new Order($nOrderId);

		$nPaylineSubscribeId = $this->getPaylineSubscribe($sPaymentRecordId);

		$bIsFirstInstallement = $this->isFirstInstallement($oOldOrder);

		$fAmount = $aTransactionDetails['payment']['amount'] / 100;

		if ($bIsFirstInstallement) {

			$this->updatePaylineSubscribeRecordId($oOldOrder, $sPaymentRecordId);

			$oOrder = $this->updateFirstSubscriptionOrder($oOldOrder, $sTransactionId, $nStatusId, $fAmount);

		} else {

			$oOrder = $this->validateNewSubscriptionOrder($nCartId,  $sTransactionId, $nStatusId, $fAmount, $aTransactionDetails);


		} // if

		$this->saveTransaction($oOrder->id_cart, $sTransactionId, $aTransactionDetails['payment']['contractNumber'], $aTransactionDetails['payment']['action'], $aTransactionDetails['payment']['mode'], $aTransactionDetails['payment']['amount'], $aTransactionDetails['order']['currency'], 'subscribePayment');

		if ($nPaylineSubscribeId) {

			$this->setPaylineSubscribeState($nPaylineSubscribeId, 1);

			if (!$bIsFirstInstallement) {

				$this->setPaylineSubscribeOrder($nPaylineSubscribeId, $oOrder->id);

			} // if

		} // if

		$this->log('SUCCESS', $aLogData);

		return true;

	} // validateSubscriptionViaPaymentRecord

	protected function getNumberOfFutureInstallments($aBillingRecordList){

		return $this->getNumberOfInstallments($aBillingRecordList, 0);

	}

	protected function getNumberOfAcceptedInstallments($aBillingRecordList){

		return $this->getNumberOfInstallments($aBillingRecordList, 1);

	}

	protected function getNumberOfRefusedInstallments($aBillingRecordList){

		return $this->getNumberOfInstallments($aBillingRecordList, 2);

	}


	protected function getNumberOfInstallments($aBillingRecordList, $nStatus){

		$nResult = 0;

		foreach ($aBillingRecordList as $oBillingRecord) {

			if ((int)$oBillingRecord->status == $nStatus) {

				$nResult++;

			} // if

		} // foreach

		return $nResult;

	}

	protected function isLastInstallement($nIdSubscription){

		$sQuery = 'SELECT COUNT(*) FROM `'._DB_PREFIX_.'payline_subscribe_order` WHERE id_payline_subscribe=' . $nIdSubscription;

		$nCount = Db::getInstance()->getValue($sQuery);

		$this->log('isLastInstallement', array($sQuery, $nCount, Configuration::get('PAYLINE_SUBSCRIBE_NUMBER')));

		return (int)$nCount >= (int)Configuration::get('PAYLINE_SUBSCRIBE_NUMBER');

	}

	protected function updatePaylineSubscribeRecordId($oOrder, $sPaymentRecordId){

		$request = 'UPDATE `'._DB_PREFIX_.'payline_subscribe` SET `paymentRecordId` = "'.pSql($sPaymentRecordId).'" WHERE id_cart ='.$oOrder->id_cart.' AND id_customer ='.$oOrder->id_customer;

		return Db::getInstance()->Execute($request);

	}

	protected function updateFirstSubscriptionOrder($oOrder, $sTransactionId, $nStatusId, $fAmountPaid){

		$sMessage = sprintf($this->l('Order validation - first installment - order id %d, cart id %d, transaction id %s, status id %d'),  $oOrder->id, $oOrder->id_cart, $sTransactionId, $nStatusId);

		$this->addOrderState($oOrder, $nStatusId);

		$this->addMessage($oOrder->id, $sMessage, true);

		$this->log($sMessage);

		return $oOrder;

	}

	protected function validateNewSubscriptionOrder($nSourceCartId, $sTransactionId, $nStatusId, $fAmount, $aTransactionDetails){

		$oCart = $this->duplicateCartFromId($nSourceCartId);

		if (!$oCart){

			$this->log('ERROR : validateNewSubscriptionOrder : Unable to duplicate cart ' . $nSourceCartId);

			return false;

		} // if

		$sValidationMessage = $this->getL('Transaction Payline : ') . $sTransactionId . ' (Subscribe) - next installement from cart ' . $nSourceCartId;

		// Validating cart which creates new order

		$oCustomer = new Customer($oCart->id_customer);

		$this->validateOrder($oCart->id, $nStatusId, $fAmount, $this->displayName, $sValidationMessage, array(), '', '', $oCustomer->secure_key);

		$nNewOrderId = (int)Order::getOrderByCartId($oCart->id);

		$sMessage = sprintf($this->l('Order validation - next installment - order id %d, cart id %d, transaction id %s, status id %d, source cart id %d'),  $nNewOrderId, $oCart->id, $sTransactionId, $nStatusId, $nSourceCartId);

		$this->addMessage($nNewOrderId, $sMessage, true);

		$this->log($sMessage);

		return new Order($nNewOrderId);

	}

	/**
	 * Subscription with given token
	 * @param string $sToken
	 * @return boolean True if succeeded
	 */
	protected function validateSubscriptionViaToken($sToken){

		$aLogData = array(
				'token' 		  => $sToken,
				'id_cart'		  => '',
				'status'		  => '',
				'code'			  => '',
				'id_order'		  => '',
				'id_new_order'	  => '',
				'id_new_cart'	  => '',
				'message'		  => ''
		);

		$this->log("validateSubscriptionViaToken : aLogData", $aLogData);

		$aPaymentDetails = $this->getPaylineSDK()->getWebPaymentDetails(array('token'=>$sToken, 'version' => self::API_VERSION));

		if($aPaymentDetails){

			$this->log("validateSubscriptionViaToken : aPaymentDetails", $aPaymentDetails);

			$sResultCode = isset($aPaymentDetails['result']['code']) ? $aPaymentDetails['result']['code'] : null;
			$sResultShortMessage = isset($aPaymentDetails['result']['shortMessage']) ? $aPaymentDetails['result']['shortMessage'] : null;
			$sResultLongMessage = isset($aPaymentDetails['result']['longMessage']) ? $aPaymentDetails['result']['longMessage'] : null;

			$aLogData['code'] = $sResultCode;

			if ($this->isResultInSet($sResultCode, self::RS_SUBSCRIBE_REDIRECT)){

				$aLogData['message'] = 'Redirecting';

				$this->log('REDIRECTION', $aLogData);

				Tools::redirectLink(__PS_BASE_URI__.'order.php');

			} // if

			$aPrivateData = isset($aPaymentDetails['privateDataList']['privateData']) ? $this->convertPrivateDataToArray($aPaymentDetails['privateDataList']['privateData']) : array();

			$this->log("validateSubscriptionViaToken : aPrivateData", $aPrivateData);

			$nCartId = isset($aPrivateData['idCart']) ? (int)$aPrivateData['idCart'] : null;

			$aLogData['id_cart'] = $nCartId;

			if (!$nCartId) {

				$aPaymentDetails = $this->getPaylineSDK()->getWebPaymentDetails(array('token'=>$sToken, 'version' => self::API_VERSION));

				$aLogData['message'] = 'Unable to find cart';

				$this->log('ERROR', $aLogData);

				return false;

			} // if

			$oCart = new Cart($nCartId);

			$oCurrency = new Currency((int)$oCart->id_currency);

			$oCustomer = new Customer((int)$oCart->id_customer);

			$nIdOrder = $nCartId ? (int)Order::getOrderByCartId($nCartId) : null;

			$aLogData['id_order'] = $nIdOrder;

			$aVars = array(
					'transaction_id' 	=> $aPaymentDetails['transaction']['id'],
					'contract_number' 	=> $aPaymentDetails['payment']['contractNumber'],
					'action' 			=> $aPaymentDetails['payment']['action'],
					'mode' 				=> $aPaymentDetails['payment']['mode'],
					'amount' 			=> $aPaymentDetails['payment']['amount'],
					'currency' 			=> $aPaymentDetails['payment']['currency'],
					'by' 				=> 'subscribePayment'
			);

			$this->log("validateSubscriptionViaToken : aVars", $aVars);

			if ($this->isResultInSet($sResultCode, self::RS_VALID_WALLET)){

				$sCardInd = $this->getCardInd($oCustomer->id, $aPaymentDetails['card']);

				if(!$nIdOrder) {

					# echeance "zero"

					$this->validateOrder($oCart->id, Configuration::get('PAYLINE_ID_ORDER_STATE_SUBSCRIBE'), 0, $this->displayName, $this->getL('Transaction Payline : ') . $aPaymentDetails['transaction']['id'] . ' (Subscribe) - first payment', $aVars, '', '', $oCustomer->secure_key);

					$nIdOrder = (int)Order::getOrderByCartId($oCart->id);

					$this->addMessage($nIdOrder, sprintf($this->l('Order created from token %s, waiting for premier installement'),  $sToken), true);

					$oNewOrder = new Order($nIdOrder);

					$this->log('makeSubscription', array('new_order_id'=>$nIdOrder, 'cardind'=>$sCardInd) +  $aPaymentDetails['payment']);

					$nIdPaylineSubscribe = $this->makeSubscription($oNewOrder, $sCardInd, $aPaymentDetails['paymentRecordId'], $aPaymentDetails['payment']['contractNumber']);

					$this->addMessage($nIdOrder, sprintf($this->l('Subscription id : %d'),  $nIdPaylineSubscribe), true);

				} else {

					$aLogData['message'] = "Order is already placed";

					$this->log('ERROR', $aLogData);

					$oOrder = new Order($nIdOrder);

					if (Context::getContext() && Context::getContext()->customer && Context::getContext()->customer->logged && Context::getContext()->customer->id && Context::getContext()->customer->id == $oOrder->id_customer) {

						$this->redirectToConfirmationPage($oOrder, !$this->isResultInSet($sResultCode, self::RS_VALID_WALLET));

					} else {

						return false;

					} // if

				} // if

			} else {

				/************************************************************************************************
				 * PAYMENT ERROR
				************************************************************************************************/
				/**If first recurring payment with error**/
				if (!$nIdOrder) {

					if($sResultCode !== '02304' && $sResultCode !== '02324' && $sResultCode !== '02534') {

						$this->validateOrder($nCartId,
								_PS_OS_ERROR_,
								$fAmount,
								$this->displayName,
								$sResultShortMessage . ' - ' . $sResultLongMessage .'<br />',
								array(),
								'','',$oCustomer->secure_key);



					} // if

				} else{

					$oOrder = new Order($nIdOrder);

					if(date('Y-m-d',strtotime($oOrder->date_add)) != date('Y-m-d')) {

						$oCart = $this->duplicateCartById($nCartId);

						if ($oCart){

							$this->validateOrder($oCart->id,
									Configuration::get('PAYLINE_ID_STATE_ERROR_SCHEDULE'),
									$oCart->getOrderTotal(),
									$this->displayName,
									$this->getL('Transaction Payline : '). $aPaymentDetails['transaction']['id'].' (Subscribe)',
									$aVars,
									'',
									'',
									$oCustomer->secure_key);

						} // if

				} // if

				} // if (!$nIdOrder)

			} // if

			$nIdOrder = (int)Order::getOrderByCartId($nCartId);

			$oOrder = new Order($nIdOrder);

			$this->redirectToConfirmationPage($oOrder, !$this->isResultInSet($sResultCode, self::RS_VALID_WALLET));

		} // if($aPaymentDetails)

		return false;

	} // function validateSubscriptionViaToken

	protected function redirectToConfirmationPage($oOrder, $bError = false) {

		$aQuery = array(
				'id_module' => $this->id,
				'id_cart' 	=> $oOrder->id_cart,
				'key' 		=> $oOrder->secure_key,
		);

		if ($bError){

			$aQuery['error'] = 'Payment error';

		} // if

		Tools::redirectLink(__PS_BASE_URI__.'order-confirmation.php?' . http_build_query($aQuery));

	}

	/**
	 * Adds a subscription record for given order
	 * @param Order $oOrder
	 * @param string $sCardInd
	 * @param string $sPaymentRecordId
	 * @param string $sContractNumber
	 * @return integer|false Id of subscription
	 */
	protected function makeSubscription($oOrder, $sCardInd, $sPaymentRecordId, $sContractNumber){

		$nPaylineSubscribeId = $this->setPaylineSubscribe($oOrder->id_customer, $oOrder->id_cart, $sPaymentRecordId, $sCardInd, $sContractNumber, Configuration::get('PAYLINE_SUBSCRIBE_PERIODICITY'));

		if ($nPaylineSubscribeId) {

			$this->setPaylineSubscribeOrder($nPaylineSubscribeId, $oOrder->id);

			$this->setPaylineSubscribeState($nPaylineSubscribeId, 1);

		} // if

		return $nPaylineSubscribeId;

	} // makeSubscription

	# CONTRACTS

	/**
	 * Get all contract types, and set available to true if contract is managed by the point of sell
	 * @return array
	 */
	public function getContractsTypes(){
		$pointOfSell = $this->_getCurrentMerchantPointOfSell();
		if ($pointOfSell !== false && isset($pointOfSell['contracts']))
			return $pointOfSell['contracts'];
		return array();
	}
	
	
	
	/**
	 * Get all contract types but select elements
	 * @return array
	 */
	public function getContractsTypesForSelect(){
		$contractList = array();
		foreach ($this->getContractsTypes() as $contract)
			$contractList[$contract['contractNumber']] = $contract['label'] . ' ('. $contract['contractNumber'] .')';
		return $contractList;
	}

	/**
	 * Get all contract types but select elements
	 * @return array
	 */
	public function getSDDContractsForSelect(){
		$contractList = array();
		foreach ($this->getContractsTypes() as $contract) {
			if ($contract['cardType'] != 'SDD')
				continue;
			$contractList[$contract['contractNumber']] = $contract['label'] . ' ('. $contract['contractNumber'] .')';
		}
		return $contractList;
	}

	public function getAllContractsForCurrentContext(){

		$nIdShopGroup = Shop::getContextShopGroupID(true);

		$nIdShop = Shop::getContextShopID(true);

		$aResult = array();
		
		$contractsFromPayline = $this->getContractsTypes();
		$contractsFromDB = $this->getPaylineContracts();
		
		foreach ($contractsFromDB as $k=>$aType) {
			$contractId = $aType['type'].'-'.$aType['contract'];
			$aResult[$contractId] = $aType;
		}
		
		foreach ($contractsFromPayline as $aType) {
			$contractId = $aType['cardType'].'-'.$aType['contractNumber'];
			if (isset($aResult[$contractId])) {
				$aResult[$contractId]['id_card'] = $contractId;
				$aResult[$contractId]['label'] = $aType['label'];
			} else {
				$aResult[$contractId] = array(
					'id_card' => $contractId,
					'label' => $aType['label'],
					'type' => $aType['cardType'],
				    'logo' => $this->sBaseUrl.'modules/payline/img/'.$aType['logo'],
					'contract'=> $aType['contractNumber'],
					'primary' => 0,
					'secondary' =>0,
					'position_primary' => 0,
					'position_secondary' =>0,
					'id_shop' => $nIdShop,
					'id_shop_group' => $nIdShopGroup
				);
			}
		}

		return $aResult;

	}
	
	/**
	 * Check if SDD contract is available
	 * @return bool
	 */
	public function isSDDContractAvailable(){
		$pointOfSell = $this->_getCurrentMerchantPointOfSell();
		if ($pointOfSell !== false && isset($pointOfSell['contracts']) && is_array($pointOfSell['contracts']) && count($pointOfSell['contracts'])) {
			foreach ($pointOfSell['contracts'] as $contract)
				if ($contract['cardType'] == 'SDD')
					return true;
		}
		return false;
	}
	
	private function _clearContracts() {
		Db::getInstance()->Execute('DELETE FROM `'._DB_PREFIX_.'payline_card` WHERE 1 ' . $this->getSqlShopRestriction());
	}

	/**
	 * Saves new contract data to payline_card table
	 * @param array $aContract
	 */
	protected function updateContract($aContract){
	    $logoUrl = $this->sBaseUrl.'modules/payline/img/'.$aContract['logo'];
	    
        return Db::getInstance()->insert('payline_card', array( 
            'type' => pSql($aContract['type']), 
            'contract' => pSql($aContract['contract']), 
            'label' => pSql($aContract['label']), 
            //'logo' => pSql($logoUrl),
            'logo' => pSql($aContract['logo']),
            'primary' => (!empty($aContract['contract']) && $aContract['primary'] ? 1 : 0), 
            'secondary' => (!empty($aContract['contract']) && $aContract['secondary'] ? 1 : 0), 
            'position_primary' => (empty($aContract['position_primary']) ? 0 : (int)$aContract['position_primary']), 
            'position_secondary' => (empty($aContract['position_secondary']) ? 0 : (int)$aContract['position_secondary']), 
            'id_shop' => (empty($aContract['id_shop']) ? 0 : (int)$aContract['id_shop']), 
            'id_shop_group' =>(empty($aContract['id_shop_group']) ? 0 : (int)$aContract['id_shop_group']), 
        ));
	}

	/**
	 * Gets all contracts passed via post for given shop set
	 * @return array of array
	 */
	protected function getContractsFromPost(){

		$aResult = array();
		$primaryContractsList = $secondaryContractsList = $primaryContractsListFinal = $secondaryContractsListFinal = array();
		$primaryPosition = $secondaryPosition = 0;

		list($nIdShopGroup, $nIdShop) = $this->getShopContextIdPair();
		
		$primaryContractsStr = Tools::getValue('paylinePrimaryContractsList');
		$secondaryContractsStr = Tools::getValue('paylineSecondaryContractsList');
		if (!empty($primaryContractsStr) && strlen($primaryContractsStr)) {
			$primaryContractsListTmp = json_decode($primaryContractsStr);
			if (is_array($primaryContractsListTmp) && count($primaryContractsListTmp))
				foreach ($primaryContractsListTmp as $contractRow) {
					$contractRow = explode('-', $contractRow);
					$primaryContractsList[$contractRow[0]][] = $contractRow[1];
				}
			unset($primaryContractsListTmp);
		}
		
		if (!empty($secondaryContractsStr) && strlen($secondaryContractsStr)) {
			$secondaryContractsListTmp = json_decode($secondaryContractsStr);
			if (is_array($secondaryContractsListTmp) && count($secondaryContractsListTmp))
				foreach ($secondaryContractsListTmp as $contractRow) {
					$contractRow = explode('-', $contractRow);
					$secondaryContractsList[$contractRow[0]][] = $contractRow[1];
				}
			unset($secondaryContractsListTmp);
		}
		
		if (is_array($primaryContractsList) && count($primaryContractsList))
			foreach ($primaryContractsList as $cardType => $contractNumberList)
				foreach ($contractNumberList as $contractNumber) {
					$primaryPosition++;
					$primaryContractsListFinal[$cardType.'-'.$contractNumber] = $primaryPosition;
				}
		
		if (is_array($secondaryContractsList) && count($secondaryContractsList))
			foreach ($secondaryContractsList as $cardType => $contractNumberList)
				foreach ($contractNumberList as $contractNumber) {
					$secondaryPosition++;
					$secondaryContractsListFinal[$cardType.'-'.$contractNumber] = $secondaryPosition;
				}

		foreach ($this->getContractsTypes() as $aType){
			$contractId = $aType['cardType'].'-'.$aType['contractNumber'];
			if (isset($primaryContractsListFinal[$contractId]) || isset($secondaryContractsListFinal[$contractId])) {
				$aResult[$contractId] = array(
						'id_card'             => $contractId,
						'contract'            => $aType['contractNumber'],
						'primary'             => isset($primaryContractsListFinal[$contractId]) ? 1 : 0,
						'secondary'           => isset($secondaryContractsListFinal[$contractId]) ? 1 : 0,
						'type'                => $aType['cardType'],
						'label'               => $aType['label'],
				        'logo'                => $this->sBaseUrl.'modules/payline/img/'.$aType['logo'],
						'id_shop'             => $nIdShop,
						'id_shop_group'       => $nIdShopGroup,
						'position_primary'    => isset($primaryContractsListFinal[$contractId]) ? (int)$primaryContractsListFinal[$contractId] : 0,
						'position_secondary'  => isset($secondaryContractsListFinal[$contractId]) ? (int)$secondaryContractsListFinal[$contractId] : 0,
				);
			}
		}

		return $aResult;

	}

	protected function getSecondaryCardList($sMode){

		$aResult = array();

		$bRecurring = ($sMode == self::MODE_RECURRING || $sMode == self::MODE_SUBSCRIBE || $sMode == self::MODE_WALLET);

		foreach ($this->getPaylineContractsSecondary($bRecurring) as  $aCard){

			$aResult[] = $aCard['contract'];

		} // foreach

		return $aResult;

	}

	/**
	 * @refactor
	 */
	protected function getPaylineContracts($nIdShopGroup = -1, $nIdShop = -1){

		if ($nIdShop === -1){

			$nIdShop = Shop::getContextShopID(true);

		}

		if ($nIdShopGroup === -1){

			$nIdShopGroup = Shop::getContextShopGroupID(true);

		}

		foreach (array(array(0, $nIdShop),array($nIdShopGroup, 0),array(0, 0)) as $aIds){

			$sQuery = '
			SELECT * FROM `'._DB_PREFIX_.'payline_card`
			WHERE `contract` <> "" ' . $this->getSqlShopRestriction($aIds[0], $aIds[1])
			. ' ORDER BY `primary`, `position_primary`, `secondary`, `position_secondary`';

			$aResult = Db::getInstance()->ExecuteS($sQuery);

			if (!empty($aResult) && is_array($aResult)){
				foreach ($aResult as $sKey => $aRow)
					$aResult[$sKey]['id_card'] = $aRow['type'].'-'.$aRow['contract'];

				return $aResult;

			}

		}

		return array();

	}

	protected function getPaylineContractDetails($contractNumber){
        $sQuery = '
		SELECT * FROM `'._DB_PREFIX_.'payline_card`
		WHERE `contract` = \''.$contractNumber.'\'';

			$aResult = Db::getInstance()->ExecuteS($sQuery);

			if (!empty($aResult) && is_array($aResult)){
			    foreach ($aResult as $sKey => $aRow)
			        $aResult[$sKey]['id_card'] = $aRow['type'].'-'.$aRow['contract'];
			    return $aResult;
			}
            return array();
	}

	/**
	 * @refactor
	 */
	protected function getPaylineContractsSecondary($recurring=false){

		$aResult = array();

		$aContracts = $this->getPaylineContracts();

		$aCards = explode(',',Configuration::get('PAYLINE_AUTORIZE_WALLET_CARD'));

		foreach ($aContracts as $aContract){

			if ($aContract['secondary'] == 1){

				if(!$recurring ||  (in_array($aContract['type'], $aCards))){

					$aResult[] = $aContract;

				} // if

			} // if

		} // foreach

		return $aResult;

	}

	/**
	 * @refactor
	 */
	protected function getPaylineContractByCard($type)
	{

		$nIdShop = Shop::getContextShopID(true);

		$nIdShopGroup = Shop::getContextShopGroupID(true);

		foreach (array(array($nIdShop, 0),array(0, $nIdShopGroup),array(0, 0)) as $aIds){

			$sQuery = 'SELECT contract FROM `'._DB_PREFIX_.'payline_card` WHERE `type` = "'.$type.'" ' .  $this->getSqlShopRestriction($aIds[0], $aIds[1]);

			$aResult = Db::getInstance()->getRow($sQuery);

			if (!empty($aResult)){

				return $aResult;

			}

		}

		return array();

	}

	# WALLET

	/**
	 * @refactor
	 */
	public function getWalletId($idCust){
		$result = Db::getInstance()->getRow('
			SELECT `id_wallet`
			FROM `'._DB_PREFIX_.'payline_wallet`
			WHERE `id_customer` = '.(int)($idCust));

		return isset($result['id_wallet']) ? $result['id_wallet'] : false;
	}

	/**
	 * @refactor
	 */
	protected function setWalletId($idCust,$idWallet){
		$request = '
			INSERT INTO `'._DB_PREFIX_.'payline_wallet` (
					`id_customer`,
					`id_wallet`
				)VALUES (
					\''.$idCust.'\',
					\''.$idWallet.'\'
				)';
		$result = Db::getInstance()->Execute($request);
		if (!$result) return false;
		return true;
	}

	/**
	 * @refactor
	 */
	public function getMyCards($customer_id)
	{

		$getCardsRequest = array();
		$getCardsRequest['contractNumber'] = Configuration::get('PAYLINE_CONTRACT_NUMBER');
		$getCardsRequest['walletId'] = $this->getOrGenWalletId($customer_id);
		$getCardsRequest['cardInd'] = null;

		$getCardsResponse =  $this->getPaylineSDK()->getCards($getCardsRequest);
		$cardData = array();

		if(isset($getCardsResponse) AND is_array($getCardsResponse) AND $getCardsResponse['result']['code'] == '02500'){
			$n = 0;
			foreach ($getCardsResponse['cardsList']['cards'] as $card){
				if(!$card->isDisabled)
				{
					$cardData[$n] = array();
					$cardData[$n]['lastName'] = $card->lastName;
					$cardData[$n]['firstName'] = $card->firstName;
					$cardData[$n]['number'] = $card->card->number;
					$cardData[$n]['type'] = $card->card->type;
					$cardData[$n]['expirationDate'] = $card->card->expirationDate;
					$cardData[$n]['cardInd'] = $card->cardInd;
					$n++;
				}
			}

			if(sizeof($cardData) > 0)
				return $cardData;
			else
				return false;
		}
		return false;
	}

	/**
	 * @refactor
	 */
	public function deleteWallet()
	{
		$cust 					= new Customer((int)($this->context->cookie->id_customer));

		$deleteWebWalletRequest['contractNumber']= Configuration::get('PAYLINE_CONTRACT_NUMBER');
		$this->getPaylineSDK()->walletIdList = array($this->getOrGenWalletId($cust->id));
		$deleteWebWalletRequest['cardInd'] = '';

		$result = $this->parseWsResult($this->getPaylineSDK()->disableWallet($deleteWebWalletRequest));

		if(isset($result) && $result['result']['code'] != '02500')
			return false;

		return true;
	}

	/**
	 * @refactor
	 */
	public function getOrGenWalletId($idCust){
		$walletId = $this->getWalletId($idCust);
		if(!$walletId){
			$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWZ";
			$salt = '';
			for ($p = 0; $p < 5; $p++) {
				$salt .= $characters[mt_rand(0, strlen($characters)-1)];
			}
			$walletId = $idCust.'_'.date('YmdHis').'_'.$salt;

			$this->setWalletId($idCust, $walletId);
		}
		return $walletId;
	}

	/**
	 * @refactor
	 */
	public function createWallet(){

		$cust 					= new Customer((int)($this->context->cookie->id_customer));
		$addresses				= $cust->getAddresses((int)$this->context->cookie->id_lang);
		if(sizeof($addresses))
			$DataAddressDelivery = new Address($addresses[0]['id_address']);

		$pays 					= new Country($DataAddressDelivery->id_country);

		// Log data
		$this->log('createWebWallet', array('Num Customer'=>$cust->id, 'Num Contract'=>Configuration::get('PAYLINE_CONTRACT_NUMBER'), 'Wallet ID' => $this->getOrGenWalletId($cust->id)));

		$createWebWalletRequest = array();

		// PRIVATE DATA
		$this->setPrivateData('idCust', $cust->id);

		// WALLET ID
		$createWebWalletRequest['buyer']['walletId'] = $this->getOrGenWalletId($cust->id);

		$createWebWalletRequest['updatePersonalDetails']= Configuration::get('PAYLINE_WALLET_PERSONNAL_DATA');

		$createWebWalletRequest['contractNumber']= Configuration::get('PAYLINE_CONTRACT_NUMBER');
		//SELECTED CONTRACT LIST
		//We retrieve all contract list
		$contracts = $this->getPaylineContracts();

		$cardAuthorizeByWallet = explode(',',Configuration::get('PAYLINE_AUTORIZE_WALLET_CARD'));

		if($contracts)
		{
			$cards = array();
			foreach($contracts as $contract)
			{
				if(in_array($contract['type'],$cardAuthorizeByWallet) AND !in_array($contract['contract'],$cards))
					$cards[] = $contract['contract'];
			}
		}

		if(sizeof($cards)) {
			$createWebWalletRequest['contracts'] = $cards;
		}

		// BUYER
		$createWebWalletRequest['buyer']['lastName'] 	= $cust->lastname;
		$createWebWalletRequest['buyer']['firstName'] 	= $cust->firstname;
		$createWebWalletRequest['buyer']['email'] 		= $cust->email;

		// ADDRESS
		$createWebWalletRequest['address'] = $this->getAddress($DataAddressDelivery);
		$createWebWalletRequest['billingAddress'] = $this->getAddress($DataAddressDelivery);
		$createWebWalletRequest['shippingAddress'] = $this->getAddress($DataAddressDelivery);

		$this->setPaylineSDKUrls(self::MODE_WALLET);

		$result = $this->parseWsResult($this->getPaylineSDK()->createWebWallet($createWebWalletRequest));

		if(isset($result) && $result['result']['code'] == '00000') {
			return $result['redirectURL'];
		}
		else
			return false;
	}

	/**
	 * @refactor
	 */
	public function updateWallet($cardInd){

		$cust 					= new Customer((int)($this->context->cookie->id_customer));
		$addresses				= $cust->getAddresses((int)$this->context->cookie->id_lang);
		if(sizeof($addresses))
			$DataAddressDelivery = new Address($addresses[0]['id_address']);

		$pays 					= new Country($DataAddressDelivery->id_country);


		//Log data
		$this->log('updateWebWallet', array('Num Customer'=>$cust->id, 'Num Contract'=>Configuration::get('PAYLINE_CONTRACT_NUMBER'), 'Wallet ID' => $this->getOrGenWalletId($cust->id)));

		$updateWebWalletRequest = array();
		$updateWebWalletRequest['updateOwnerDetails'] = false;
		$updateWebWalletRequest['shippingAddress'] = array();
		$updateWebWalletRequest['billingAddress'] = array();

		// PRIVATE DATA
		$this->setPrivateData('idCust', $cust->id);


		// WALLET ID
		$updateWebWalletRequest['walletId'] = $this->getOrGenWalletId($cust->id);

		$updateWebWalletRequest['updatePersonalDetails']= Configuration::get('PAYLINE_WALLET_PERSONNAL_DATA');
		$updateWebWalletRequest['updatePaymentDetails']= Configuration::get('PAYLINE_WALLET_PAYMENT_DATA');
		$updateWebWalletRequest['contractNumber']= Configuration::get('PAYLINE_CONTRACT_NUMBER');
		//SELECTED CONTRACT LIST
		//We retrieve all contract list
		$contracts = $this->getPaylineContracts();

		$cardAuthorizeByWallet = explode(',',Configuration::get('PAYLINE_AUTORIZE_WALLET_CARD'));

		if($contracts)
		{
			$cards = array();
			foreach($contracts as $contract)
			{
				if(in_array($contract['type'],$cardAuthorizeByWallet) AND !in_array($contract['contract'],$cards))
					$cards[] = $contract['contract'];
			}
		}

		if(sizeof($cards)) {
			$updateWebWalletRequest['contracts'] = $cards;
		}

		$updateWebWalletRequest['cardInd'] = $cardInd;

		// BUYER
		$updateWebWalletRequest['buyer']['lastName'] 	= $cust->lastname;
		$updateWebWalletRequest['buyer']['firstName'] 	= $cust->firstname;
		$updateWebWalletRequest['buyer']['email'] 		= $cust->email;

		// ADDRESS
		$updateWebWalletRequest['address'] = $this->getAddress($DataAddressDelivery);

		$this->setPaylineSDKUrls(self::MODE_WALLET);

		$result = $this->parseWsResult($this->getPaylineSDK()->updateWebWallet($updateWebWalletRequest));

		if(isset($result) && $result['result']['code'] == '00000') {
			return $result['redirectURL'];
		}
		else
			return false;
	}

	/**
	 * @refactor
	 */
	public function deleteCard($cardInd)
	{
		$cust 					= new Customer((int)($this->context->cookie->id_customer));

		$deleteWebWalletRequest['contractNumber']= Configuration::get('PAYLINE_CONTRACT_NUMBER');
		$this->getPaylineSDK()->walletIdList = array($this->getOrGenWalletId($cust->id));
		$deleteWebWalletRequest['cardInd'] = $cardInd;

		$result = $this->parseWsResult($this->getPaylineSDK()->disableWallet($deleteWebWalletRequest));

		if(isset($result) && $result['result']['code'] != '02500')
			return false;

		return true;
	}

	/**
	 * @refactor
	 */
	public function doRecurrentWalletPayment($paylineVars)
	{
		$this->log('params', $paylineVars);

		$address 				= new Address($this->context->cart->id_address_invoice);
		$cust 					= new Customer(intval($this->context->cart->id_customer));
		$pays 					= new Country($address->id_country);
		$DataAddressDelivery 	= new Address($this->context->cart->id_address_delivery);

		$currency 	 	 = new Currency(intval($this->context->cart->id_currency));

		$orderTotalWhitoutTaxes			= round($this->context->cart->getOrderTotal(false)*100);
		$orderTotal						= round($this->context->cart->getOrderTotal()*100);
		$taxes							= $orderTotal-$orderTotalWhitoutTaxes;

		$newOrderId = $this->l('Cart') . (int)$this->context->cart->id;

		//Log data
		$this->log('doRecurrentWalletPayment', array('Num cart'=>$this->context->cart->id, 'Periodicity' => Configuration::get('PAYLINE_SUBSCRIBE_PERIODICITY'), 'Num contract'=>$paylineVars['contractNumber'], 'Amount'=>$orderTotal, 'Start Date' => $paylineVars['date']));

		// End log Data
		$doWebPaymentRequest = array('version' => self::API_VERSION);

		$cardAuthoriseWallet = explode(',',Configuration::get('PAYLINE_AUTORIZE_WALLET_CARD'));

		//Retrieve payment mode
		$payment_mode = Configuration::get('PAYLINE_SUBSCRIBE_MODE');
		$payment_action = Configuration::get('PAYLINE_SUBSCRIBE_ACTION');
		$payment_by_wallet = true;

		if(isset($paylineVars['amountPaid']))
		{
			$orderTotalWhitoutTaxes = round($orderTotalWhitoutTaxes*$paylineVars['amountPaid']/$orderTotal);
			$orderTotal	= $paylineVars['amountPaid'];
		}
		$doWebPaymentRequest['recurring']['amount'] 		= $orderTotal;
		$doWebPaymentRequest['recurring']['firstAmount'] 	= $orderTotal;
		$doWebPaymentRequest['recurring']['billingCycle']	= Configuration::get('PAYLINE_SUBSCRIBE_PERIODICITY');

		if(isset($paylineVars['subscribeNumber']))
			$doWebPaymentRequest['recurring']['billingLeft']	= $paylineVars['subscribeNumber'];
		else
			$doWebPaymentRequest['recurring']['billingLeft']	= Configuration::get('PAYLINE_SUBSCRIBE_NUMBER');

		$doWebPaymentRequest['recurring']['billingDay']		= Configuration::get('PAYLINE_SUBSCRIBE_DAY') ? Configuration::get('PAYLINE_SUBSCRIBE_DAY') : date('d');

		$doWebPaymentRequest['recurring']['startDate']		= $paylineVars['date'];

		// PAYMENT
		$doWebPaymentRequest['payment']['amount'] 			= $doWebPaymentRequest['recurring']['firstAmount']+($doWebPaymentRequest['recurring']['billingLeft']-1)*$doWebPaymentRequest['recurring']['amount'];
		$doWebPaymentRequest['payment']['currency'] 		= $currency->iso_code_num;
		$doWebPaymentRequest['payment']['contractNumber']	= $paylineVars['contractNumber'];
		$doWebPaymentRequest['payment']['mode']				= $payment_mode;
		$doWebPaymentRequest['payment']['action']			= $payment_action;

		// ORDER
		$doWebPaymentRequest['order']['ref'] 				= $newOrderId;
		$doWebPaymentRequest['order']['country'] 			= $pays->iso_code;
		$doWebPaymentRequest['order']['taxes'] 				= $taxes;
		$doWebPaymentRequest['order']['amount'] 			= $orderTotalWhitoutTaxes;
		$doWebPaymentRequest['order']['date'] 				= date('d/m/Y H:i');
		$doWebPaymentRequest['order']['currency'] 			= $doWebPaymentRequest['payment']['currency'];

		$this->addCartProducts($this->context->cart);

		// PRIVATE DATA
		$this->setPrivateData('idOrder', $newOrderId);
		$this->setPrivateData('idCart', $this->context->cart->id);
		$this->setPrivateData('idCust', $cust->id);

		$doWebPaymentRequest['contracts'] = array(Configuration::get('PAYLINE_CONTRACT_NUMBER'));
		$doWebPaymentRequest['secondContracts'] = $this->getSecondaryCardList(self::MODE_WALLET);;

		// BUYER
		$doWebPaymentRequest['buyer']['lastName'] 	= $cust->lastname;
		$doWebPaymentRequest['buyer']['firstName'] 	= $cust->firstname;
		$doWebPaymentRequest['buyer']['email'] 		= $cust->email;

		$doWebPaymentRequest['walletId'] = $this->getWalletId($cust->id);
		$doWebPaymentRequest['cardInd'] = $paylineVars['cardInd'];
		$doWebPaymentRequest['orderRef'] = '';
		$doWebPaymentRequest['orderDate'] = '';

		// ADDRESS
		$doWebPaymentRequest['address'] = $this->getAddress($DataAddressDelivery);

		$this->setPaylineSDKUrls(self::MODE_SUBSCRIBE);

		$result = $this->parseWsResult($this->getPaylineSDK()->doRecurrentWalletPayment($doWebPaymentRequest));

		return $result;
	}

	/**
	 * @refactor
	 */
	public function regenerateRecurrentWalletPayment($paylineVars)
	{

		$cart 					= new Cart($paylineVars['id_cart']);
		$address 				= new Address($cart->id_address_invoice);
		$cust 					= new Customer(intval($cart->id_customer));
		$pays 					= new Country($address->id_country);
		$DataAddressDelivery 	= new Address($cart->id_address_delivery);

		$currency 	 	 = new Currency(intval($cart->id_currency));

		$orderTotalWhitoutTaxes			= round($cart->getOrderTotal(false)*100);
		$orderTotal						= round($cart->getOrderTotal()*100);
		$taxes							= $orderTotal-$orderTotalWhitoutTaxes;

		$newOrderId = $this->l('Cart') . (int)$cart->id;

		//Log data
		$this->log('regenerateRecurrentWalletPayment', array('Num cart'=>$cart->id, 'Periodicity' => $paylineVars['periodicity'], 'Num contract'=>$paylineVars['contractNumber'], 'Amount'=>$paylineVars['amount'], 'Start Date' => $paylineVars['date']));

		$doWebPaymentRequest = array('version' => self::API_VERSION);

		$cardAuthoriseWallet = explode(',',Configuration::get('PAYLINE_AUTORIZE_WALLET_CARD'));

		//Retrieve payment mode
		$payment_mode = Configuration::get('PAYLINE_SUBSCRIBE_MODE');
		$payment_action = Configuration::get('PAYLINE_SUBSCRIBE_ACTION');
		$payment_by_wallet = true;

		$doWebPaymentRequest['recurring']['amount'] 		= $paylineVars['amount'];
		$doWebPaymentRequest['recurring']['firstAmount'] 	= $paylineVars['amount'];
		$doWebPaymentRequest['recurring']['billingCycle']	= $paylineVars['periodicity'];
		$doWebPaymentRequest['recurring']['billingLeft']	= $paylineVars['subscribeNumber'];
		$doWebPaymentRequest['recurring']['billingDay']		= Configuration::get('PAYLINE_SUBSCRIBE_DAY') ? Configuration::get('PAYLINE_SUBSCRIBE_DAY') : date('d');
		$doWebPaymentRequest['recurring']['startDate']		= $paylineVars['date'];

		// PAYMENT
		$doWebPaymentRequest['payment']['amount'] 			= $doWebPaymentRequest['recurring']['firstAmount']+($doWebPaymentRequest['recurring']['billingLeft']-1)*$doWebPaymentRequest['recurring']['amount'];
		$doWebPaymentRequest['payment']['currency'] 		= $currency->iso_code_num;
		$doWebPaymentRequest['payment']['contractNumber']	= $paylineVars['contractNumber'];
		$doWebPaymentRequest['payment']['mode']				= $payment_mode;
		$doWebPaymentRequest['payment']['action']			= $payment_action;

		// ORDER
		$doWebPaymentRequest['order']['ref'] 				= $newOrderId;
		$doWebPaymentRequest['order']['country'] 			= $pays->iso_code;
		$doWebPaymentRequest['order']['taxes'] 				= $taxes;
		$doWebPaymentRequest['order']['amount'] 			= $orderTotalWhitoutTaxes;
		$doWebPaymentRequest['order']['date'] 				= date('d/m/Y H:i');
		$doWebPaymentRequest['order']['currency'] 			= $doWebPaymentRequest['payment']['currency'];

		$this->addCartProducts($cart);

		// PRIVATE DATA
		$this->setPrivateData('idOrder', $newOrderId);
		$this->setPrivateData('idCart', $cart->id);
		$this->setPrivateData('idCust', $cust->id);

		$doWebPaymentRequest['contracts'] = array(Configuration::get('PAYLINE_CONTRACT_NUMBER'));
		$doWebPaymentRequest['secondContracts'] = $this->getSecondaryCardList(self::MODE_WALLET);

		// BUYER
		$doWebPaymentRequest['buyer']['lastName'] 	= $cust->lastname;
		$doWebPaymentRequest['buyer']['firstName'] 	= $cust->firstname;
		$doWebPaymentRequest['buyer']['email'] 		= $cust->email;

		$doWebPaymentRequest['walletId'] = $this->getWalletId($cust->id);
		$doWebPaymentRequest['orderRef'] = '';
		$doWebPaymentRequest['orderDate'] = '';

		// ADDRESS
		$doWebPaymentRequest['address'] = $this->getAddress($DataAddressDelivery);

		$this->setPaylineSDKUrls(self::MODE_SUBSCRIBE);

		$result = $this->parseWsResult($this->getPaylineSDK()->doRecurrentWalletPayment($doWebPaymentRequest));

		return $result;
	}

	# SUBSCRIBE

	/**
	 * @refactor
	 */
	public function setPaylineSubscribe($idCust,$idCart,$paymentRecordId,$cardInd,$contractNumber,$periodicity){
		$request = '
			INSERT INTO `'._DB_PREFIX_.'payline_subscribe` (
					`id_customer`,
					`id_cart`,
					`paymentRecordId`,
					`cardInd`,
					`contractNumber`,
					`periodicity`
				)VALUES (
					\''.$idCust.'\',
					\''.$idCart.'\',
					\''.$paymentRecordId.'\',
					\''.$cardInd.'\',
					\''.$contractNumber.'\',
					\''.$periodicity.'\'
				)';
		$result = Db::getInstance()->Execute($request);
		if (!$result) return false;
		return  Db::getInstance()->Insert_ID();
	}

	/**
	 * @refactor
	 */
	public function getPaylineSubscribe($paymentRecordId){
		$request = '
				SELECT `id_payline_subscribe`
				FROM `'._DB_PREFIX_.'payline_subscribe`
				WHERE `paymentRecordId` = \''.$paymentRecordId.'\'
				';

		$result = Db::getInstance()->getValue($request);
		if (!$result) return false;
		return $result;
	}

	/**
	 * @refactor
	 */
	public function getPaylineSubscribeByCustomer(){
		$request = '
				SELECT `id_payline_subscribe`, `paymentRecordId`
				FROM `'._DB_PREFIX_.'payline_subscribe`
				WHERE `id_customer` = \''.$this->context->cookie->id_customer.'\'
				';

		$result = Db::getInstance()->ExecuteS($request);
		if (!$result) return false;
		return $result;
	}

	/**
	 * @refactor
	 */
	public function getPaylineSubscribeData($paymentRecordId){
		$request = '
				SELECT `id_payline_subscribe`, `paymentRecordId`, `contractNumber`, `cardInd`,`id_cart`,`periodicity`
				FROM `'._DB_PREFIX_.'payline_subscribe`
				WHERE `id_customer` = \''.$this->context->cookie->id_customer.'\'
				AND `paymentRecordId` = \''.$paymentRecordId.'\'
				';

		$result = Db::getInstance()->ExecuteS($request);
		if (!$result) return false;
		return $result;
	}

	public function getPaylineSubscribeDataByCartId($nCartId){
		$request = '
				SELECT `id_payline_subscribe`, `paymentRecordId`, `contractNumber`, `cardInd`,`id_cart`,`periodicity`
				FROM `'._DB_PREFIX_.'payline_subscribe`
				WHERE `id_cart` = '.(int)$nCartId;

		$result = Db::getInstance()->getRow($request);
		if (!$result) return false;
		return $result;
	}
	/**
	 * @refactor
	 */
	public function verifyPaymentRecordByUserId($paymentRecordId){
		$request = '
				SELECT `id_payline_subscribe`
				FROM `'._DB_PREFIX_.'payline_subscribe`
				WHERE `id_customer` = \''.$this->context->cookie->id_customer.'\'
				AND `paymentRecordId` = \''.$paymentRecordId.'\'
				';

		$result = Db::getInstance()->getValue($request);
		if (!$result) return false;
		return $result;
	}

	/**
	 * @refactor
	 */
	public function getOrderIdByPaymentRecord($paymentRecordId){
		$request = '
				SELECT `id_cart`
				FROM `'._DB_PREFIX_.'payline_subscribe`
				WHERE `id_customer` = \''.$this->context->cookie->id_customer.'\'
				AND `paymentRecordId` = \''.$paymentRecordId.'\'
				';

		$result = Db::getInstance()->getValue($request);
		if (!$result) return false;
		return $result;
	}

	/**
	 * @refactor
	 */
	public function getLastPaymentRecordByIdCart($paymentRecordId,$id_cart){
		$request = '
				SELECT `paymentRecordId`
				FROM `'._DB_PREFIX_.'payline_subscribe`
				WHERE `id_cart` = \''.$id_cart.'\'
				AND `paymentRecordId` <> \''.$paymentRecordId.'\'
				';

		$result = Db::getInstance()->getValue($request);
		if (!$result) return false;
		return $result;
	}

	/**
	 * @refactor
	 */
	public function getSumPendingSubscribe($idPaylineSubscribe)
	{
		$request = '
				SELECT COUNT(`id_payline_subscribe_state`) as sumPending
				FROM `'._DB_PREFIX_.'payline_subscribe_state`
				WHERE `id_payline_subscribe` = \''.$idPaylineSubscribe.'\'
				AND `status` = 0
				';

		$result = Db::getInstance()->getValue($request);
		if (!$result) return 0;
		return $result;
	}

	/**
	 * @refactor
	 */
	public function getOrderBySubscribeId($idPaylineSubscribe){

		$request = '
			SELECT o.*
			FROM `'._DB_PREFIX_.'payline_subscribe_order` pso
			LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = pso.`id_order`)
			WHERE `id_payline_subscribe` = \''.$idPaylineSubscribe.'\'
			ORDER BY `id_order` DESC
			';
		$result = Db::getInstance()->ExecuteS($request);

		if (!$result) return false;

		foreach ($result as $key => $val)
		{
			$result2 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
				SELECT os.`id_order_state`, osl.`name` AS order_state, os.`invoice`
				FROM `'._DB_PREFIX_.'order_history` oh
				LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = oh.`id_order_state`)
				INNER JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.$this->context->language->id.')
			WHERE oh.`id_order` = '.(int)($val['id_order']).'
				ORDER BY oh.`date_add` DESC, oh.`id_order_history` DESC
			LIMIT 1');

			if ($result2)
				$result[$key] = array_merge($result[$key], $result2[0]);

		}
		return $result;
	}

	/**
	 * @refactor
	 */
	public function getLastOrderSate($idPaylineSubscribe){
		$request = '
				SELECT `status`
				FROM `'._DB_PREFIX_.'payline_subscribe_state`
				WHERE `id_payline_subscribe` = \''.$idPaylineSubscribe.'\'
				ORDER BY `id_payline_subscribe_state` DESC
				LIMIT 1';

		$result = Db::getInstance()->ExecuteS($request);
		if (!isset($result[0]['status'])) return false;
		return $result[0]['status'];
	}

	/**
	 * @refactor
	 */
	public function setPaylineSubscribeOrder($idPaylineSubscribe,$idOrder){
		$request = '
			INSERT INTO `'._DB_PREFIX_.'payline_subscribe_order` (
					`id_payline_subscribe`,
					`id_order`
				)VALUES (
					\''.$idPaylineSubscribe.'\',
					\''.$idOrder.'\'
				)';
		$result = Db::getInstance()->Execute($request);
		if (!$result) return false;
		return true;
	}

	/**
	 * @refactor
	 */
	public function setPaylineSubscribeState($idPaylineSubscribe,$state){
		$request = '
			INSERT INTO `'._DB_PREFIX_.'payline_subscribe_state` (
					`id_payline_subscribe`,
					`status`,
					`date_add`
				)VALUES (
					\''.$idPaylineSubscribe.'\',
					\''.$state.'\',
					\''.date('Y-m-d H:i:s').'\'
				)';
		$result = Db::getInstance()->Execute($request);
		if (!$result) return false;
		return true;
	}

	/**
	 * @refactor
	 */
	public function updatePaylineSubscribe($oldPaymentRecordId,$newPaymentRecordId){
		$request = '
			UPDATE `'._DB_PREFIX_.'payline_subscribe`
			SET `paymentRecordId` = \''.$newPaymentRecordId.'\'
			WHERE `id_customer` = \''.$this->context->cookie->id_customer.'\'
			AND `paymentRecordId` = \''.$oldPaymentRecordId.'\'
			';
		$result = Db::getInstance()->Execute($request);
		if (!$result) return false;
		return true;
	}

	/**
	 * Returns payment record
	 * @param string $sPaymentRecordId
	 * @return false|array Payment record if it is valid, false otherwise
	 */
	public function getPaymentRecord($sPaymentRecordId){

		$aRequest = array(
				'contractNumber' 	=> Configuration::get('PAYLINE_CONTRACT_NUMBER'),
				'paymentRecordId'	=> $sPaymentRecordId
		);

		$aResult = $this->parseWsResult($this->getPaylineSDK()->getPaymentRecord($aRequest));

		if (!$aResult || ($aResult && !$this->isResultInSet($aResult['result']['code'], self::RS_VALID_WALLET))){

			return false;

		} // if

		return $aResult;

	} // getPaymentRecord

	/**
	 * @refactor
	 */
	public function disablePaymentRecord($paymentRecordId,$newPaymentRecordId = false, $regenerate=false)
	{
		if(!$newPaymentRecordId)
			$idPaylineSubscribe = $this->verifyPaymentRecordByUserId($paymentRecordId);
		else
			$idPaylineSubscribe = $this->verifyPaymentRecordByUserId($newPaymentRecordId);

		if($idPaylineSubscribe)
		{

			$paymentDisabelRecordIdRequest = array();

			if(!$newPaymentRecordId)
				$dataSubscribe = $this->getPaylineSubscribeData($paymentRecordId);
			else
				$dataSubscribe = $this->getPaylineSubscribeData($newPaymentRecordId);

			if($dataSubscribe)
				$contractNumber 	= $dataSubscribe[0]['contractNumber'];
			else
				return false;

			$paymentDisabelRecordIdRequest['contractNumber'] = $contractNumber;
			$paymentDisabelRecordIdRequest['paymentRecordId'] = $paymentRecordId;

			$result = $this->parseWsResult($this->getPaylineSDK()->disablePaymentRecord($paymentDisabelRecordIdRequest));

			if(isset($result) AND $result['result']['code'] != '02500' AND $result['result']['code'] != '02501' AND $result['result']['code'] != '00000')
				return false;
			if(!$regenerate)
				$this->setPaylineSubscribeState($idPaylineSubscribe,-1);
			else
				$this->setPaylineSubscribeState($idPaylineSubscribe,0);
			return true;
		}
		else
			return false;
	}

	# TRANSACTIONS

	/**
	 * @refactor
	 */
	protected function _getTransactionId($id_order)
	{
		if (!(int)$id_order)
			return false;

		return Db::getInstance()->getValue('
		SELECT `id_transaction`
		FROM `'._DB_PREFIX_.'payline_order`
		WHERE `id_order` = '.(int)$id_order);
	}

	/**
	 * @refactor
	 */
	protected function _getContractNumberByTransaction($id_order)
	{
		if (!(int)$id_order)
			return false;

		return Db::getInstance()->getValue('
		SELECT `contract_number`
		FROM `'._DB_PREFIX_.'payline_order`
		WHERE `id_order` = '.(int)$id_order);
	}

	/**
	 * @refactor
	 */
	protected function _getAmountByTransaction($id_order)
	{
		if (!(int)$id_order)
			return false;

		return Db::getInstance()->getValue('
		SELECT `amount`
		FROM `'._DB_PREFIX_.'payline_order`
		WHERE `id_order` = '.(int)$id_order);
	}

	/**
	 * @refactor
	 */
	protected function _getPaymentByOder($id_order)
	{
		if (!(int)$id_order)
			return false;

		return Db::getInstance()->getValue('
		SELECT `payment_by`
		FROM `'._DB_PREFIX_.'payline_order`
		WHERE `id_order` = '.(int)$id_order);
	}

	# CAPTURE

	/**
	 * @refactor
	 */
	public function _canCapture($id_order)
	{
		if (!(int)$id_order)
			return false;
		$payline_order = Db::getInstance()->getRow('
		SELECT *
		FROM `'._DB_PREFIX_.'payline_order`
		WHERE `id_order` = '.(int)$id_order);
		if (!is_array($payline_order) OR !sizeof($payline_order))
			return false;
		if ($payline_order['payment_status'] != 'authorization')
			return false;
		return true;
	}

	/**
	 * @refactor
	 */
	public function _doTotalCapture($id_order)
	{
		if (!$id_order)
			return false;

		$id_transaction = $this->_getTransactionId((int)($id_order));
		if (!$id_transaction)
			return false;

		$contract_number = $this->_getContractNumberByTransaction((int)($id_order));
		if (!$contract_number)
			return false;

		$amount = $this->_getAmountByTransaction((int)($id_order));
		if (!$amount)
			return false;

		$order = new Order((int)($id_order));
		if (!Validate::isLoadedObject($order))
			return false;


		// check if the payment was made there more than 7 days
		if(strtotime($order->date_add) > mktime(23,59,59,date('m'),date('d')-7,date('Y')))
			$response = $this->_makeCapture($id_transaction, $contract_number, (int)($id_order), (int)($amount));
		else
			$response = $this->_makeReauthorization($id_transaction, $contract_number, (int)($id_order), (int)($amount));

		$message = $this->l('Capture operation result:') . "\n";

		if(isset($response) && $response['result']['code'] == '00000'){
			$message .= $this->l('Payline capture successful!') . "\n";
			$message .= $this->l('Transaction ID:') . ' ' . $response['transaction']['id'];
			if (!Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'payline_order` SET `payment_status` = \'capture\' WHERE `id_order` = '.(int)($id_order)))
				die(Tools::displayError('Error when updating Payline database'));
		}
		else
			$message .= $this->l('Transaction capture error!');

		$history = new OrderHistory();
		$history->id_order = (int)($id_order);
		$history->changeIdOrderState(_PS_OS_PAYMENT_, (int)($id_order));
		$history->addWithemail();

		$this->addPrivateMessage((int)($id_order), $message);

		return $response;
	}

	/**
	 * @refactor
	 */
	protected function _makeCapture($id_transaction, $contract_number, $id_order, $amount)
	{

		$order = new Order($id_order);
		$cart = new Cart($order->id_cart);

		$currency 	 	 = new Currency(intval($cart->id_currency));

		$doWebCaptureRequest['transactionID'] 					= $id_transaction;
		$doWebCaptureRequest['payment']['currency'] 			= $currency->iso_code_num;
		$doWebCaptureRequest['payment']['contractNumber']		= $contract_number;
		$doWebCaptureRequest['payment']['amount']				= $amount;
		$doWebCaptureRequest['payment']['mode']					= 'CPT';
		$doWebCaptureRequest['payment']['action']				= 201;
		$doWebCaptureRequest['payment']['differedActionDate']	= '';
		$doWebCaptureRequest['sequenceNumber'] 					= '';
		$doWebCaptureRequest['comment'] 						= $this->l('Capture from the back office Prestashop');

		//Log data
		$this->log('Capture order', array('Num order'=>$id_order, 'Num Cart'=>$order->id_cart, 'Id transaction' => $id_transaction, 'Num Contract' => $contract_number, 'Amount' => $doWebCaptureRequest['payment']['amount']));

		$result = $this->parseWsResult($this->getPaylineSDK()->doCapture($doWebCaptureRequest));

		return $result;
	}

	/**
	 * @refactor
	 */
	protected function _makeReauthorization($id_transaction, $contract_number, $id_order, $amount)
	{

		$order = new Order($id_order);
		$cart = new Cart($order->id_cart);

		$currency 	 	 = new Currency(intval($cart->id_currency));

		$doWebCaptureRequest['transactionID'] 					= $id_transaction;
		$doWebCaptureRequest['payment']['currency'] 			= $currency->iso_code_num;
		$doWebCaptureRequest['payment']['contractNumber']		= $contract_number;
		$doWebCaptureRequest['payment']['amount']				= $amount;
		$doWebCaptureRequest['payment']['mode']					= 'CPT';
		$doWebCaptureRequest['payment']['action']				= 101;
		$doWebCaptureRequest['payment']['differedActionDate']	= '';
		$doWebCaptureRequest['sequenceNumber'] 					= '';
		$doWebCaptureRequest['comment'] 						= $this->l('Capture from the back office Prestashop');
		$doWebCaptureRequest['order']['ref'] 					= $currency->iso_code_num;
		$doWebCaptureRequest['order']['origin']					= $contract_number;
		$doWebCaptureRequest['order']['country']				= $contract_number;
		$doWebCaptureRequest['order']['taxes']					= 'CPT';
		$doWebCaptureRequest['order']['amount']					= $amount;
		$doWebCaptureRequest['order']['currency']				= $currency;
		$doWebCaptureRequest['order']['date']					= $order->date_add;

		//Log data
		$this->log('Make Reauthorization', array('Num order'=>$id_order, 'Num Cart'=>$order->id_cart, 'Id transaction' => $id_transaction, 'Num Contract' => $contract_number, 'Amount' => $amount));

		$result = $this->parseWsResult($this->getPaylineSDK()->doReAuthorization($doWebCaptureRequest));

		return $result;
	}

	# REFUND

	/**
	 * @refactor
	 */
	public function _canRefund($id_order)
	{
		if (!(int)$id_order)
			return false;
		$payline_order = Db::getInstance()->getRow('
		SELECT *
		FROM `'._DB_PREFIX_.'payline_order`
		WHERE `id_order` = '.(int)$id_order . '
		AND `id_transaction` NOT LIKE "SD%"');
		if (!is_array($payline_order) OR !sizeof($payline_order))
			return false;
		if ($payline_order['payment_status'] != 'capture')
			return false;
		return true;
	}

	/**
	 * @refactor
	 */
	public function _doTotalRefund($id_order)
	{
		if (!$id_order)
			return false;

		$id_transaction = $this->_getTransactionId((int)($id_order));
		if (!$id_transaction)
			return false;

		$contract_number = $this->_getContractNumberByTransaction((int)($id_order));
		if (!$contract_number)
			return false;

		$order = new Order((int)($id_order));
		if (!Validate::isLoadedObject($order))
			return false;

		$order_slip_detail_list = array();
		$products = $order->getProducts();
		// Amount for refund
		$amt = 0.00;
		foreach ($products AS $id_order_detail => $product)
		{
			if (($product['product_quantity'] - $product['product_quantity_refunded']) > 0) {
				$order_slip_detail_list[(int)$id_order_detail] = array(
                    'id_order_detail' => $id_order_detail,
                    'quantity' => ($product['product_quantity'] - $product['product_quantity_refunded']),
                    'unit_price' => (float)$product['unit_price_tax_excl'],
                    'amount' => $product['unit_price_tax_incl'] * ($product['product_quantity'] - $product['product_quantity_refunded']),
				);
			}
            $amt += round(($product['product_price_wt']*$product['product_quantity']),2);
		}

		$amt += (float)($order->total_shipping);

		// get previous refund
		$orderPaymentList = OrderPayment::getByOrderId($order->id);
		if (is_array($orderPaymentList) && sizeof($orderPaymentList))
			foreach ($orderPaymentList as $orderPayment)
				if ($orderPayment->amount < 0 && $orderPayment->payment_method == $this->displayName)
					$amt += (float)$orderPayment->amount;

		// check if total or partial
		if ($order->total_products_wt == $amt)
			$response = $this->_makeRefund($id_transaction, $contract_number, (int)$id_order);
		else
			$response = $this->_makeRefund($id_transaction, $contract_number, (int)$id_order, (float)$amt);
		$message = $this->l('Refund operation result:') . "\n";

		if(isset($response) && $response['result']['code'] == '00000'){
			$message .= $this->l('Payline refund successful!') . "\n";
			$message .= $this->l('Amount:') . ' ' . $amt .  "\n";
			$message .= $this->l('Transaction ID:') . ' ' . $response['transaction']['id']. "\n";
			
			$history = new OrderHistory();
			$history->id_order = (int)$id_order;
			$history->changeIdOrderState(_PS_OS_REFUND_, (int)$id_order);
			$history->addWithemail();

			// Reload Order (because it has been edited by OrderHistory)
			$order = new Order((int)$id_order);
			
			$orderInvoice = new OrderInvoice($order->invoice_number);
			if (!Validate::isLoadedObject($orderInvoice))
				$orderInvoice = null;
			
			if (!Db::getInstance()->Execute('UPDATE `'._DB_PREFIX_.'payline_order` SET `payment_status` = \'Refunded\',  `id_transaction` = \''.$response['transaction']['id'].'\' WHERE `id_order` = '.(int)($id_order)))
				die(Tools::displayError('Error when updating Payline database'));
			
			// Add order slip
            if (method_exists('OrderSlip', 'create')) {
                // Use latest available method (since PS 1.6.0.11)
                OrderSlip::create($order, $order_slip_detail_list, null);
            } else {
                $this->createPartialOrderSlip($order, $amt, $order->total_shipping_tax_incl, $order_slip_detail_list);
            }
			$this->addOrderPayment($order, $amt * -1, null, $response['transaction']['id'], null, null, $orderInvoice);
		} else {
			$message .= $this->l('Transaction error!') . "\n";
			$message .= $this->l('Error code:') . ' ' . $response['result']['code'];
		}
		$this->addPrivateMessage((int)$id_order, $message);

		return $response;
	}

    private function createPartialOrderSlip($order, $amount, $shipping_cost_amount, $order_detail_list)
    {
        $currency = new Currency($order->id_currency);
        $orderSlip = new OrderSlip();
        $orderSlip->id_customer = (int)$order->id_customer;
        $orderSlip->id_order = (int)$order->id;
        $orderSlip->amount = (float)$amount;
        $orderSlip->shipping_cost = false;
        $orderSlip->shipping_cost_amount = (float)$shipping_cost_amount;
        $orderSlip->conversion_rate = $currency->conversion_rate;

        // Fill missing required fields
        $orderSlip->total_products_tax_excl = 0;
        $orderSlip->total_products_tax_incl = 0;
        $orderSlip->total_shipping_tax_excl = 0;
        $orderSlip->total_shipping_tax_incl = 0;
        if ($orderSlip->shipping_cost_amount) {
            $orderSlip->total_shipping_tax_excl = (float)$order->total_shipping_tax_excl;
            $orderSlip->total_shipping_tax_incl = (float)$order->total_shipping_tax_incl;
        }
        foreach ($order_detail_list as $product) {
            $orderSlip->total_products_tax_excl += (float)$product['unit_price'] * $product['quantity'];
            $orderSlip->total_products_tax_incl += (float)$product['amount'];
        }
        // /Fill missing required fields

        $orderSlip->partial = 1;
        if (!$orderSlip->add()) {
            return false;
        }

        $orderSlip->addPartialSlipDetail($order_detail_list);
        return true;
    }

	/*
	* Clone of Order::addOrderPayment()
	* we force total_paid_real to be = 0 instead of a negative value, so we can update without warning
	*/
	public function addOrderPayment($order, $amount_paid, $payment_method = null, $payment_transaction_id = null, $currency = null, $date = null, $order_invoice = null) {
		$order_payment = new OrderPayment();
		$order_payment->order_reference = $order->reference;
		$order_payment->id_currency = ($currency ? $currency->id : $order->id_currency);
		// we kept the currency rate for historization reasons
		$order_payment->conversion_rate = ($currency ? $currency->conversion_rate : 1);
		// if payment_method is define, we used this
		$order_payment->payment_method = ($payment_method ? $payment_method : $order->payment);
		$order_payment->transaction_id = $payment_transaction_id;
		$order_payment->amount = $amount_paid;
		$order_payment->date_add = ($date ? $date : null);

		// Force total_paid_real to 0
		$order->total_paid_real = 0;

		// We put autodate parameter of add method to true if date_add field is null
		$res = $order_payment->add(is_null($order_payment->date_add)) && $order->update();
		
		if (!$res)
			return false;
	
		if (!is_null($order_invoice))
		{
			$res = Db::getInstance()->execute('
			INSERT INTO `'._DB_PREFIX_.'order_invoice_payment` (`id_order_invoice`, `id_order_payment`, `id_order`)
			VALUES('.(int)$order_invoice->id.', '.(int)$order_payment->id.', '.(int)$order->id.')');

			// Clear cache
			Cache::clean('order_invoice_paid_*');
		}
		
		return $res;
	}

	/**
	 * @refactor
	 */
	protected function _makeRefund($id_transaction, $contract_number, $id_order, $amt = false)
	{

		$order = new Order($id_order);
		$cart = new Cart($order->id_cart);

		$currency 	 	 = new Currency(intval($cart->id_currency));

		if(!$amt)
			$doWebRefundRequest['payment']['amount'] = round($order->total_paid_real*100);
		else
			$doWebRefundRequest['payment']['amount'] = round($amt*100);

		$doWebRefundRequest['transactionID'] 					= $id_transaction;
		$doWebRefundRequest['payment']['currency'] 				= $currency->iso_code_num;
		$doWebRefundRequest['payment']['contractNumber']		= $contract_number;
		$doWebRefundRequest['payment']['mode']					= 'CPT';
		$doWebRefundRequest['payment']['action']				= 421;
		$doWebRefundRequest['payment']['differedActionDate']	= '';
		$doWebRefundRequest['sequenceNumber'] 					= '';
		$doWebRefundRequest['comment'] 							= $this->l('Refund from the back office Prestashop');

		//Log data

		$this->log('Refund order', array('id_order'=>$id_order, 'Num cart'=>$order->id_cart, 'Id transaction'=>$id_transaction, 'Num contract'=>$contract_number, 'Amount'=>$doWebRefundRequest['payment']['amount']) );

		$result = $this->parseWsResult($this->getPaylineSDK()->doRefund($doWebRefundRequest));

		return $result;
	}

	private function doWebPaymentRequest($paylineVars){
		$invoiceAddress			= new Address($this->context->cart->id_address_invoice);
		$deliveryAddress 		= new Address($this->context->cart->id_address_delivery);
		$cust 					= new Customer(intval($this->context->cookie->id_customer));
		$pays 					= new Country($invoiceAddress->id_country);
		$paysLivraison			= new Country($deliveryAddress->id_country);

		$currency 	 	 = new Currency(intval($this->context->cart->id_currency));

		$orderTotalWhitoutTaxes			= round($this->context->cart->getOrderTotal(false)*100);
		$orderTotal						= round($this->context->cart->getOrderTotal()*100);
		$taxes							= $orderTotal-$orderTotalWhitoutTaxes;

		$newOrderId = $this->l('Cart') . (int)$this->context->cart->id;

		$doWebPaymentRequest = array('version' => self::API_VERSION);

		$cardAuthoriseWallet = explode(',',Configuration::get('PAYLINE_AUTORIZE_WALLET_CARD'));

		//Log data
		$this->log('DoWebPayment', array('Payment mode'=>$paylineVars['mode'], 'Num Cart' => $newOrderId, 'Num Contract' => $paylineVars['contractNumber']));
		//Retrieve payment mode
		$bActivateWalletPayment = false;

		switch($paylineVars['mode'])
		{
			case 'webCash' :
				$payment_mode = Configuration::get('PAYLINE_WEB_CASH_MODE');
				$payment_action = Configuration::get('PAYLINE_WEB_CASH_ACTION');
				if(Configuration::get('PAYLINE_WEB_CASH_BY_WALLET') && (in_array($paylineVars['type'],$cardAuthoriseWallet) || in_array(Configuration::get('PAYLINE_WEB_CASH_UX'),array(Payline::WIDGET_COLUMN,Payline::WIDGET_TAB)) ) ){
					$bActivateWalletPayment = true;
				}
			break;

			case 'recurring' :
				$payment_mode = Configuration::get('PAYLINE_RECURRING_MODE');
				$payment_action = Configuration::get('PAYLINE_RECURRING_ACTION');

				if(Configuration::get('PAYLINE_RECURRING_BY_WALLET') && in_array($paylineVars['type'],$cardAuthoriseWallet)){
					$bActivateWalletPayment = true;

				}
				
				$nUnitAmount = 0;
				$nFirstAmount = 0;
				if(Configuration::get('PAYLINE_RECURRING_FIRST_WEIGHT')>0){
					$nFirstAmount = round($orderTotal*Configuration::get('PAYLINE_RECURRING_FIRST_WEIGHT')/100);
					$nUnitAmount = round(($orderTotal-$nFirstAmount)/(Configuration::get('PAYLINE_RECURRING_NUMBER')-1));
					$delta = $orderTotal-($nFirstAmount+($nUnitAmount*(Configuration::get('PAYLINE_RECURRING_NUMBER')-1)));
					$nFirstAmount += $delta;
				}else{
					$nUnitAmount = round($orderTotal/Configuration::get('PAYLINE_RECURRING_NUMBER'));
					$nFirstAmount = $orderTotal - ($nUnitAmount * (Configuration::get('PAYLINE_RECURRING_NUMBER')-1));
				}
				
				$doWebPaymentRequest['recurring']['amount'] 		= $nUnitAmount;
				$doWebPaymentRequest['recurring']['firstAmount'] 	= $nFirstAmount;
				$doWebPaymentRequest['recurring']['billingCycle']	= Configuration::get('PAYLINE_RECURRING_PERIODICITY');
				$doWebPaymentRequest['recurring']['billingLeft']	= Configuration::get('PAYLINE_RECURRING_NUMBER');
				$doWebPaymentRequest['recurring']['billingDay']		= '';
				$doWebPaymentRequest['recurring']['startDate']		= '';


				$this->log('paymentRequest', $doWebPaymentRequest['recurring']);


				break;

			case 'subscribe' :

				$payment_mode = Configuration::get('PAYLINE_SUBSCRIBE_MODE');
				$payment_action = Configuration::get('PAYLINE_SUBSCRIBE_ACTION');
				$bActivateWalletPayment = true;

				if (Configuration::get('PAYLINE_SUBSCRIBE_GIFT_ACTIVE')){

					// removing anothers gifts
					$this->removePaylineGifts($this->context->cart);

					// creating unique gift voucher
					$nCartRuleId = $this->createCartRule($this->context->cart);

					if ($nCartRuleId){

						$this->context->cart->addCartRule($nCartRuleId);

					} // if

				} // if

				list($firstAmount, $nUnitAmount, $orderTotalWhitoutTaxes, $taxes) = $this->getSubscribtionAmounts($this->context->cart, Configuration::get('PAYLINE_SUBSCRIBE_NUMBER'));
				
        $cfgStartDate = (int)Configuration::get('PAYLINE_SUBSCRIBE_START_DATE'); // 1 => à la commande / 2 => après 1 période / 3 => après 2 périodes
				$cfgSubscribeDay = (int)Configuration::get('PAYLINE_SUBSCRIBE_DAY');
				$iWaitPeriod = $cfgStartDate-1; // on attend 0, 1 ou 2 périodes avant de débuter l'échéancier
				$iPeriodicity = (int)Configuration::get('PAYLINE_SUBSCRIBE_PERIODICITY'); // voir $aPeriodicityOptions
				
				switch ($iPeriodicity){
					case 10 : // Daily
						$sStartDate  = date('d/m/Y', strtotime(sprintf('now + %d day', $iWaitPeriod)));
						break;
					case 20 : // Weekly
						$sStartDate  = date('d/m/Y', strtotime(sprintf('now + %d week', $iWaitPeriod)));
						break;
					case 30 : // Bimonthly
						$sStartDate  = date('d/m/Y', strtotime(sprintf('now + %d week', $iWaitPeriod*2)));
						break;
					case 40 : // Monthly
					    if($cfgSubscribeDay == 0){ // PAYLINE_SUBSCRIBE_DAY=0 => les prélèvements mensuels ont lieu le même jour que celui de la commande initiale 
					        $sStartDate  = date('d/m/Y', strtotime(sprintf('now + %d month', $iWaitPeriod)));
					    }else{ // les prélèvements ont lieu le PAYLINE_SUBSCRIBE_DAY de chaque mois
					        $iWaitPeriod = date('j') <= $cfgSubscribeDay ? $iWaitPeriod : $iWaitPeriod+1; // si le jour de prélèvement est passé, on décale au mois suivant
					        $sStartDate = date('d/m/Y', strtotime(sprintf(date('Y').'/'.date('n').'/'.$cfgSubscribeDay.' + %d month', $iWaitPeriod))); // on calcule le décalage à partir de la date au format Y/n/j que strtotime comprend  
					    }
						break;
					case 50 : // Two quaterly
						$sStartDate  = date('d/m/Y', strtotime(sprintf('now + %d month', $iWaitPeriod*2)));
						break;
					case 60 : // Quaterly
						$sStartDate  = date('d/m/Y', strtotime(sprintf('now + %d month', $iWaitPeriod*3)));
						break;
					case 70 : // Semiannual
						$sStartDate  = date('d/m/Y', strtotime(sprintf('now + %d month', $iWaitPeriod*6)));
						break;
					case 80 : // Annual
						$sStartDate  = date('d/m/Y', strtotime(sprintf('now + %d year', $iWaitPeriod)));
						break;
					case 90 : // Biannual
						$sStartDate  = date('d/m/Y', strtotime(sprintf('now + %d year', $iWaitPeriod*2)));
						break;
				}
				$doWebPaymentRequest['recurring']['amount'] 		= $nUnitAmount;
				$doWebPaymentRequest['recurring']['firstAmount'] 	= $firstAmount;
				$doWebPaymentRequest['recurring']['billingCycle']	= Configuration::get('PAYLINE_SUBSCRIBE_PERIODICITY');
				$doWebPaymentRequest['recurring']['billingLeft']	= Configuration::get('PAYLINE_SUBSCRIBE_NUMBER') > 1 ? Configuration::get('PAYLINE_SUBSCRIBE_NUMBER') : null; // PAYLINE_SUBSCRIBE_NUMBER=1 => no limit => billingLeft is null
				$doWebPaymentRequest['recurring']['billingDay']		= Configuration::get('PAYLINE_SUBSCRIBE_DAY') ? Configuration::get('PAYLINE_SUBSCRIBE_DAY') : date('d');
				$doWebPaymentRequest['recurring']['startDate']		= $sStartDate;

				$this->log('paymentRequest', $doWebPaymentRequest['recurring']);


			break;
			
			case 'directdebit' :
				$payment_mode = Configuration::get('PAYLINE_DIRDEBIT_MODE');
				$payment_action = Configuration::get('PAYLINE_DIRDEBIT_ACTION');
				$bActivateWalletPayment = true;
			break;

		}

		if ($paylineVars['mode'] == 'recurring' || $paylineVars['mode'] == 'subscribe'){

			   if(Configuration::get('PAYLINE_SUBSCRIBE_NUMBER') >1){ // number of installment is known
		        $doWebPaymentRequest['payment']['amount'] 		= $doWebPaymentRequest['recurring']['firstAmount'] + ($doWebPaymentRequest['recurring']['billingLeft'] - 1) * $doWebPaymentRequest['recurring']['amount'];
		    }else{ // no limit => payment.amount is set to null
		        $doWebPaymentRequest['payment']['amount'] 		= null;
		    }

		} else {

			$doWebPaymentRequest['payment']['amount'] 		= $orderTotal;

		} // if

		$doWebPaymentRequest['payment']['currency'] 		= $currency->iso_code_num;
		$doWebPaymentRequest['payment']['contractNumber']	= $paylineVars['contractNumber'];
		$doWebPaymentRequest['payment']['mode']				= $payment_mode;
		$doWebPaymentRequest['payment']['action']			= $payment_action;

		// ORDER
		$doWebPaymentRequest['order']['ref'] 					= $newOrderId;
		$doWebPaymentRequest['order']['country'] 				= $pays->iso_code;
		$doWebPaymentRequest['order']['taxes'] 					= $taxes;
		$doWebPaymentRequest['order']['amount'] 				= $orderTotalWhitoutTaxes;
		$doWebPaymentRequest['order']['date'] 					= date('d/m/Y H:i');
		$doWebPaymentRequest['order']['currency'] 				= $doWebPaymentRequest['payment']['currency'];
		$doWebPaymentRequest['order']['deliveryMode']			= 4; // transporteur privé
		$doWebPaymentRequest['order']['deliveryTime']			= 1; // standard
		$doWebPaymentRequest['order']['deliveryExpectedDelay']	= 7; // 7 jours par défaut
		$doWebPaymentRequest['order']['deliveryExpectedDate']	= date('d/m/Y', strtotime('now + 7 day'));
		
		

		$this->addCartProducts($this->context->cart);

		// PRIVATE DATA
		$this->setPrivateData('idOrder', $newOrderId);
		$this->setPrivateData('idCart', $this->context->cart->id);
		$this->setPrivateData('idCust', $cust->id);

        // CONTRACTS
        if(isset($paylineVars['widget']) || $paylineVars['type'] == 'widget'){ // all primary payment means are displayed in widget
            $allContracts = $this->getPaylineContracts();
            $pContracts = array();
            foreach($allContracts as $contract){
                if($contract["primary"]){
                    $this->log($contract["contract"].' added in primary widget contract');
                    $pContracts[] = $contract["contract"];
                } // if
            } // foreach
            $doWebPaymentRequest['contracts'] = $pContracts;
        }else{ // payment mean is chose in store => only one choice on payment page
            $doWebPaymentRequest['contracts'] = array($paylineVars['contractNumber']);
        }
		$doWebPaymentRequest['secondContracts'] = $this->getSecondaryCardList($paylineVars['mode']);

		// BUYER
		$doWebPaymentRequest['buyer']['title'] 	    = ($cust->id_gender == 2) ? 'Mme' : 'M'; // 2=Mme, 1=M
		$doWebPaymentRequest['buyer']['lastName'] 	= $cust->lastname;
		$doWebPaymentRequest['buyer']['firstName'] 	= $cust->firstname;
		$doWebPaymentRequest['buyer']['email'] 		= $cust->email;
		$doWebPaymentRequest['buyer']['customerId']	= $cust->email;
		$doWebPaymentRequest['buyer']['walletId'] 	= $bActivateWalletPayment ? $this->getOrGenWalletId($cust->id) : '';

		// ADDRESSES
		$AddressBill = $this->getAddress($invoiceAddress);
		$doWebPaymentRequest['billingAddress']['name']		= $AddressBill['name'];
		$doWebPaymentRequest['billingAddress']['title']		= $doWebPaymentRequest['buyer']['title'];
		$doWebPaymentRequest['billingAddress']['firstName']	= $AddressBill['firstname'];
		$doWebPaymentRequest['billingAddress']['lastName']	= $AddressBill['lastname'];
		$doWebPaymentRequest['billingAddress']['street1']	= $AddressBill['street'];
		$doWebPaymentRequest['billingAddress']['street2']	= $AddressBill['street2'];
		$doWebPaymentRequest['billingAddress']['cityName']	= $AddressBill['cityName'];
		$doWebPaymentRequest['billingAddress']['zipCode']	= $AddressBill['zipCode'];
		$doWebPaymentRequest['billingAddress']['state']	    = $AddressBill['state'];
		$doWebPaymentRequest['billingAddress']['country']	= $pays->iso_code;
		$doWebPaymentRequest['billingAddress']['phone']		= $AddressBill['phone'];
		
		$AddressDelivery = $this->getAddress($deliveryAddress);
		$doWebPaymentRequest['shippingAddress']['name']		= $AddressDelivery['name'];
		$doWebPaymentRequest['shippingAddress']['title']	= $doWebPaymentRequest['buyer']['title'];
		$doWebPaymentRequest['shippingAddress']['firstName']= $AddressDelivery['firstname'];
		$doWebPaymentRequest['shippingAddress']['lastName']	= $AddressDelivery['lastname'];
		$doWebPaymentRequest['shippingAddress']['street1']	= $AddressDelivery['street'];
		$doWebPaymentRequest['shippingAddress']['street2']	= $AddressDelivery['street2'];
		$doWebPaymentRequest['shippingAddress']['cityName']	= $AddressDelivery['cityName'];
		$doWebPaymentRequest['shippingAddress']['zipCode']	= $AddressDelivery['zipCode'];
		$doWebPaymentRequest['shippingAddress']['state']	= $AddressDelivery['state'];
		$doWebPaymentRequest['shippingAddress']['country']	= $paysLivraison->iso_code;
		$doWebPaymentRequest['shippingAddress']['phone']	= $AddressDelivery['phone'];
		
		$doWebPaymentRequest['buyer']['mobilePhone']= !empty($AddressBill['mobilePhone']) ? $AddressBill['mobilePhone'] : $AddressDelivery['mobilePhone'];
    
		// URLs
		$this->setPaylineSDKUrls($paylineVars['mode']);

		return $doWebPaymentRequest;
	}
	
	public function redirectToPaymentPage($paylineVars=NULL){
	    $doWebPaymentRequest = $this->doWebPaymentRequest($paylineVars);
		
		foreach ($doWebPaymentRequest as $k=>$v){
			$this->log('request ' . $k, $v);
		}
		
		// Do not call doWebPayment if amount is 0
		if (isset($doWebPaymentRequest['order']['amount']) && $doWebPaymentRequest['order']['amount'] == 0) {
			$this->log('ERROR: Order amount = 0');
			die($this->l('ERROR:') . ' ' . $this->l('Order amount is equal to 0'));
		} else if (isset($doWebPaymentRequest['payment']['amount']) && $doWebPaymentRequest['payment']['amount'] == 0) {
			$this->log('ERROR: Payment amount = 0');
			die($this->l('ERROR:') . ' ' . $this->l('Payment amount is equal to 0'));
		}
		$result = $this->parseWsResult($this->getPaylineSDK()->doWebPayment($doWebPaymentRequest));

		if(isset($result) && $result['result']['code'] == '00000'){
			$this->saveToken((int)$this->context->cart->id, $result['token']);
			header("location:".$result['redirectURL']);
			exit();
		}
		elseif(isset($result)) {
			echo 'ERROR : '.$result['result']['code']. ' '.$result['result']['longMessage'].' <BR/>';
		}
	}

	/**
	 *
	 * @param integer $nOrderTotal amount*100
	 */
	protected function getSubscribeReductionAmount($oCart){

		$fResult = 0;


		if (Configuration::get('PAYLINE_SUBSCRIBE_AMOUNT_GIFT')){

			$fReductionRaw = Configuration::get('PAYLINE_SUBSCRIBE_AMOUNT_GIFT');

			switch(Configuration::get('PAYLINE_SUBSCRIBE_TYPE_GIFT')){

				case 'amount':

					$fOrderTotal = $oCart->getOrderTotal();

					$fResult = min($fOrderTotal,  $fReductionRaw);

				break;

				case 'percent':

					$fOrderTotal = $oCart->getOrderTotal() - $oCart->getOrderTotal(true, Cart::ONLY_SHIPPING);

					$fResult = min($fOrderTotal, ($fOrderTotal * $fReductionRaw)/100);

				break;

			} // switch

		} // if

		return $fResult;

	}

	protected function getSubscribtionAmounts($oCart, $nNumberOfInstallments){

		$fTotalWithDiscounts 				= $oCart->getOrderTotal(true);

		$fTotalWithDiscountsWithoutTaxes 	= $oCart->getOrderTotal(false);

		$fDiscounts 						= $oCart->getOrderTotal(true,  Cart::ONLY_DISCOUNTS);

		$fDiscountsWithoutTaxes 			= $oCart->getOrderTotal(false, Cart::ONLY_DISCOUNTS);

		$this->log('getSubscribtionAmounts', array($fTotalWithDiscounts, $fTotalWithDiscountsWithoutTaxes, $fDiscounts, $fDiscountsWithoutTaxes));

		$fTotalTaxesWithDiscounts 			= $fTotalWithDiscounts - $fTotalWithDiscountsWithoutTaxes;

		$fDiscountsTaxes 					= $fDiscounts - $fDiscountsWithoutTaxes;

		$fOrderAmount 						= $fTotalWithDiscounts + $fDiscounts;

		$fOrderTaxes   						= $fTotalTaxesWithDiscounts + $fDiscountsTaxes;

		$fOrderAmountWithoutTaxes 			= $fOrderAmount - $fOrderTaxes;

		$aResult = array();

		foreach (array($fTotalWithDiscounts, $fOrderAmount, $fOrderAmountWithoutTaxes, $fOrderTaxes) as $fValue){

			$aResult[] = round($fValue * 100);

		} // foreach

		$this->log('getSubscribtionAmounts', $aResult);

		return $aResult;

	}

	/**
	 * @refactor
	 */
	public function directPayment($paylineVars=NULL){
		$address 				= new Address($this->context->cart->id_address_invoice);
		$cust 					= new Customer(intval($this->context->cookie->id_customer));
		$pays 					= new Country($address->id_country);
		$DataAddressDelivery 	= new Address($this->context->cart->id_address_delivery);

		$currency 	 	 = new Currency(intval($this->context->cart->id_currency));

		$orderTotalWhitoutTaxes			= round($this->context->cart->getOrderTotal(false)*100);
		$orderTotal						= round($this->context->cart->getOrderTotal()*100);
		$taxes							= $orderTotal-$orderTotalWhitoutTaxes;

		$newOrderId = $this->l('Cart') . (int)$this->context->cart->id;

		//Log data
		$this->log('doAuthorization', array('Num cart'=>$this->context->cart->id, 'Num contract'=>$paylineVars['contractNumber'], 'Amount'=>$orderTotal));

		$directPaymentRequest = array();

		$cardAuthoriseWallet = explode(',',Configuration::get('PAYLINE_AUTORIZE_WALLET_CARD'));


		// PAYMENT
		$directPaymentRequest['payment']['amount'] 			= $orderTotal;

		$directPaymentRequest['payment']['currency'] 		= $currency->iso_code_num;
		$directPaymentRequest['payment']['contractNumber']	= $paylineVars['contractNumber'];
		$directPaymentRequest['payment']['mode']			= 'CPT';
		$directPaymentRequest['payment']['action']			= Configuration::get('PAYLINE_DIRECT_ACTION');

		// ORDER
		$directPaymentRequest['order']['ref'] 				= $newOrderId;
		$directPaymentRequest['order']['country'] 			= $pays->iso_code;
		$directPaymentRequest['order']['taxes'] 				= $taxes;
		$directPaymentRequest['order']['amount'] 			= $orderTotalWhitoutTaxes;
		$directPaymentRequest['order']['date'] 				= date('d/m/Y H:i');
		$directPaymentRequest['order']['currency'] 			= $directPaymentRequest['payment']['currency'];

		$this->addCartProducts($this->context->cart);

		// PRIVATE DATA
		$this->setPrivateData('idOrder', $newOrderId);
		$this->setPrivateData('idCart', $this->context->cart->id);
		$this->setPrivateData('idCust', $cust->id);

		$directPaymentRequest['contracts'] = array($paylineVars['contractNumber']);

		// CARD DATA
		$directPaymentRequest['card']['cardHolder'] = $paylineVars['holder'];
		$directPaymentRequest['card']['type'] 		= 'VISA';
		$directPaymentRequest['card']['number'] 	= $paylineVars['cardNumber'];
		if(strlen($paylineVars['monthExpire']) == 1)
			$month = '0'.$paylineVars['monthExpire'];
		else
			$month = $paylineVars['monthExpire'];

		$directPaymentRequest['card']['expirationDate'] 		= $month.substr($paylineVars['yearExpire'], -2);
		$directPaymentRequest['card']['cvx'] 		= $paylineVars['crypto'];

		// BUYER
		$directPaymentRequest['buyer']['lastName'] 		= $cust->lastname;
		$directPaymentRequest['buyer']['firstName'] 	= $cust->firstname;
		$directPaymentRequest['buyer']['email'] 		= $cust->email;

		$directPaymentRequest['buyer']['walletId']	= "";

		// ADDRESS
		$directPaymentRequest['address'] = $this->getAddress($DataAddressDelivery);


		$this->setPaylineSDKUrls(self::MODE_DIRECT);

		$result = $this->parseWsResult($this->getPaylineSDK()->doAuthorization($directPaymentRequest));

		$vars = array();
		if(isset($result) && $result['result']['code'] == '00000'){
			$message = $this->getL('Transaction Payline : ').$result['transaction']['id'];
			if(Configuration::get('PAYLINE_DIRECT_ACTION') == '100')
				$status = Configuration::get('PAYLINE_ID_STATE_AUTO_SIMPLE');
			else
				$status = _PS_OS_PAYMENT_;
			$vars['transaction_id'] = $result['transaction']['id'];
			$vars['contract_number'] = $paylineVars['contractNumber'];
			$vars['mode'] = 'CPT';
			$vars['action'] = Configuration::get('PAYLINE_DIRECT_ACTION');
			$vars['amount'] = $orderTotal;
			$vars['currency'] = $currency->iso_code_num;
			$vars['by'] = 'directPayment';
			$err=false;
		}else{
			$message = 'Direct payment error (code '.$result['result']['code'].') -> '.$result['result']['longMessage'];
			$status = _PS_OS_ERROR_;
			$err=true;
		}
		$this->validateOrder($this->context->cart->id , $status, $this->context->cart->getOrderTotal() , $this->displayName, $message.' (direct)',$vars,'','',$cust->secure_key);
		$id_order = (int)Order::getOrderByCartId($this->context->cart->id);
		$order = new Order( $id_order );
		$redirectLink = __PS_BASE_URI__.'order-confirmation.php?id_cart='.$order->id_cart
		.'&id_module='.$this->id
		.'&key='.$order->secure_key;
		if($err){
			$redirectLink .= '&error='.$err;
		}
		Tools::redirectLink($redirectLink);
		exit;
	}

	/**
	 * @refactor
	 */
	public function walletPayment($cardInd, $type){
		$address 				= new Address($this->context->cart->id_address_invoice);
		$cust 					= new Customer(intval($this->context->cookie->id_customer));
		$pays 					= new Country($address->id_country,$this->context->cookie->id_lang);
		$DataAddressDelivery 	= new Address($this->context->cart->id_address_delivery);

		$currency 	 	 = new Currency(intval($this->context->cart->id_currency));

		$orderTotalWhitoutTaxes			= round($this->context->cart->getOrderTotal(false)*100);
		$orderTotal						= round($this->context->cart->getOrderTotal()*100);
		$taxes							= $orderTotal-$orderTotalWhitoutTaxes;

		$doImmediateWalletPaymentRequest = array();

		$contractNumber = $this->getPaylineContractByCard($type);
		//Log data
		$this->log('doImmediateWalletPayment', array('Num cart'=>$this->context->cart->id, 'Num contract'=>$contractNumber['contract'], 'Amount'=>$orderTotal, 'Wallet ID'=>$this->getOrGenWalletId($cust->id), 'CardInd'=>$cardInd));

		// PAYMENT
		$doImmediateWalletPaymentRequest['payment']['amount'] 			= $orderTotal;
		$doImmediateWalletPaymentRequest['payment']['currency'] 		= $currency->iso_code_num;
		$doImmediateWalletPaymentRequest['payment']['contractNumber']	= $contractNumber['contract'];
		$doImmediateWalletPaymentRequest['payment']['mode']				= Configuration::get('PAYLINE_WEB_CASH_MODE');
		$doImmediateWalletPaymentRequest['payment']['action']			= Configuration::get('PAYLINE_WEB_CASH_ACTION');

		// ORDER
		$doImmediateWalletPaymentRequest['order']['ref'] 				= $this->l('Cart') . (int)$this->context->cart->id;
		$doImmediateWalletPaymentRequest['order']['country'] 			= $pays->iso_code;
		$doImmediateWalletPaymentRequest['order']['taxes'] 				= $taxes;
		$doImmediateWalletPaymentRequest['order']['amount'] 			= $orderTotalWhitoutTaxes;
		$doImmediateWalletPaymentRequest['order']['date'] 				= date('d/m/Y H:i');
		$doImmediateWalletPaymentRequest['order']['currency'] 			= $doImmediateWalletPaymentRequest['payment']['currency'];

		$this->addCartProducts($this->context->cart);

		// PRIVATE DATA
		// $this->setPrivateData('idOrder', $newOrderId);
		$this->setPrivateData('idCart', $this->context->cart->id);
		$this->setPrivateData('idCust', $cust->id);

		// WALLET ID
		$doImmediateWalletPaymentRequest['walletId'] = $this->getOrGenWalletId($cust->id);

		// CARDIND
		$doImmediateWalletPaymentRequest['cardInd'] =  $cardInd;

		$result = $this->parseWsResult($this->getPaylineSDK()->doImmediateWalletPayment($doImmediateWalletPaymentRequest));

		$vars = array();
		if(isset($result) && $result['result']['code'] == '00000'){
			$message = $this->getL('Transaction Payline : ').$result['transaction']['id'];
			if(Configuration::get('PAYLINE_WALLET_ACTION') == '100')
				$status = Configuration::get('PAYLINE_ID_STATE_AUTO_SIMPLE');
			else
				$status = _PS_OS_PAYMENT_;
			$vars['transaction_id'] = $result['transaction']['id'];
			$vars['contract_number'] = $contractNumber['contract'];
			$vars['mode'] = Configuration::get('PAYLINE_WEB_CASH_MODE');
			$vars['action'] = Configuration::get('PAYLINE_WALLET_ACTION');
			$vars['amount'] = $orderTotal;
			$vars['currency'] = $currency->iso_code_num;
			$vars['by'] = 'walletPayment';
			$err=false;
		}else{
			$message = 'Wallet payment error (code '.$result['result']['code'].') -> '.$result['result']['longMessage'];
			$status = _PS_OS_ERROR_;
			$err=true;
		}
		$this->validateOrder($this->context->cart->id , $status, $this->context->cart->getOrderTotal() , $this->displayName, $message.' (wallet)',$vars,'','',$cust->secure_key);
		$id_order = (int)Order::getOrderByCartId($this->context->cart->id);
		$order = new Order( $id_order );
		$redirectLink = __PS_BASE_URI__.'order-confirmation.php?id_cart='.$order->id_cart
		.'&id_module='.$this->id
		.'&key='.$order->secure_key;
		if($err){
			$redirectLink .= '&error='.$err;
		}
		Tools::redirectLink($redirectLink);
		exit;
	}

	# ORDER VALIDATION

	/**
	 * Validates order
	 * @see PaymentModule::validateOrder
	 */
	public function validateOrder($id_cart, $id_order_state, $amountPaid, $paymentMethod = 'Unknown', $message = NULL, $extraVars = array(), $currency_special = NULL, $dont_touch_amount = false, $secure_key = false, Shop $shop = null){

        if (!$this->active){

        	return false ;

        } // if

        $this->log('VALIDATING', array('id_cart'=>$id_cart, 'id_order_state'=>$id_order_state, 'amountPaid'=>$amountPaid));
        $this->log('VALIDATING ARGS', func_get_args());

        // Check if country is defined into context
        if (empty($this->context->country) || !Validate::isLoadedObject($this->context->country) || !$this->context->country->active) {
            // Current context country is not active or is undefined
            // Set an alternative country, from the cart address invoice
            if (!empty($this->context->cart->id_address_invoice)) {
                $address = new Address($this->context->cart->id_address_invoice);
                $this->context->country = new Country($address->id_country, $this->context->cart->id_lang);
                if (!$this->context->country->active && !empty($this->context->cart->id_address_delivery)) {
                    // Try from delivery address
                    $address = new Address($this->context->cart->id_address_delivery);
                    $this->context->country = new Country($address->id_country, $this->context->cart->id_lang);
                }
            }
        }

        parent::validateOrder($id_cart, $id_order_state, $amountPaid, $paymentMethod, $message, $extraVars, $currency_special, $dont_touch_amount, $secure_key, $shop);

    	if (array_key_exists('transaction_id', $extraVars)){

    		$this->saveTransaction($id_cart, $extraVars['transaction_id'], $extraVars['contract_number'], $extraVars['action'],$extraVars['mode'],$extraVars['amount'],$extraVars['currency'], $extraVars['by']);

    	} // if

	} // validateOrder

    /**
     * Saves transaction to payline order
     */
	protected function saveTransaction($nCartId, $sTransactionId, $sContractNumber, $nActionCode,
			 $sMode, $nAmount, $nCurrency, $sPaymentBy){

		$nOrderId = (int)Order::getOrderByCartId($nCartId);

		if ($nOrderId && $sMode != 'NX'){

			$sPaymentStatus = ($nActionCode == 100 ? 'authorization' : 'capture');

			$sQuery = 'REPLACE INTO `'._DB_PREFIX_.'payline_order`
						(`id_order`, `id_transaction`, `contract_number`,
						 `payment_status`, `mode`, `amount`, `currency`,
						 `payment_by`)
						VALUES (
						 '. intval($nOrderId) . ', "'.pSql($sTransactionId) .'",
						 "'.pSql($sContractNumber).'", "'.pSql($sPaymentStatus).'",
						 "'. pSql($sMode).'", '.intval($nAmount).',
						 '.pSql($nCurrency).', "'.pSql($sPaymentBy).'")';

			Db::getInstance()->Execute($sQuery);

		} // if

	} // saveTransaction

    /**
     * Saves token <=> id_cart association
     */
	protected function saveToken($nCartId, $sToken) {
		$sQuery = 'REPLACE INTO `'._DB_PREFIX_.'payline_token`
					(`id_cart`, `token`)
					VALUES ('. (int)$nCartId . ', "'.pSql($sToken) .'")';

		Db::getInstance()->Execute($sQuery);
	} // saveToken
	
	/**
     * Get id_cart for a token
	 * @return int
     */
	public function getIdCartByToken($sToken) {
		$sQuery = 'SELECT `id_cart` FROM `'._DB_PREFIX_.'payline_token` WHERE `token`="'.pSql($sToken) .'"';
		return (int)Db::getInstance()->getValue($sQuery);
	} // getIdCartByToken


	# BACKOFFICE DISPLAY

	/**
	 * Returns string with smarty template parsed
	 * @param string $sPath Relative path to template dir
	 * @param string $sName Template name (without extension)
	 * @return mixed Parsed template content or some false-ish value symbolising error
	 */
	public function fetchTemplate($sPath, $sName){

		$sTemplatePath = ltrim($sPath . $sName .'.tpl', DIRECTORY_SEPARATOR);

		return $this->display(__FILE__, $sTemplatePath);

	} // fetchTemplate

	/**
	 * Returns array of point of sell informations if ID, Access Key & POS is correct, else return false
	 * @return array|boolean
	 */
	private static $_getCurrentMerchantPointOfSellCache = null;
	private function _getCurrentMerchantPointOfSell() {
		if (self::$_getCurrentMerchantPointOfSellCache == null) {
			$pos = Configuration::get('PAYLINE_POS');
			$getMerchantSettingsResult = $this->_getMerchantSettings();
			if ($getMerchantSettingsResult['result']['code'] == '00000') {
				foreach ($getMerchantSettingsResult['POS'] as $pointOfSell)
					if ($pointOfSell['label'] == $pos) {
						self::$_getCurrentMerchantPointOfSellCache = $pointOfSell;
						return self::$_getCurrentMerchantPointOfSellCache;
					}
				// If there is only one POS, we auto-select it
				if (empty($pos) && count($getMerchantSettingsResult['POS']) == 1) {
					self::$_getCurrentMerchantPointOfSellCache = $pointOfSell;
					Configuration::updateValue('PAYLINE_POS', $pointOfSell['label']);
					return self::$_getCurrentMerchantPointOfSellCache;
				// More than one POS, we auto-select the first
				} else if (empty($pos) && count($getMerchantSettingsResult['POS']) > 1) {
					$pointOfSell = current($getMerchantSettingsResult['POS']);
					Configuration::updateValue('PAYLINE_POS', $pointOfSell['label']);
					return self::$_getCurrentMerchantPointOfSellCache;
				}
			}
		} else {
			return self::$_getCurrentMerchantPointOfSellCache;
		}
		self::$_getCurrentMerchantPointOfSellCache = false;
		return self::$_getCurrentMerchantPointOfSellCache;
	}
	
	/**
	 * Returns array of point of sell informations if ID, Access Key & POS is correct, else return false
	 * @return array|boolean
	 */
	private static $_getAllPointOfSellCache = array();
	private function _getAllPointOfSell() {
		if (!count(self::$_getAllPointOfSellCache)) {
			$getMerchantSettingsResult = $this->_getMerchantSettings();
			if ($getMerchantSettingsResult['result']['code'] == '00000')
				foreach ($getMerchantSettingsResult['POS'] as $pointOfSell)
					self::$_getAllPointOfSellCache[$pointOfSell['label']] = $pointOfSell['label'];
			return self::$_getAllPointOfSellCache;
		} else {
			return self::$_getAllPointOfSellCache;
		}
	}
	
	/**
	 * Returns getMerchantSettings response, and put it in cache
	 * @return array|boolean
	 */
	private static $_getMerchantSettingsCache = null;
	private function _getMerchantSettings($params = array()) {
		if (self::$_getMerchantSettingsCache == null)
			self::$_getMerchantSettingsCache = $this->getPaylineSDK()->getMerchantSettingsToArray($params);
		return self::$_getMerchantSettingsCache;
	}

	/**
	 * @refactor
	 * @return string
	 */
	public function getContent() {
		if (Tools::getValue('getItem')) {
			$this->_html = '';
			$item = Tools::getValue('itemType');
			$query = Tools::getValue('q', false);
			if (!$query || strlen($query) < 1) {
				if (ob_get_length() > 0) ob_clean();
				die();
			}
			$limit = Tools::getValue('limit', 100);
			$start = Tools::getValue('start', 0);
			switch ($item) {
				case 'product' :
					$items = $this->getProductsOnLive($query, $limit, $start);
					$item_id_column = 'id_product';
					$item_name_column = 'name';
					break;
			}
			if ($items)
				foreach ($items as $row )
					$this->_html .= $row [$item_id_column] . '=' . $row [$item_name_column] . "\n";
			die($this->_html);
		}

		// Be sure that the module is hooked on new hooks
		if (!$this->isRegisteredInHook('actionObjectOrderSlipAddAfter'))
			$this->registerHook('actionObjectOrderSlipAddAfter');
		
		$this->context->controller->addJqueryUI('ui.sortable');
		$this->context->controller->addJS($this->_path . 'js/json2.min.js');
		$this->context->controller->addJS($this->_path . 'js/toggles.min.js');
		$this->context->controller->addCSS($this->_path . 'css/toggles-light.css', 'all');
		$this->context->controller->addCSS($this->_path . 'css/admin.css', 'all');
		$this->context->controller->addJS($this->_path . 'js/module.js');

		$sHtml = '';

		if (Tools::isSubmit('submitPayline')){

			$sHtml = $this->postProcess();

		} // if

		$this->_adminForm();

		return '<div class="pm_bo_ps_'.Tools::substr(str_replace('.', '', _PS_VERSION_), 0, 2).'">' . $sHtml . (string)$this->fetchTemplate('/views/templates/admin/', 'back_office') . '</div>';

	} // getContent

	/**
	 * @refactor
	 */
	public function _adminForm()
	{

		if(!extension_loaded('curl')) $this->_postErrors[] = $this->l('php-curl extension is not loaded');
		if(!extension_loaded('soap')) $this->_postErrors[] = $this->l('php-soap extension is not loaded');
		if(!extension_loaded('openssl')) $this->_postErrors[] = $this->l('php-openssl extension is not loaded');

		$pointOfSell = $this->_getCurrentMerchantPointOfSell();
		if ($pointOfSell === false)
			$this->context->smarty->assign(array('api_error' => $this->l('Unable to get your merchant settings. Please check Merchant ID and Access Key below and make sure they are correct.')));

		if(isset($this->_postErrors) AND sizeof($this->_postErrors))
			$this->context->smarty->assign(array('errors' => $this->_postErrors));

		$this->context->smarty->assign(array(
				'module_version'	=> $this->version,
				'default_id_lang'	=> (int)Configuration::get('PS_LANG_DEFAULT'),
				'path' 				=> $this->_path,
				'ps_version' 		=> _PS_VERSION_,
				'js_dir' 			=> $this->context->shop->getBaseURL() . '/js/',
				'css_dir'			=> $this->context->shop->getBaseURL() . '/css/',
				'tab'				=> (($tab = (int)Tools::getValue('tabs')) ? $tab : '0'),
				'request_uri'		=> htmlentities($_SERVER['REQUEST_URI']),
				'html_access' 		=> $this->getPaylineAccessTabHtml(),
				'html_proxy' 		=> $this->getPaylineProxyTabHtml(),
				'html_payment' 		=> $this->getPaylinePaymentPageTabHtml(),
				'paylineContracts'	=> $this->getAllContractsForCurrentContext(),
		));
	}

	/**
	 * @refactor
	 * @param unknown_type $key
	 * @return Ambigous <NULL>
	 */
	public function getL($key)
	{
		$translations = array(
				'Transaction Payline : ' => $this->l('Transaction Payline : '),
				'Do not honor' => $this->l('Do not honor'),
				'Card expired' => $this->l('Card expired'),
				'Contact  your bank for authorization' => $this->l('Contact your bank for authorization'),
				'Contact your bank for special condition' => $this->l('Contact your bank for special condition'),
				'Invalid card number' => $this->l('Invalid card number'),
				'Expenses not accepted' => $this->l('Expenses not accepted'),
				'Invalid PIN code' => $this->l('Invalid PIN code'),
				'Card not registered' => $this->l('Card not registered'),
				'This transaction is not authorized' => $this->l('This transaction is not authorized'),
				'Transaction refused by terminal' => $this->l('Transaction refused by terminal'),
				'Debit limit exceeded' => $this->l('Debit limit exceeded'),
				'Do not honor' => $this->l('Do not honor'),
				'Card expired' => $this->l('Card expired'),
				'Maximum number of attempts reached' => $this->l('Maximum number of attempts reached'),
				'Card lost' => $this->l('Card lost'),
				'Card stolen' => $this->l('Card stolen'),
				'Transaction is refused' => $this->l('Transaction is refused'),
				'Transaction is invalid' => $this->l('Transaction is invalid'),
				'Transaction is approved' => $this->l('Transaction is approved'),
				'Result:' => $this->l('Result:'),
				':' => $this->l(':'),
				'Your next bank levies' => $this->l('Your next bank levies'),
				'Recurring payment is approved' => $this->l('Recurring payment is approved'),
				'[Merchant]' =>$this->l('[Merchant]'),
				'[Buyer]' =>$this->l('[Buyer]'),
				'[Your schedule]' =>$this->l('[Your schedule]'),
				'Differed payment accepted' => $this->l('Differed payment accepted'),
				'Payment cancelled by the buyer' => $this->l('Payment cancelled by the buyer'),
				'ERROR:you can\'t delete this card.' => $this->l('ERROR:you can\'t delete this card.'),
				'ERROR:you can\'t delete this wallet.' => $this->l('ERROR:you can\'t delete this wallet.'),
				'Operation successfully.' => $this->l('Operation successfully.'),
				'Unsubscribing message'	=> $this->l('Unsubscribing message'),
				'ERROR:you can\'t update this subscription.' => $this->l('ERROR:you can\'t update this subscription.'),
				'Invalid currency' => $this->l('Invalid currency'),
		);
		return $translations[$key];
	}

	/**
	 * @refactor
	 * @return string
	 */
	protected function getPaylineAccessTabHtml()
	{

		$html = $this->_adminFormTextinput('PAYLINE_MERCHANT_ID', $this->l('Merchant id'), $this->l('Merchant id provided by the payment gateway'), 'size="40"');
		$html .= $this->_adminFormTextinput('PAYLINE_ACCESS_KEY', $this->l('Access key'), $this->l('Access key provided by the gateway'), 'size="40"');
		
		if ((strlen(Configuration::get('PAYLINE_MERCHANT_ID')) && strlen(Configuration::get('PAYLINE_ACCESS_KEY'))) && sizeof($this->_getAllPointOfSell())) {
			$html .= $this->getHtmlSelect($this->_getAllPointOfSell(), 'PAYLINE_POS', 0, $this->l('Point of sell'), '', 0, '', true);
			$html .= $this->getHtmlSelect($this->getContractsTypesForSelect(), 'PAYLINE_CONTRACT_NUMBER', 0, $this->l('Main contract'), '', 0, '', true);
		}

		$html .= $this->getHtmlSwitchOnOff('PAYLINE_PRODUCTION', 0, $this->l('Production mode'));

		$html .= '<input type="hidden" name="PAYLINE_AUTORIZE_WALLET_CARD" value="CB,VISA,MASTERCARD,AMEX">';
		return $html;
	}

	/**
	 * @refactor
	 * @return string
	 */
	protected function getPaylineProxyTabHtml()
	{
		$html = $this->_adminFormTextinput('PAYLINE_PROXY_HOST', $this->l('Host'), '', 'size="30"');
		$html .= $this->_adminFormTextinput('PAYLINE_PROXY_PORT', $this->l('Port'), '', 'size="5" maxlength="5"');
		$html .= $this->_adminFormTextinput('PAYLINE_PROXY_LOGIN', $this->l('Login'), '', 'size="30"');
		$html .= $this->_adminFormTextinput('PAYLINE_PROXY_PASSWORD', $this->l('Password'), '', 'size="30"');

		return $html;
	}

	private function getProductsOnLive($q, $limit, $start) {
		$result = Db::getInstance()->ExecuteS('
		SELECT p.`id_product`, CONCAT(p.`id_product`, \' - \', IFNULL(CONCAT(NULLIF(TRIM(p.reference), \'\'), \' - \'), \'\'), pl.`name`) AS name
		FROM `' . _DB_PREFIX_ . 'product` p, `' . _DB_PREFIX_ . 'product_lang` pl, `' . _DB_PREFIX_ . 'product_shop` ps
		WHERE p.`id_product`=pl.`id_product`
		AND p.`id_product`=ps.`id_product`
		' . Shop::addSqlRestriction(false, 'ps') . '
		AND pl.`id_lang`=' . (int)$this->context->cookie->id_lang . '
		AND ps.`active` = 1
		AND ((p.`id_product` LIKE \'%' . pSQL($q) . '%\') OR (pl.`name` LIKE \'%' . pSQL($q) . '%\') OR (p.`reference` LIKE \'%' . pSQL($q) . '%\') OR (pl.`description` LIKE \'%' . pSQL($q) . '%\') OR (pl.`description_short` LIKE \'%' . pSQL($q) . '%\'))
		GROUP BY p.`id_product`
		ORDER BY pl.`name` ASC ' . ($limit ? 'LIMIT ' . $start . ', ' . (int) $limit : ''));
		return $result;
	}

	private function getProductsAssociation($productList) {
		return Db::getInstance()->ExecuteS('
			SELECT pl.`name`, pl.`id_product`
			FROM `'._DB_PREFIX_.'product_lang` pl
			WHERE pl.`id_product` IN ('. implode(',', $productList) .') AND pl.`id_lang` = '.(int)$this->context->cookie->id_lang.'
			ORDER BY pl.`name` ASC');
	}

  private function getProductList($sConfigVarName, $onlyId = false) {
        $productList = array();
        $configValue = Configuration::get($sConfigVarName);
        if (!empty($configValue)) {
            $productList = explode(',', $configValue);
            if ($onlyId) {
                return $productList;
            } else {
                $productList = $this->getProductsAssociation($productList);
            }
        }
        return $productList;
    }


	protected function getHtmlInputProducts($sConfigVarName, $sTitle = '') {
		$this->context->controller->addJS($this->_path . 'js/multiselect/jquery.tmpl.1.1.1.js');
		$this->context->controller->addJS($this->_path . 'js/multiselect/jquery.blockUI.js');
		$this->context->controller->addJS($this->_path . 'js/multiselect/ui.multiselect.js');
		$this->context->controller->addCSS($this->_path . 'js/multiselect/ui.multiselect.css');

		$productList = $this->getProductList($sConfigVarName);

		$selectItem = '<select id="multiselect' . Tools::strtolower($sConfigVarName) . '" class="multiselect" multiple="multiple" name="' . $sConfigVarName . '[]">';
		if (is_array($productList) && sizeof($productList)) {
			foreach ($productList as $value => $option) {
				$selectItem .= '<option value="' . $option['id_product'] . '" selected="selected">' . htmlentities($option['name'], ENT_COMPAT, 'UTF-8') . '</option>';
			}
		}
		$selectItem .= '</select>';

		$html = '
		<div class="cfg-'.Tools::strtolower($sConfigVarName).'-container multiselect-container">
			<label style="text-align:left;">'.$sTitle.'</label>
			<div class="margin-form cfg-'.Tools::strtolower($sConfigVarName).'">
				'. $selectItem .'
			</div>
		</div>';

		$html .= '
		<script type="text/javascript">
		$(document).ready(function() {			
			$("#multiselect' . Tools::strtolower($sConfigVarName) . '").multiselect({
				locale: {
						addAll:\''.addcslashes($this->l('Add all'), "'").'\',
						removeAll:\''.addcslashes($this->l('Remove all'), "'").'\',
						itemsCount:\''.addcslashes($this->l('#{count} items selected'), "'").'\',
						itemsTotal:\''.addcslashes($this->l('#{count} items total'), "'").'\',
						busy:\''.addcslashes($this->l('Please wait...'), "'").'\',
						errorDataFormat:\''.addcslashes($this->l('Cannot add options, unknown data format'), "'").'\',
						errorInsertNode:"'.addcslashes($this->l('There was a problem trying to add the item').':\n\n\t[#{key}] => #{value}\n\n'.addcslashes($this->l('The operation was aborted.'), '"'), "'").'",
						errorReadonly:\''.addcslashes($this->l('The option #{option} is readonly'), "'").'\',
						errorRequest:\''.addcslashes($this->l('Sorry! There seemed to be a problem with the remote call. (Type: #{status})'), "'").'\',
						sInputSearch:\''.addcslashes($this->l('Please enter the first letters of the search item'), "'").'\',
						sInputShowMore:\''.addcslashes($this->l('Show more'), "'").'\'
					},
				remoteUrl: "' . $_SERVER['SCRIPT_NAME'] . '?controller='. Tools::getValue('controller') . '&configure=' . $this->name . '&token=' . Tools::getValue('token') . '&getItem=1&itemType=product' . '",
				remoteLimit: 500,
				remoteStart: 0,
				remoteLimitIncrement: 500,
				triggerOnLiClick: true,
				displayMore: true
			});
		});
		</script>';
		return $html;
	}
	
	protected function getHtmlSwitchOnOff($sConfigVarName, $sDefaultOption, $sTitle = '', $sDescription = '') {
		if (!empty($sTitle))
			$html = '
			<div class="cfg-'.Tools::strtolower($sConfigVarName).'-container">
				<label style="text-align:left;">'.$sTitle.'</label>
				<div class="margin-form cfg-'.Tools::strtolower($sConfigVarName).'">
					<div class="payline-toggle toggle-light toggle-'.str_replace('_', '-', Tools::strtolower($sConfigVarName)).'"></div>
				</div>
				' . (!empty($sDescription) ? $sDescription : '') . '
				<input type="hidden" class="'.$sConfigVarName.'" name="'.$sConfigVarName.'" value="'.(Configuration::get($sConfigVarName) !== false ? (int)Configuration::get($sConfigVarName) : (int)$sDefaultOption).'" />
			</div>';
		else
			$html = '
				<div class="payline-toggle toggle-light toggle-'.str_replace('_', '-', Tools::strtolower($sConfigVarName)).'"></div>
				<input type="hidden" class="'.$sConfigVarName.'" name="'.$sConfigVarName.'" value="'.(Configuration::get($sConfigVarName) !== false ? (int)Configuration::get($sConfigVarName) : (int)$sDefaultOption).'" />
			';
			
		$html .= '
		<script type="text/javascript">
			$(document).ready(function() {
				$(\'.toggle-'.str_replace('_', '-', Tools::strtolower($sConfigVarName)).'\').toggles({checkbox:$(\'input.'.$sConfigVarName.'\')});
			});
		</script>
		';
		return $html;
	}

	/**
	 * @refactor
	 * @return string
	 */
	protected function getHtmlSelect($aOptions, $sConfigVarName, $sDefaultOption, $sTitle, $sDescription, $nWidth = 0, $sField = '', $bSort = false){

		if ($bSort)
			asort($aOptions);

		$sSelected = Configuration::get($sConfigVarName) && array_key_exists(Configuration::get($sConfigVarName), $aOptions) ? Configuration::get($sConfigVarName) : $sDefaultOption;

		return $this->_adminFormSelect($aOptions, $sSelected, $sConfigVarName, $this->l($sTitle), $this->l($sDescription), $nWidth, $sField);

	}

	protected function getMultilangFieldHtml($sTitle, $sName){

		$aResult = array();
		$aLanguages = Language::getLanguages(false);
		$nDefaultLanguageId = (int)(Configuration::get('PS_LANG_DEFAULT'));
		$sDivLangName = 'recurringTitleÂ¤recurringSubtitleÂ¤directTitleÂ¤directSubtitleÂ¤walletTitleÂ¤walletSubtitleÂ¤subscribeTitleÂ¤subscribeSubtitle';

		$aDivIdElements = explode('_', strtolower($sName));
		$sDivId = $aDivIdElements[1]. ucfirst($aDivIdElements[2]);

		$sDefaultValue = (string)ConfigurationCore::get($sName, $nDefaultLanguageId) ;

		$aResult[] = '<label style="text-align:left;">'.$sTitle.'</label><div class="margin-form">';

		foreach ($aLanguages as $aLanguage){

			$nLanguageId = $aLanguage['id_lang'];

			$sValue =  Configuration::get($sName, $nLanguageId);  ;
			$aResult[] = '<div class="translatable-field lang-'.$nLanguageId.'" id="'.$sDivId.'_'.$nLanguageId.'" style="'.($nLanguageId  != $nDefaultLanguageId && count($aLanguages) > 1 ? 'display:none' : '').'; float: left;">
						<input type="text" name="'.$sName.'_'.$nLanguageId.'" id="'.$sName.'_'.$nLanguageId.'" size="64" value="'.($sValue ? $sValue : $sDefaultValue).'" /></div>';
		}

		$aResult[] = $this->displayFlags($aLanguages, $nDefaultLanguageId, $sDivLangName, $sDivId, true);
		$aResult[] = '<div class="clear"></div><p>'.$this->l('Limited to 255 characters').'</p>';
		$aResult[] = '</div>';

		return implode(PHP_EOL, $aResult);

	}

	/**
	 * @refactor
	 * @return string
	 */
	protected function getPaylinePaymentPageTabHtml()
	{
		$states = OrderState::getOrderStates((int)($this->context->cookie->id_lang));

		$defaultLanguage = (int)(Configuration::get('PS_LANG_DEFAULT'));
		$languages = Language::getLanguages(false);
		$iso = Language::getIsoById((int)($this->context->cookie->id_lang));
		$divLangName = 'recurringTitleÂ¤recurringSubtitleÂ¤directTitleÂ¤walletTitleÂ¤subscribeTitleÂ¤subscribeSubtitle';

		$html = '<div class="payline-payment-container-left">';

		$aZeroOneOptions = array(
				'1'=>$this->l('TRUE '),
				'0'=>$this->l('FALSE ')
		);

		$aAuthorisationOptions = array(
				'100'=>$this->l('différé'),
				'101'=>$this->l('à la commande')
		);

		$aPeriodicityOptions = array(
				'10'=>$this->l('Daily '),
				'20'=>$this->l('Weekly '),
				'30'=>$this->l('Bimonthly '),
				'40'=>$this->l('Monthly '),
				'50'=>$this->l('Two quaterly '),
				'60'=>$this->l('Quaterly '),
				'70'=>$this->l('Semiannual '),
				'80'=>$this->l('Annual '),
				'90'=>$this->l('Biannual ')

		);

		$aStatesOptions = array('-1'=>'Manual capture');

		foreach ($states AS $state){

			$aStatesOptions[$state['id_order_state']] = stripslashes($state['name']);

		}

		$options = array(
				''		=> $this->l('- based on customer browser -'),
				'eng'	=> $this->l('English'),
				'spa'	=> $this->l('Spanish'),
				'fra'	=> $this->l('French'),
				'ita'	=> $this->l('Italian')
		);

		// PAYMENT WEB CASH
		$html .= '<div class="payline-payment-classic payline-payment-cfg-container">';
		$html .= '<h3>'.$this->l('Cash web payment').'</h3>';
		$html .= '<p class="payline-payment-baseline">'.$this->l('Il s\'agit de la méthode de paiement traditionnelle. Votre client est redirigé vers notre plateforme de paiement Payline, afin de collecter ses informations de paiement en toute sécurité. Vous recevrez en retour une validation ou une annulation de commande dans votre back-office.').'</p>';
		$html .= $this->getHtmlSwitchOnOff('PAYLINE_WEB_CASH_ENABLE', 0, '');
		
		$html .= '<p class="center"><input type="button" class="button payline-cfg-button" value="'.$this->l('Configure').'" /></p>';
		$html .= '<div class="payline-payment-cfg-fields">';

		$html .= $this->getHtmlSelect($aAuthorisationOptions, 'PAYLINE_WEB_CASH_ACTION', '100', $this->l('Mode de débit'), '', 0,'cashWebCapture');

		$html .= '<div id="cashWebCapture" '.(Configuration::get('PAYLINE_WEB_CASH_ACTION') == 101 ? 'style="display:none"' : false).'>';

		$html .= $this->getHtmlSelect($aStatesOptions, 'PAYLINE_WEB_CASH_VALIDATION', '0', $this->l('Statut de validation de commande'), '', 0,'cashWebCapture', true);

		$html .= '</div>';
		
		$html .= $this->getHtmlSwitchOnOff('PAYLINE_WEB_CASH_BY_WALLET', 0, $this->l('Payment by wallet'));
		
		if(Configuration::get('PS_ORDER_PROCESS_TYPE') == 0){ // standard process - 5 steps
		    $aWebUxOptions = array('REDIRECT'=>$this->l('redirect to payment page'),Payline::WIDGET_COLUMN=>$this->l('in-site (column)'),Payline::WIDGET_TAB=>$this->l('in-site (tab)'));
		    $html .= $this->getHtmlSelect($aWebUxOptions, 'PAYLINE_WEB_CASH_UX', '100', $this->l('User experience'), '', 0,'widgetLayout');
		}else{
		    $html .= '<input type="hidden" name="PAYLINE_WEB_CASH_UX" value="REDIRECT">'; // if One Page Checkout is activated, redirection is the only supported user experience
		}
		
		$html .= '<input type="hidden" name="PAYLINE_WEB_CASH_MODE" value="CPT">';
		$html .= $this->_adminFormTextinput('PAYLINE_WEB_CASH_CUSTOM_CODE', $this->l('Custom payment page code'), $this->l('Example : ')."1fd51s2dfs51", 'size="65"');
		$html .= $this->_adminFormTextinput('PAYLINE_WEB_CASH_TPL_URL', $this->l('Custom payment template URL'), $this->l('https ://.... Only.'), 'size="65"');


		$html .= '</div>';
		$html .= '</div><!-- .payline-payment-classic -->';
		// END PAYMENT WEB CASH

		// PAYMENT DIRECT
		$html .= '<div class="payline-payment-direct payline-payment-cfg-container">';
		$html .= '<h3>'.$this->l('Direct payment').'</h3>';
		$html .= '<p class="payline-payment-baseline">'.$this->l('Attention, la sécurisation du transfert des données de paiement entre votre boutique et les serveurs Payline doit être assurée par vous même.').'</p>';
		
		if(Configuration::get('PS_SHOP_DOMAIN_SSL')) {
			$html .= $this->getHtmlSwitchOnOff('PAYLINE_DIRECT_ENABLE', 0, '');
			$html .= '<p class="center"><input type="button" class="button payline-cfg-button" value="'.$this->l('Configure').'" /></p>';
		} else {
			$html .='<div class="error">'.$this->l('To enable this option you must have an SSL certificate');
			$html .= '<br/>'.$this->l('When you have your SSL certificate to thank you fill in the field domain name in the SSL tab preferences > SEO & URLs.');
			$html .='</div>';
			$html .= '<input type="hidden" name="PAYLINE_DIRECT_ENABLE" value="0">';
		}
		$html .= '<div class="payline-payment-cfg-fields">';

		$html .= $this->getMultilangFieldHtml($this->l('Title'), 'PAYLINE_DIRECT_TITLE');
		$html .= $this->getMultilangFieldHtml($this->l('Subtitle'), 'PAYLINE_DIRECT_SUBTITLE');

		$html .= $this->getHtmlSelect($aAuthorisationOptions, 'PAYLINE_DIRECT_ACTION', 0, $this->l('Mode de débit'), '', 0,'directCapture');

		$html .= '<div id="directCapture" '.(Configuration::get('PAYLINE_DIRECT_ACTION') == 101 ? 'style="display:none"' : false).'>';
		$html .= $this->getHtmlSelect($aStatesOptions, 'PAYLINE_DIRECT_VALIDATION', 0, $this->l('Statut de validation de commande'), '', 0, '', true);
		$html .= '</div>';
		$html .= '<input type="hidden" name="PAYLINE_DIRECT_MODE" value="CPT">';
		
		$html .= '</div>';
		$html .= '</div><!-- .payline-payment-direct -->';
		// END PAYMENT DIRECT
		
		// WEB PAYMENT BY SUBSCRIBE
		$html .= '<div class="payline-payment-subscribe payline-payment-cfg-container">';
		$html .= '<h3>'.$this->l('Web payment by subscribe').'</h3>';
		$html .= '<p class="payline-payment-baseline">'.$this->l('Définissez le début de l\'échéancier à compter de la seconde échéance, son jour dans le cas d\'une périodicité mensuelle, et leur nombre.').'</p>';
		
		$html .= $this->getHtmlSwitchOnOff('PAYLINE_SUBSCRIBE_ENABLE', 0, '');
		
		$html .= '<p class="center"><input type="button" class="button payline-cfg-button" value="'.$this->l('Configure').'" /></p>';
		$html .= '<div class="payline-payment-cfg-fields">';

		$html .= $this->getMultilangFieldHtml($this->l('Title'), 'PAYLINE_SUBSCRIBE_TITLE');
		$html .= $this->getMultilangFieldHtml($this->l('Subtitle'), 'PAYLINE_SUBSCRIBE_SUBTITLE');


		$options = array(
			1=>$this->l('Due day '),
			2=>$this->l('After a period '),
			3=>$this->l('After two periods ')
		);

		$html .= $this->getHtmlSelect($options, 'PAYLINE_SUBSCRIBE_START_DATE', 1, $this->l('Start date of scheduler'), '');

		$options = range(0,31);

		$html .= $this->getHtmlSelect($aPeriodicityOptions, 'PAYLINE_SUBSCRIBE_PERIODICITY', 50, $this->l('Periodicity of payments'), '');
		$html .= $this->getHtmlSelect($options, 'PAYLINE_SUBSCRIBE_DAY',0, $this->l('Recurring days'), $this->l('0 if you want the payment to take place the same day as the date of the first order'));

		$nbInstalment = array(
		    1 => $this->l('No limit '),
		    2 => '2',
		    3 => '3',
		    4 => '4',
		    5 => '5',
		    6 => '6',
		    7 => '7',
		    8 => '8',
		    9 => '9',
		    10 => '10',
		    11 => '11',
		    12 => '12',
		    13 => '13',
		    14 => '14',
		    15 => '15',
		    16 => '16',
		    17 => '17',
		    18 => '18',
		    19 => '19',
		    20 => '20',
		    21 => '21',
		    22 => '22',
		    23 => '23',
		    24 => '24',
		    25 => '25',
		    26 => '26',
		    27 => '27',
		    28 => '28',
		    29 => '29',
		    30 => '30',
		    31 => '31',
		    32 => '32',
		    33 => '33',
		    34 => '34',
		    35 => '35',
		    36 => '36'
		);

		$html .= $this->getHtmlSelect($nbInstalment, 'PAYLINE_SUBSCRIBE_NUMBER', 12, $this->l('Number of payments'), '');

		$html .= $this->getHtmlSelect($aZeroOneOptions, 'PAYLINE_SUBSCRIBE_GIFT_ACTIVE', 0, $this->l('Activate subscription gift'), $this->l('If activated, it will create a voucher for the first subscription'), 0, 'subscribeGift');

		$html .= '<div id="subscribeGift">';
		$html .= $this->_adminFormTextinput('PAYLINE_SUBSCRIBE_AMOUNT_GIFT', $this->l('Percentage or amount of discount'), '', 'size="3"');

		$defaultCurrency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
		$options = array(
				'0'=>$this->l('--------'),
				'percent'=>$this->l('Percent'),
				'amount'=>$this->l('Amount') . ' ('.$defaultCurrency->sign.')'
		);
		$html .= $this->getHtmlSelect($options, 'PAYLINE_SUBSCRIBE_TYPE_GIFT', 0, $this->l('Discount\'s type'), '');
		$html .= '</div>';

		$options = range(0,12);

		$html .= $this->getHtmlSelect($options, 'PAYLINE_SUBSCRIBE_NUMBER_PENDING', 0, $this->l('Number of times the client may suspend payment'), $this->l('0 to disallow this option'));
		
		$html .= '<input type="hidden" name="PAYLINE_SUBSCRIBE_MODE" value="REC">';
		$html .= '<input type="hidden" name="PAYLINE_SUBSCRIBE_ACTION" value="101">';

		// Product list
		$html .= $this->getHtmlInputProducts('PAYLINE_SUBSCRIBE_PLIST', $this->l('Product list:'));
		// Exclusive product
		$html .= $this->getHtmlSwitchOnOff('PAYLINE_SUBSCRIBE_EXCLUSIVE', 0, $this->l('Set this product list as exclusive'), $this->l('If a product from the cart is in this list, only this method will be shown. Else, this method will only be available if a product will be in your cart.'));

		$html .= '</div>';
		$html .= '</div><!-- .payline-payment-subscribe -->';
		// END SUBSCRIBE PAYMENT
		
		$html .= '</div><!-- .payline-payment-container-left -->';
		$html .= '<div class="payline-payment-container-right">';
		
		$html .= '<div class="payline-payment-n-time payline-payment-cfg-container">';
		// WEB PAYMENT IN SEVERAL TIMES
		$html .= '<h3>'.$this->l('Web payment in several times').'</h3>';
		$html .= '<p class="payline-payment-baseline">'.$this->l('Il s\'agit d\'une variante de la méthode de paiement traditionnelle, à l\'exception que les paiements sont fractionnés selon le calendrier de votre choix.').'</p>';
		
		$html .= $this->getHtmlSwitchOnOff('PAYLINE_RECURRING_ENABLE', 0, '');
		
		$html .= '<p class="center"><input type="button" class="button payline-cfg-button" value="'.$this->l('Configure').'" /></p>';
		$html .= '<div class="payline-payment-cfg-fields">';

		$html .= $this->getMultilangFieldHtml($this->l('Title'), 'PAYLINE_RECURRING_TITLE');
		$html .= $this->getMultilangFieldHtml($this->l('Subtitle'), 'PAYLINE_RECURRING_SUBTITLE');

		$html .= $this->_adminFormTextinput('PAYLINE_RECURRING_TRIGGER', $this->l('Amount trigger'), $this->l('Amount under which payment in several times is not displayed'), 'size="65"');
		$options = array_combine(range(2,99), range(2,99));
		$weights = array_combine(range(0,70,5), range(0,70,5));

		$html .= $this->getHtmlSelect($options, 'PAYLINE_RECURRING_NUMBER', 2, $this->l('Number of payments'), '', 0,'timesWeb');

		$html .= $this->getHtmlSelect($aPeriodicityOptions, 'PAYLINE_RECURRING_PERIODICITY', 40, $this->l('Periodicity of payments'), '');
		
		$html .= $this->getHtmlSelect($weights, 'PAYLINE_RECURRING_FIRST_WEIGHT', 0, $this->l('First payment weight'), $this->l('Percentage of total amount for first payment'), 0,'firstWeb');
		
		$html .= $this->getHtmlSwitchOnOff('PAYLINE_RECURRING_BY_WALLET', 0, $this->l('Payment by wallet'));
		
		$html .= $this->_adminFormTextinput('PAYLINE_RECURRING_TPL_URL', $this->l('Custom payment template URL'), $this->l('https ://.... Only.'), 'size="65"');
		$html .= $this->_adminFormTextinput('PAYLINE_RECURRING_CUSTOM_CODE', $this->l('Custom payment page code'), $this->l('Example : ')."1fd51s2dfs51", 'size="65"');

		$html .= '<input type="hidden" name="PAYLINE_RECURRING_MODE" value="NX">';
		$html .= '<input type="hidden" name="PAYLINE_RECURRING_ACTION" value="101">';
		$html .= '</div>';
		$html .= '</div><!-- .payline-payment-n-time -->';
		// END WEB PAYMENT IN SEVERAL TIMES

		// PAYMENT BY WALLET
		$html .= '<div class="payline-payment-wallet payline-payment-cfg-container">';
		$html .= '<h3>'.$this->l('Payment by Wallet').'</h3>';
		$html .= '<p class="payline-payment-baseline">'.$this->l('Simplifiez votre processus d\'achat en proposant à vos clients de payer à l\'aide d\'un identifiant et mot de passe facile à mémoriser et ne nécessitant pas la saisie de leur numéro de CB.').'</p>';

		$html .= $this->getHtmlSwitchOnOff('PAYLINE_WALLET_ENABLE', 0, '');
		
		$html .= '<p class="center"><input type="button" class="button payline-cfg-button" value="'.$this->l('Configure').'" /></p>';
		$html .= '<div class="payline-payment-cfg-fields">';

		$html .= $this->getMultilangFieldHtml($this->l('Title'), 'PAYLINE_WALLET_TITLE');
		$html .= $this->getMultilangFieldHtml($this->l('Subtitle'), 'PAYLINE_WALLET_SUBTITLE');

		$html .= $this->getHtmlSelect($aAuthorisationOptions, 'PAYLINE_WALLET_ACTION', 0, $this->l('Mode de débit'), '', 0,'walletCapture');

		$html .= '<div id="walletCapture" '.(Configuration::get('PAYLINE_WALLET_ACTION') == 101 ? 'style="display:none"' : false).'>';
		$html .= $this->getHtmlSelect($aStatesOptions, 'PAYLINE_WALLET_VALIDATION', 0, $this->l('Statut de validation de commande'), '', 0,'walletCapture', true);
		$html .= '</div>';

		//$html .= $this->getHtmlSelect($aZeroOneOptions, 'PAYLINE_WALLET_PERSONNAL_DATA', 0, $this->l('Allow updating of personal data'), '');

		//$html .= $this->getHtmlSelect($aZeroOneOptions, 'PAYLINE_WALLET_PAYMENT_DATA', 0, $this->l('Allow updating of payment data'), '');
		$html .= $this->getHtmlSwitchOnOff('PAYLINE_WALLET_PERSONNAL_DATA', 0, $this->l('Allow updating of personal data'));
		$html .= $this->getHtmlSwitchOnOff('PAYLINE_WALLET_PAYMENT_DATA', 0, $this->l('Allow updating of payment data'));

		$html .= $this->_adminFormTextinput('PAYLINE_WALLET_CUSTOM_CODE', $this->l('Custom payment page code'), $this->l('Example : ')."1fd51s2dfs51", 'size="65"');
		$html .= $this->_adminFormTextinput('PAYLINE_WALLET_TPL_URL', $this->l('Custom payment template URL'), $this->l('https ://.... Only.'), 'size="65"');
		
		$html .= '</div>';
		$html .= '</div><!-- .payline-payment-wallet -->';
		// END PAYMENT BY WALLET
		
		if ($this->isSDDContractAvailable()) {
			// WEB PAYMENT BY DIRECT DEBIT
			$html .= '<div class="payline-payment-direct-debit payline-payment-cfg-container">';
			$html .= '<h3>'.$this->l('Web payment by direct debit').'</h3>';
			$html .= '<p class="payline-payment-baseline">'.$this->l('Définissez le début de l\'échéancier à compter de la seconde échéance, son jour dans le cas d\'une périodicité mensuelle, et leur nombre.').'</p>';
			
			$html .= $this->getHtmlSwitchOnOff('PAYLINE_DIRDEBIT_ENABLE', 0, '');
			
			$html .= '<p class="center"><input type="button" class="button payline-cfg-button" value="'.$this->l('Configure').'" /></p>';
			$html .= '<div class="payline-payment-cfg-fields">';

			$html .= $this->getMultilangFieldHtml($this->l('Title'), 'PAYLINE_DIRDEBIT_TITLE');
			$html .= $this->getMultilangFieldHtml($this->l('Subtitle'), 'PAYLINE_DIRDEBIT_SUBTITLE');

			$html .= $this->getHtmlSelect($aPeriodicityOptions, 'PAYLINE_DIRDEBIT_PERIODICITY', 50, $this->l('Periodicity of payments'), '');
			$options = range(0,31);
			$html .= $this->getHtmlSelect($options, 'PAYLINE_DIRDEBIT_DAY',0, $this->l('Recurring days'), $this->l('0 if you want the payment to take place the same day as the date of the first order'));

			$options = array_combine(range(2,99), range(2,99));
			$html .= $this->getHtmlSelect($options, 'PAYLINE_DIRDEBIT_NUMBER', 2, $this->l('Number of payments'), '');
			
			// Set cron key if not defined already
			if (Configuration::get('PAYLINE_CRON_SECURE_KEY') === false)
				Configuration::updateValue('PAYLINE_CRON_SECURE_KEY', Tools::passwdGen(16));
			$html .= $this->getHtmlSelect($this->getSDDContractsForSelect(), 'PAYLINE_DIRDEBIT_CONTRACT', 0, $this->l('Main contract'), '', 0, '', true);
			$urlCrontab = Tools::getHttpHost(true, false) . __PS_BASE_URI__ . 'modules/payline/cron.php?secureKey=' . Configuration::get('PAYLINE_CRON_SECURE_KEY');
			$html .= '
				<p><strong>' . $this->l('URL to use for crontab task:') . '</strong><br /><br />
				<a class="colored-payline-link" href="' . $urlCrontab . '">' . $urlCrontab . '</a>
			</p>';
			
			$html .= '<input type="hidden" name="PAYLINE_DIRDEBIT_MODE" value="CPT">';
			$html .= '<input type="hidden" name="PAYLINE_DIRDEBIT_ACTION" value="101">';

			// Product list
			$html .= $this->getHtmlInputProducts('PAYLINE_DIRDEBIT_PLIST', $this->l('Product list:'));
			// Exclusive product
			$html .= $this->getHtmlSwitchOnOff('PAYLINE_DIRDEBIT_EXCLUSIVE', 0, $this->l('Set this product list as exclusive'), $this->l('If a product from the cart is in this list, only this method will be shown. Else, this method will only be available if a product will be in your cart.'));

			$html .= '</div>';
			$html .= '</div><!-- .payline-payment-direct-debit -->';
			// END DIRECT DEBIT PAYMENT
		}
		
		$html .= '</div><!-- .payline-payment-container-right -->';

		$html .= '<p class="center clear"><input class="button submitPayline" type="submit" name="submitPayline" value="'.$this->l('Save settings').'" /></p>';
		$html .= '<input type="hidden" name="PAYLINE_WALLET_MODE" value="CPT">';
		return $html;

	}

	/**
	 * @refactor
	 * @return string
	 */
	protected function _adminFormTextinput($name, $label, $description=null, $extra_attributes='', $width = 0) {
		$value = Configuration::get($name);
		$html  = "\n";
		$html .= '<div class="cfg-'.Tools::strtolower($name).'-container">';
		$html .= '<label for="'.$name.'" style="text-align:left; '.($width!=0 ? ' width: '.$width.'px;': false).'">'.$label.'</label>';
		$html .= '<div class="margin-form cfg-'.Tools::strtolower($name).'">';
		$html .= '<input type="text" id="'.$name.'" name="'.$name.'" value="'.$value.'" '.$extra_attributes.'/>';
		$html .= '<p '.($width!=0 ? ' style="margin-left: '.($width - 190).'px;"' : false).'>'.$description.'</p>';
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * @refactor
	 * @return string
	 */
	protected function _adminFormSelect($options, $selected, $name, $label, $description, $width = 0, $field = '') {
	$arrayToJS = array('PAYLINE_WEB_CASH_ACTION', 'PAYLINE_DIRECT_ACTION', 'PAYLINE_WALLET_ACTION', 'PAYLINE_SUBSCRIBE_GIFT_ACTIVE');
		$html = "\n";
	$html .= '<div class="cfg-'.Tools::strtolower($name).'-container">';
	$html .= '<label for="'.$name.'" style="text-align:left; '.($width!=0 ? ' width: '.$width.'px;': false).'">'.$label.'</label>';
	$html .= '<div class="margin-form">';
		$html .= '<select name="'.$name.'" id="'.$name.'" '.(in_array($name,$arrayToJS) ? 'onChange="DisplayField(\''.$name.'\',\''.$field.'\');"' : false).'>';
			foreach($options as $value => $label) {
			$html .= '<option value="'.$value.'"';
			$is_selected = is_array($selected) ? in_array($value,$selected)	: ((string)$value == (string)$selected);
				$html .= $is_selected ? ' selected="selected"' : '';
			$html .= ' style="padding-left: 5px; padding-right:5px">'.$label.'</option>';
	}
	$html .= '</select>';
	if (in_array($name, $arrayToJS))
		$html .= '<script>$(document).ready(function() { $(\'select#'.$name.'\').trigger(\'change\'); });</script>';
	if (!empty($description))
		$html .= '<p>'.$description.'</p>';
	$html .= '</div>';
	$html .= '</div>';
	return $html;
	}

	# DATE HANDLING

	/**
	 * Converts french date format into another
	 * Unfortunately, DateTime::createFromFormat doesn't exists in 5.2, so we cannot use it.
	 * @param string $sDate Date in format Y/m/d
	 * @param string $sTargetFormat Target date format, default Y-m-d
	 * @return string Formatted date
	 */
	public function convertFrenchDate($sDate, $sTargetFormat = 'Y-m-d'){

		if (strpos($sDate , "/") === false) {

			return $sDate;

		}

		list($sDay,$sMonth,$sYear) = explode('/', $sDate);

		$oDate = new DateTime();

		$oDate->setDate((int)$sYear, (int)$sMonth, (int)$sDay);

		return $oDate->format($sTargetFormat);

	} // convertFrenchDate

	/**
	 * Gets date of the next payment counting from today
	 * @param integer $nDiffered Number of differed units
	 * @return string  Date of next payment
	 */
	public function getDateSubscribe($nDiffered = 0){

		return $this->getNextDateSubscribe(date('Y-m-d'), (int)$nDiffered);

	} // getDateSubscribe

	/**
	 * Gets date of the next payment counting from base date
	 * @param string $sBaseDate Base date in Y-m-d format
	 * @param integer $nDiffered Number of differed units
	 * @param string $sDateFormat Date format
	 * @return string  Date of next payment
	 */
	public function getNextDateSubscribe($sBaseDate, $nDiffered = 0, $sDateFormat = null) {

		return $this->getDifferedDate(
				Configuration::get('PAYLINE_SUBSCRIBE_PERIODICITY'),
				strtotime($sBaseDate),
				(int)$nDiffered,
				$sDateFormat);

	} // getNextDateSubscribe

	/**
	 * Returns date of the differed payment
	 * @param integer $nMode Defines quantity and type of units
	 * @param string $nBaseDate Base date as timestamp
	 * @param int $nDiffered number of units to differ
	 * @param string $sDateFormat Date format
	 * @return string Date of the differed payment in d/m/Y format
	 */
	protected function getDifferedDate($nMode, $nBaseDate, $nDiffered= 0, $sDateFormat = null){

		if ($sDateFormat == null)
			$sDateFormat = 'd/m/Y';

		$sDateDiff = date('Y-m-d');

		if (isset($this->aDifferedModes[$nMode])) {

			$aMode = $this->aDifferedModes[$nMode];

			if ($nMode === 50) {

				$nMonthDiffs = (int)$nDiffered  + 1 ;

				$nDaysDiffs = ((int)$nDiffered + 1) * 15;

				$sDateDiff = sprintf('+ %d month + %d day', $nMonthDiffs, $nDaysDiffs);

			} else {

				$nDateDiff = $aMode['multiplier'] * ((int)$nDiffered + 1);

				$sDateDiff = sprintf('+ %d %s', $nDateDiff, $aMode['unit']);

			} // if

		} // if

		return date($sDateFormat, strtotime($sDateDiff, (int)$nBaseDate));

	}  // getDifferedDate

	# MESSAGES

	/**
	 * Adds message invisible to customer
	 * @param integer $nIdOrder
	 * @param string $sMessage
	 * @return boolean
	 */
	public function addPrivateMessage($nIdOrder, $sMessage) {

		return $this->addMessage($nIdOrder, $sMessage, false);

	} // addPrivateMessage

	/**
	 * Adds message visible to customer
	 * @param integer $nIdOrder
	 * @param string $sMessage
	 * @return boolean
	 */
	public function addPublicMessage($nIdOrder, $sMessage){

		return $this->addMessage($nIdOrder, $sMessage, true);

	} // addPublicMessage

	/**
	 * Adds message
	 * @param integer $nIdOrder
	 * @param string $sMessage
	 * @param boolean $bIsPrivate
	 * @return boolean
	 */
	protected function addMessage($nIdOrder, $sMessage, $bIsPrivate = false){

		$nIdOrder = (int)$nIdOrder;

		if ($nIdOrder <= 0){

			return false;

		} // if

		$sMessage = strip_tags((string)$sMessage, '<br>');

		if (!Validate::isCleanHtml($sMessage)){

			$sMessage = $this->l('Payment message is not valid, please check your module.');

		} // if

		$oOrder = new Order($nIdOrder);

		$oMessage = new Message();

		$oMessage->id_order = $nIdOrder;

		$oMessage->id_cart = $oOrder->id_cart;

		$oMessage->id_employee = _PS_ADMIN_PROFILE_;

		$oMessage->message = 'Payline - ' . $sMessage;

		$oMessage->private = $bIsPrivate ? 1 : 0;

		return $oMessage->add();

	} // addMessage

	# SDK OBJKECT HANDLING

	/**
	 * Creates instance of PaylineSDK, using configuration values
	 * @return paylineSDK
	 */
	public function createPaylineSDKInstance(){

		return new paylineSDK(
				Configuration::get('PAYLINE_MERCHANT_ID'),
				Configuration::get('PAYLINE_ACCESS_KEY'),
				Configuration::get('PAYLINE_PROXY_HOST'),
				Configuration::get('PAYLINE_PROXY_PORT'),
				Configuration::get('PAYLINE_PROXY_LOGIN'),
				Configuration::get('PAYLINE_PROXY_PASSWORD'),
				Configuration::get('PAYLINE_PRODUCTION') == '1' ? paylineSDK::ENV_PROD : paylineSDK::ENV_HOMO
		);

	} // createPaylineSDKInstance

	/**
	 * Returns paylineSDK object instance. If not exists, creates it
	 * @return paylineSDK
	 */
	public function getPaylineSDK(){

		if ($this->oPaylineSDK === null) {

			$this->oPaylineSDK = $this->createPaylineSDKInstance();

		} // if

		return $this->oPaylineSDK;

	} // getPaylineSDK

	/**
	 * Generally PaylineSDK::webServiceRequest calls are returning an array, but sometimes (when errors) we have
	 * an fake pl_result object instead. But, all methods of payline module are expecting array as return.
	 * So we are dealing with it here.
	 * @param array|pl_result $mWsResult
	 * @return array
	 */
	protected function parseWsResult($mWsResult){

		$aResult = null;

		if ($mWsResult instanceof pl_result){

			$aResult = array('result'=>(array)$mWsResult);

		} else {

			$aResult = $mWsResult;

		} // if

		$this->log('Webservice return', isset($aResult['result']) ? $aResult['result'] : var_export($aResult, true));

		return $aResult;

	} // parseWsResult

	/**
	 * Sets urls and personalized templates info for WS request
	 * @param string $sMode (see class constants)
	 * @return boolean
	 */
	protected function setPaylineSDKUrls($sMode){

		$sKey = isset($this->aUrlsConfig[$sMode]) ? $sMode : self::MODE_WEBCASH;

		$oPaylineSDK = $this->getPaylineSDK();
		foreach ($this->aUrlsConfig[$sKey] as $sPropertyName => $mConfig) {
			if (!is_array($mConfig) && strpos($mConfig, '%%URL%%') !== false || is_array($mConfig) && strpos($mConfig[0], '%%URL%%') !== false) {
				$sQuery = $mConfig[1];
				if (is_array($mConfig))
					$oPaylineSDK->{$sPropertyName} = str_replace('%%URL%%', $this->sBaseUrl, $mConfig[0]) . $sQuery;
				else
					$oPaylineSDK->{$sPropertyName} = str_replace('%%URL%%', $this->sBaseUrl, $mConfig);
			} else {
				$sQuery = '';
				$sConfigVarName = $mConfig;
				if (is_array($mConfig)) {
					$sConfigVarName = $mConfig[0];
					$sQuery = $mConfig[1];
				}
				$oPaylineSDK->{$sPropertyName} = ($sConfigVarName ? Configuration::get($sConfigVarName) . $sQuery : '');
			}

		} // foreach

		return $sMode === $sKey;

	} // setPaylineSDKUrls

	/**
	 * Adds cart items to WS request, if item's price greater than 0
	 * @param Cart $oCart
	 */
	protected function addCartProducts($oCart){

		foreach($oCart->getProducts() as $aProductDetail) {

			if(round($aProductDetail['price']*100) > 0){
				

				$this->getPaylineSDK()->setItem(
						array(
							'ref'		=> $aProductDetail['reference'],
							'price'		=> round($aProductDetail['price']*100),
							'quantity'	=> $aProductDetail['cart_quantity'],
							'comment'	=> $aProductDetail['name'],
							//'category'	=> preg_replace('/[^a-zA-Z0-9]/','',$aProductDetail['category']),
							'category'	=> 8, // Bijouterie
							'brand'		=> $aProductDetail['id_manufacturer']
						));

			} // if

		} // foreach

	} // addCartProducts

	/**
	 * Sets private data for WS request
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	protected function setPrivateData($sKey, $mValue){

		$this->getPaylineSDK()->setPrivate(array('key' => $sKey, 'value' => $mValue));

	}

	/**
	 * Converts private data from WS return (array of objects) into associative array
	 * @param array $aPrivateData WS return's privateData array of objects
	 * @return array of <string:key => mixed:value>
	 */
	protected function convertPrivateDataToArray($aPrivateData){

		$aResult = array();

		if (is_array($aPrivateData) && !empty($aPrivateData)){

			foreach($aPrivateData as $oData){

				if (is_object($oData) && property_exists($oData, 'key')  && property_exists($oData, 'value')) {

					$aResult[$oData->key]	=	$oData->value;

				} // if

			} // foreach

		} // if

		return $aResult;

	} // convertPrivateDataToArray

	# RESULT HANDLING

	/**
	 * Returns payment type
	 * @param array $aData
	 * @return string|null Payment type (REC, NX, CPT) or null if cannot be defined
	 */
	public function getType($aData){

		if (!is_array($aData)){

			return null;

		} // if

		if (isset($aData['transactionId'])){

			$aArgs = array(
					'orderRef' 			=> '',
					'startDate' 		=> '',
					'endDate'			=> '',
					'transactionHistory'=> '',
					'archiveSearch'		=> '',
			);

			# @ws_call
			$aTransactionDetails = $this->getPaylineSDK()->getTransactionDetails(/*$aArgs +*/ array('transactionId' => $aData['transactionId'], 'version' => self::API_VERSION));

			if (!is_array($aTransactionDetails) || empty($aTransactionDetails)){

				return null;

			} // if

			if (isset($aTransactionDetails['payment']['mode']) && $aTransactionDetails['payment']['mode']=='NX') {

				return $aTransactionDetails['payment']['mode'];

			} // if

			// This is id of original cart (from first order)
			if (isset($aTransactionDetails['order']['ref']) && $aTransactionDetails['order']['ref']){

				if ($this->isSubscriptionSavedInDatabase($aTransactionDetails['order']['ref'])) {

					return 'REC';

				} else if (isset($aData['notificationType']) && $aData['notificationType']== 'BILL'){

					return 'NX';

				}// if

			} // if

			if (isset($aTransactionDetails['payment']['mode'])) {

				return $aTransactionDetails['payment']['mode'];

			} // if

		} else if (isset($aData['token'])) {

			# @ws_call
			$aWebPaymentDetails = $this->getPaylineSDK()->getWebPaymentDetails(array('token' => $aData['token'], 'version' => self::API_VERSION));

			if ($aWebPaymentDetails && isset($aWebPaymentDetails['transaction']['id']) && $aWebPaymentDetails['transaction']['id']){

				return $this->getType(array('transactionId'=>$aWebPaymentDetails['transaction']['id']));

			} // if

			// This is id of original cart (from first order)
			if ($aWebPaymentDetails && isset($aWebPaymentDetails['order']['ref']) && $aWebPaymentDetails['order']['ref']){

				if ($this->isSubscriptionSavedInDatabase($aWebPaymentDetails['order']['ref'])) {

					return 'REC';

				}  else if (isset($aData['notificationType']) && $aData['notificationType']=='BILL'){

					return 'NX';

				}// if

			} // if

			if (isset($aWebPaymentDetails['payment']['mode'])) {

				return $aWebPaymentDetails['payment']['mode'];

			} // if
		} // if

		return null;

	} // getType

	protected function isSubscriptionSavedInDatabase($sReference){

		$nCartId = $this->getCartIdFromOrderReference($sReference);

		return ($nCartId > 0 && ObjectModel::existsInDatabase($nCartId, 'cart') && $this->getPaylineSubscribeDataByCartId($nCartId));

	}

	/**
	 * Tests if result code belongs to specified set
	 * @param string $sResult Result code
	 * @param string|array $sResultSet Name of predefined set (defined in $aResultSets) or set of results codes
	 * @return boolean True if code belongs to specified set
	 */
	protected function isResultInSet($sResult, $mResultSet = self::RS_VALID_SIMPLE){

		if (is_array($mResultSet)){

			$aSet = $mResultSet;

		} else if (is_scalar($mResultSet) && isset($this->aResultSets[$mResultSet])) {

			$aSet = $this->aResultSets[$mResultSet];

		} else {

			$aSet = array();

		} // if

		return in_array($sResult, $aSet, true);

	} // isResultInSet

	# CONVERSIONS & MISC EXTRACTORS

	/**
	 * Extracts id cart from order reference
	 * @param string $sOrderReference Likely "[Mon panier XXX]"
	 * @return number
	 */
	protected function getCartIdFromOrderReference($sOrderReference){

		return (int) preg_replace('/\D/','', (string)$sOrderReference);

	} // getCartIdFromOrderReference

	/**
	 * Converts Prestashop addres into array
	 * @param Address $oAddress
	 * @return array
	 */
	protected function getAddress($oAddress){	    
		$state = "";
		if($oAddress->id_state){
			$addressState = new State($oAddress->id_state);
			$state = $addressState->iso_code; // ISo state code is required by Paypal
		}
		return array(
			'name'           => $oAddress->alias, 
			'firstname'      => $oAddress->firstname,
			'lastname'       => $oAddress->lastname,
			'street'         => $oAddress->address1,
			'street2'        => $oAddress->address2,
			'cityName'       => $oAddress->city,
			'zipCode'        => $oAddress->postcode,
			'country'        => $oAddress->country,
			'state'          => $state,
			'phone'          => str_replace(array(' ','.','(',')','-'), '', $oAddress->phone),
			'mobilePhone'    => str_replace(array(' ','.','(',')','-'), '', $oAddress->phone_mobile)
		);
	}

	/**
	 * Finds last billing record object with given transaction id
	 * @param array $aBillingRecords Array of billing record objects
	 * @param atring $sTransactionId Id of transaction
	 * @return null|object Billing record object or null
	 */
	protected function getBillingRecordByTransactionId($aBillingRecords, $sTransactionId){

		$mResult = null;

		foreach($aBillingRecords as $oBillingRecord){

			if($oBillingRecord->status != 0 && $oBillingRecord->transaction->id == $sTransactionId){

				$mResult = $oBillingRecord;

			} // if

		} // foreach

		return $mResult;

	} // getBillingRecordByTransactionId

	/**
	 * Gets cardInd if given card matches a card from customer's wallet
	 * @param integer $nCustomerId
	 * @param array $aCard
	 * @return string|null cardInd or null if not found
	 */
	protected function getCardInd($nCustomerId, $aCard){

		$aCards = $this->getMyCards($nCustomerId);

		foreach($aCards as $aCandidate){

			if (isset($aCandidate['number']) &&
					isset($aCard['number']) &&
					isset($aCandidate['cardInd']) &&
					$aCandidate['number'] == $aCard['number'] &&
					$aCandidate['expirationDate'] == $aCard['expirationDate'] &&
					$aCandidate['type'] == $aCard['type']) {

				return $aCandidate['cardInd'];

			} // if

		} // foreach

		return null;

	} // getCardInd

	# CART & ORDER MANAGEMENT

	protected function getCartIdFromPaymentRecord($aPaymentRecord){

		$nCartId = null;

		if (isset($aPaymentRecord['privateDataList']) && isset($aPaymentRecord['privateDataList']['privateData'])){

			$aPrivateData = isset($aPaymentRecord['privateDataList']['privateData']) ? $this->convertPrivateDataToArray($aPaymentRecord['privateDataList']['privateData']) : array();

			$nCartId = isset($aPrivateData['idCart']) ? (int)$aPrivateData['idCart'] : null;

		} // if

		return $nCartId;

	}

	/**
     *
     */
	protected function getCartIdFromTransactionDetails($aTransactionDetails){

		$nCartId = null;

		if (isset($aTransactionDetails['order']['ref'])){

			$nCartId = $this->getCartIdFromOrderReference($aTransactionDetails['order']['ref']);

		} // if

		return $nCartId;

	}

	/**
	 * Duplicates cart with given id
	 * @param integer $nCartId Cart id
	 * @return null|Cart Null if failed, new cart otherwise
	 */
	protected function duplicateCartFromId($nCartId) {

		$oOldCart = new Cart($nCartId);

		$aNewCart = $oOldCart->duplicate();

		if(!$aNewCart['success']  || !$aNewCart['cart']){

			return null;

		} // if

		return $aNewCart['cart'];

	} // duplicateCartFromId

	/**
	 * Tests if order is first installement
	 * Currently: it must have status "waiting for installement" in history and it couldn't have status "Payment accepted" in history
	 * @param Order $oOrder
	 * @return boolean True if it's first installement
	 */
	protected function isFirstInstallement($oOrder){

		$aHistory = $this->getOrderHistory($oOrder);

		return (in_array(Configuration::get('PAYLINE_ID_ORDER_STATE_SUBSCRIBE'), $aHistory) &&
				!(in_array(_PS_OS_PAYMENT_, $aHistory) || in_array( Configuration::get('PAYLINE_ID_STATE_ALERT_SCHEDULE'), $aHistory)));

	}

	/**
	 * Applies reduction to order
	 * @param Order $oOrder
	 * @param float $fAmount
	 * @return boolean True if reduction was applied successfully
	 */
	protected function applyReduction($oOrder, $fAmount){

		$oCart = new Cart($oOrder->id_cart);

		$fOrderTotal = $oCart->getOrderTotal();

		//We calculate amount reduce
		$fReduceAmount = $fOrderTotal - $fAmount;

		$oOrder->total_discounts = $fReduceAmount;

		$oOrder->total_discounts_tax_incl = $fReduceAmount;

		$oOrder->total_paid = $fAmount;

		$oOrder->total_paid_real = $fAmount;

		$oOrder->total_paid_tax_incl = $fAmount;

		$oOrder->total_paid_tax_excl = round($order->total_paid_tax_excl* $fAmount / $fOrderTotal,2);

		$aLogData = array(
				'id_order' => $oOrder->id,
				'id_cart' => $oCart->id,
				'amount' => $fAmount,
				'reduce amount' => $fReduceAmount,
				'order_total'=>$fOrderTotal
		);

		if ($oOrder->update()){

			$this->addMessage($oOrder->id, sprintf($this->l('Reduction applied : %1.2f'),  $fReduceAmount), true);

			$this->log('Discount applied', $aLogData);

			return true;

		} else {

			$this->log('Discount NOT applied', $aLogData);

			return false;

		} // if

	} // applyReduction

	# MULTISHOP

	/**
	 * Get current shop group and shop ids
	 * @return array of (integer|null : id shop group, integer|null : id shop)
	 */
	protected function getShopContextIdPair(){

		$nIdShopGroup = Shop::getContextShopGroupID(true);

		$nIdShop = Shop::getContextShopID(true);

		if ($nIdShop) {

			return array(0, $nIdShop);

		} elseif ($nIdShopGroup) {

			return array($nIdShopGroup, 0);

		} else {

			return array(0, 0);

		} // if

	} // getShopContextIdPair

	/**
	 * Returns sql string with shop restriction
	 * @param int|null $nIdShopGroup Id shop group or null. If id shop group = -1 (default), current context value is used
	 * @param int|null $nIdShop Id shop or null. If id shop = -1 (default), current context value is used
	 * @return string Part of sql WHERE clause defining shop / shop group
	 */
	protected function getSqlShopRestriction($nIdShopGroup = -1, $nIdShop = -1){

		if ($nIdShopGroup === -1){

			$nIdShopGroup = Shop::getContextShopGroupID(true);

		} // if

		if ($nIdShop === -1){

			$nIdShop = Shop::getContextShopID(true);

		} // if

		if ($nIdShop) {

			return ' AND id_shop = '.(int)$nIdShop;

		} elseif ($nIdShopGroup) {

			return ' AND id_shop_group = '.(int)$nIdShopGroup.' AND id_shop=0';

		} else {

			return ' AND id_shop_group=0 AND id_shop=0';

		} // if

	} // getSqlShopRestriction

	# CONFIGURATION

	/**
	 * Returns message if configuration is not finished
	 * @return string
	 */
	protected function verifyConfiguration(){

		$config = Configuration::getMultiple(array('PAYLINE_MERCHANT_ID', 'PAYLINE_ACCESS_KEY', 'PAYLINE_CONTRACT_NUMBER', 'PAYLINE_POS'));

		if (empty($config['PAYLINE_MERCHANT_ID']) || empty($config['PAYLINE_ACCESS_KEY']) || empty($config['PAYLINE_CONTRACT_NUMBER']) || empty($config['PAYLINE_POS'])) {

			$warning = $this->l('Missing some parameters : ');
			if(empty($config['PAYLINE_MERCHANT_ID'])) 	  	$warning .= $this->l(' - Merchant ID');
			if(empty($config['PAYLINE_ACCESS_KEY']))  	 	$warning .= $this->l(' - Access Key');
			if(empty($config['PAYLINE_POS'])) 				$warning .= $this->l(' - Point of sell');
			if(empty($config['PAYLINE_CONTRACT_NUMBER'])) 	$warning .= $this->l(' - Contract number');

			return $warning;
		}

		return '';

	} // verifyConfiguration

	# LOGGING

	/**
	 * Logs data using payline system. Automatically ads date and IP
	 * @param string $sLabel
	 * @param array $aData
	 */
	public function log($sLabel, $mData = null){

		$aMessages = array();

		$aMessages[] = str_pad(' ' . $sLabel . ' ', 40, '*', STR_PAD_BOTH);

		if (is_array($mData)){

			$aMessages[] = sprintf('[%s] [%s]', date('Y-m-d H:i:s'), isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '???');

			foreach ($mData as $mKey=>$sMessage) {

				$aMessages[]  = sprintf('%s : %s',  $mKey, is_scalar($sMessage) ? $sMessage : var_export($sMessage, true));

			} // foreach

		} else if ($mData!==null){

			$aMessages[] = (string)$mData;

		} // if

		if ($mData !== null)
			$aMessages[] = str_pad(' /' . $sLabel . ' ', 40, '*', STR_PAD_BOTH);

		foreach ($aMessages as $sMessageLine){

			$this->getPaylineSDK()->writeTrace($sMessageLine);

			if (PHP_SAPI === 'cli') {

				print $sMessageLine . PHP_EOL;

			}

		}

	} // log


	/**
	 * WebCash payment
	 * @param array $aData
	 * @return boolean
	 */
	public function validateWebPayment($aData){

		$this->log('validateWebPayment', $aData);

		if (!isset($aData['token']) && !isset($aData['paylinetoken'])){

			$this->log('ERROR : not enough parameters');

			return false;

		} // if

		// @ws_call
		$token = isset($aData['token']) ? $aData['token'] : $aData['paylinetoken']; 
		$aResponse = $this->getPaylineSDK()->getWebPaymentDetails(array('token' => $token, 'version' => self::API_VERSION));
		$this->log('getWebPaymentDetails response', $aResponse);

		if(!empty($aResponse)) {

			$sResponseCode = $aResponse['result']['code'];
			$sResponseShortMessage = $aResponse['result']['shortMessage'];
			$sResponseLongMessage = $aResponse['result']['longMessage'];
			

			//WE check if return is Wallet
			if (Tools::getValue('walletInterface')) {

				echo '<script language="javascript">parent.$.fancybox.close(); ' . ($sResponseCode == '02319' ? '' : 'window.parent.location.href="'.$this->context->link->getModuleLink('payline', 'wallet', array('success' => 'true'), true).'";'). ' </script>';

				return true;

			} // if

			$paymentContractDetails = $this->getPaylineContractDetails($aResponse['payment']['contractNumber']);
			/*
			echo '<pre>';
			var_dump($paymentContractDetails);
			echo '</pre>';
			echo "<br/>type : ".$paymentContractDetails[0]['type'];
			exit;
			*/
			
			$pendingCodes = array(
				'02304', // No transaction found for this token
				'02319', // Payment cancelled by Buyer
				'02306', // Customer has to fill his payment data
				'02533', // Customer not redirected to payment page AND session is active
				'02000', // transaction in progress
				'02005' // transaction in progress
			);
			if(in_array($sResponseCode, $pendingCodes)){
			    if($sResponseCode == '02005' && $paymentContractDetails[0]['type'] == 'PRESTO'){ // cas particulier 02005 avec Presto
			        $this->log('Pending Presto payment - order is created with dedicated pending status');
			    }else{
			        Tools::redirectLink(__PS_BASE_URI__ . 'order.php');
			        return false;
			    }

			} // if

			$aPrivateData = $this->convertPrivateDataToArray($aResponse['privateDataList']['privateData']);

			$nCartId = (int)$aPrivateData['idCart'];
			
			// Incorrect token <=> id_cart association
			if ($this->getIdCartByToken($token) != $nCartId) {
				$this->log("getIdCartByToken - cartId for token $token is incorrect");
				Tools::redirectLink(__PS_BASE_URI__ . 'order.php');
				return false;
			} else {
				$this->log('getIdCartByToken - Token OK');
			}

			$oCart = new Cart($nCartId);

			$oCurrency = new Currency((int)$oCart->id_currency);

			$oCustomer = new Customer((int)$oCart->id_customer);

			$fAmount = $aResponse['payment']['amount']/100;

			$nOrderId = (int)Order::getOrderByCartId($nCartId);

			if (!$nOrderId) {
				$this->log('Create the order - Response code ' . $sResponseCode);
				// order does not exists
				if($sResponseCode === '00000' || $sResponseCode === '34230' || $sResponseCode === '34330' || $sResponseCode === '02005') {

					//If amount paid > getTotalCart()
					if($fAmount > $oCart->getOrderTotal()){

						$fAmount = $oCart->getOrderTotal();

					} // if

					if($sResponseCode == '02005' && $paymentContractDetails[0]['type'] == 'PRESTO'){ // cas particulier 02005 avec Presto
					    $nStateId = Configuration::get('PAYLINE_ID_ORDER_STATE_PENDING_PARTNER');
					}else{
					    $nStateId = (Configuration::get('PAYLINE_WEB_CASH_ACTION') == '100' ? Configuration::get('PAYLINE_ID_STATE_AUTO_SIMPLE') : _PS_OS_PAYMENT_);
					}
					
					$aVars = array();
					$aVars['transaction_id'] 	= $aResponse['transaction']['id'];
					$aVars['contract_number'] 	= $aResponse['payment']['contractNumber'];
					$aVars['action'] 			= $aResponse['payment']['action'];
					$aVars['mode'] 				= $aResponse['payment']['mode'];
					$aVars['amount'] 			= $aResponse['payment']['amount'];
					$aVars['currency']			= $aResponse['payment']['currency'];
					$aVars['by'] 				= 'webPayment';
					
					// Direct debit ? - Change order status
					if (Configuration::get('PAYLINE_DIRDEBIT_CONTRACT') == $aResponse['payment']['contractNumber'])
						$nStateId = (int)Configuration::get('PAYLINE_ID_ORDER_STATE_DIRDEBIT');

					$paymentMean = $this->_getPaymentMean($aVars['contract_number']).' via '.$this->displayName.' ('.$aVars['contract_number'].')';
					$this->validateOrder($nCartId, $nStateId, $fAmount , $paymentMean, $this->getL('Transaction Payline : ') . $aVars['transaction_id'] . ' (web)', $aVars, '', '', $oCustomer->secure_key);

					// Direct debit ? - Populate time table
					if (Configuration::get('PAYLINE_DIRDEBIT_CONTRACT') == $aResponse['payment']['contractNumber'])
						$this->_populateDirectDebitTimeTable($nCartId, $oCustomer->id, $aResponse['payment']['contractNumber']);
					
				}else if ($sResponseCode !== '02304' && $sResponseCode !== '02324' && $sResponseCode !== '02534') {

					$this->validateOrder($nCartId, _PS_OS_ERROR_, $fAmount, $this->displayName, $sResponseShortMessage . ' - ' . $sResponseLongMessage . '<br />', array(), '', '', $oCustomer->secure_key);

				} else {
					$this->log('Unknown response code to handle => ' . $sResponseCode);
				}

				$nOrderId = (int)Order::getOrderByCartId($nCartId);

			} else {
				$this->log('Order already exists => ' . $nOrderId);
			}

			if ($nOrderId) {

				$oOrder = new Order($nOrderId);

				$bErr = false;
				if($sResponseCode !== '00000' && $sResponseCode !== '34230' && $sResponseCode !== '34330'){
					$this->log('Wrong response code (error) ' . $sResponseCode);
					$this->log('Redirect to confirmation page with error...');
					$bErr = true;
				} else {
					$this->log('Redirect to confirmation page (order has already been created) ...');
				}

				$this->redirectToConfirmationPage($oOrder, $bErr);

				return true;

			} // if

		} // if

		return false;

	} // validateWebPayment

	/**
	 * Delete all payline gifts (based on code 'PAYLINE-*')
	 * @param unknown_type $oCart
	 */
	protected function removePaylineGifts($oCart){

		foreach ($oCart->getCartRules() as $aRule){

			if (substr($aRule['obj']->code, 0, 8) === 'PAYLINE-') {

				$oCart->removeCartRule((int)$aRule['id_cart_rule']);

				$aRule['obj']->active = false;

				$aRule['obj']->update();

			} // if

		} // foreach

	}

	/**
	 * Creates cart rule for given cart
	 * @param cart $oCart
	 * @param float|null $fAmount Amount ou percentage. If null, value is taken from PAYLINE_SUBSCRIBE_AMOUNT_GIFT
	 * @param string|null $sType 'amount' or 'percent'. If null, PAYLINE_SUBSCRIBE_TYPE_GIFT decides
	 * @return NULL
	 */
	protected function createCartRule($oCart, $fAmount = null, $sType = null){

		$this->log('Creating cart rule', func_get_args());

		if ($fAmount === null){

			$fAmount = (float)Configuration::get('PAYLINE_SUBSCRIBE_AMOUNT_GIFT');

		} // if

		if ($sType === null){

			$sType = Configuration::get('PAYLINE_SUBSCRIBE_TYPE_GIFT') == 'percent' ? 'percent' : 'amount';

		} // if

		$sName = sprintf('PAYLINE-GIFT-%s-%s-%s', $oCart->id, $fAmount, $sType);

		$aName = array();

		foreach (Language::getLanguages(false) as $aLanguage){

			$aName[(int)$aLanguage['id_lang']] = $sName;

		} // foreach

		$oCartRule = new CartRule();

		$oCartRule->name 				= $aName;
		$oCartRule->date_from			= date('Y-m-d');
		$oCartRule->date_to				= date('Y-m-d', time() + 86400);
		$oCartRule->id_customer			= $oCart->id_customer;
		$oCartRule->description 		= $this->l('Generated automatically by Payline');
		$oCartRule->partial_use 		= 0;
		$oCartRule->code 				= 'PAYLINE-' . strtoupper(md5(implode(',', array(Configuration::get('PAYLINE_ACCESS_KEY') , $oCart->id, $fAmount, $sType))));
		$oCartRule->reduction_tax		= true;
		$oCartRule->reduction_currency	= $this->context->currency->id;

		if ($sType === 'percent') {

			$oCartRule->reduction_percent = $fAmount;

		} else {

			$oCartRule->reduction_amount = $fAmount;

		} // if

		if ($oCartRule->save()) {

			return $oCartRule->id;

		} else {

			return null;

		} // if

	}
	
	# DIRECT DEBIT
	
	/**
	 * Run crontab tasks
	 * @return boolean
	 */
	public function runCrontab() {
		$this->log('START : runCrontab');
		$dateCall = date('Y-m-d H:i:s');
		$aDirDebitList = $this->_getDirectDebitToProcess();
		if ($aDirDebitList!== false) {
			$this->log('We have ' . sizeof($aDirDebitList) . ' row(s) to proceed');
			foreach ($aDirDebitList as $aDirDebitRow) {
				$nSourceCartId = (int)$aDirDebitRow['id_cart_origin'];
				// Check cart first
				$oCart = $this->duplicateCartFromId($nSourceCartId);
				if (!$oCart){
					$this->log('ERROR : runCrontab : Unable to duplicate cart ' . $nSourceCartId);
					die('NOK');
				}
				$oCustomer = new Customer((int)$aDirDebitRow['id_customer']);
				$oCurrency = new Currency((int)$oCart->id_currency);
				
				$fAmount = $oCart->getOrderTotal() * 100;
				
				// Payment status
				$nStatusId = (int)Configuration::get('PAYLINE_ID_ORDER_STATE_DIRDEBIT');
				
				$aDoScheduledWalletPaymentParams = array(
					'payment' => array(
						'amount' => $fAmount,
						'currency' => $oCurrency->iso_code_num,
						'action' => Configuration::get('PAYLINE_DIRDEBIT_ACTION'),
						'mode' => Configuration::get('PAYLINE_DIRDEBIT_MODE'),
						'contractNumber' => $aDirDebitRow['contract_number'],
					),
					// Payment date
					'scheduledDate' => date('d/m/Y', strtotime($aDirDebitRow['date_debit'])),
					'walletId' => $this->getOrGenWalletId($oCustomer->id),
					'order' => array(
						'ref' => $this->l('Cart') . (int)$oCart->id,
						'amount' => $fAmount,
						'currency' => $oCurrency->iso_code_num,
						'date' => date('d/m/Y H:i'),
					),
					'version' => self::API_VERSION,
				);
				$aResponse = $this->getPaylineSDK()->doScheduledWalletPayment($aDoScheduledWalletPaymentParams);
				
				if (!empty($aResponse)) {
					$sResponseCode = $aResponse['result']['code'];
					if ($sResponseCode === '02500' || $sResponseCode === '02501') {
						$sValidationMessage = $this->getL('Transaction Payline : ') . ' (Direct Debit) - next installement from cart ' . $nSourceCartId;
						$oCustomer = new Customer((int)$aDirDebitRow['id_customer']);
						$this->validateOrder($oCart->id, $nStatusId, $oCart->getOrderTotal(), $this->displayName, $sValidationMessage, array(), '', '', $oCustomer->secure_key);
						$nNewOrderId = (int)Order::getOrderByCartId($oCart->id);
						
						if ($nNewOrderId) {
							// Update database
							$data = array('payment_record_id' => $aResponse['paymentRecordId'], 'id_order' => $nNewOrderId, 'date_paid' => date('Y-m-d H:i:s'), 'date_call' => $dateCall, 'paid' => 1);
							Db::getInstance()->autoExecute(_DB_PREFIX_ . 'payline_dirdebit', $data, 'UPDATE', '`id_direct_debit`=' . (int)$aDirDebitRow['id_direct_debit']);
							$this->log('Direct Payment success', array('code' => $sResponseCode, 'id_cart' => $oCart->id, 'id_order' => $nNewOrderId));
						} else {
							$this->log('Direct Payment failed to create order', array('code' => $sResponseCode, 'id_cart' => $oCart->id, 'id_order' => $nNewOrderId));
						}
					} else {
						$this->log('Direct Payment failed', array('code' => $sResponseCode, 'id_cart' => $oCart->id));
					}
				}
				$this->log('doScheduledWalletPayment response', $aResponse);
			}
		} else {
			$this->log('Nothing to do...');
		}
		$this->log('END : runCrontab');
		die('OK');
	}
	
	/**
	 * Insert complete schedule of a direct debit payment
	 * @return boolean
	 */
	private function _populateDirectDebitTimeTable($nCartId, $nCustomerId, $contractNumber) {
		$res = true;
		// Start date is today
		$startDate = time();
		if (Configuration::get('PAYLINE_DIRDEBIT_DAY')) {
			// Start date is different from today
			if (Configuration::get('PAYLINE_DIRDEBIT_PERIODICITY') > 10 )
				$startDate = strtotime(date('Y-m-'.Configuration::get('PAYLINE_DIRDEBIT_DAY')));
		}
		for ($numEcheance = 0; $numEcheance<(int)Configuration::get('PAYLINE_DIRDEBIT_NUMBER') ; $numEcheance++) {
			$data = array(
				'id_cart_origin' => $nCartId,
				'id_customer' => $nCustomerId,
				'contract_number' => $contractNumber,
				'date_debit' => ($numEcheance == 0 ? date('Y-m-d') : $this->getDifferedDate(Configuration::get('PAYLINE_DIRDEBIT_PERIODICITY'), $startDate, (int)$numEcheance-1, 'Y-m-d')),
				'id_order' => ($numEcheance == 0 ? (int)Order::getOrderByCartId($nCartId) : NULL),
				'date_paid' => ($numEcheance == 0 ? date('Y-m-d H:i:s') : NULL),
				'paid' => ($numEcheance == 0 ? 1 : 0),
			);
			$res &= Db::getInstance()->autoExecute(_DB_PREFIX_ . 'payline_dirdebit', $data, 'INSERT', '', 0, true, true);
		}
		return $res;
	}
	
	/**
	 * Return the row to process for payment via direct debit
	 * @return array|boolean
	 */
	private function _getDirectDebitToProcess() {
		$sQuery = 'SELECT * FROM `'._DB_PREFIX_.'payline_dirdebit` WHERE id_order IS NULL AND date_debit <= "'.date('Y-m-d').'"';
		$aResult = Db::getInstance()->ExecuteS($sQuery);
		if ($aResult && is_array($aResult) && sizeof($aResult))
			return $aResult;
		return false;
	}
  
  /**
   * Return type of a contract
   */     
  private function _getPaymentMean($contractNumber){
		$sQuery = "SELECT type FROM `"._DB_PREFIX_."payline_card` WHERE contract = '$contractNumber'";
		$aResult = Db::getInstance()->ExecuteS($sQuery);
		return $aResult[0]['type'];
	} 
}