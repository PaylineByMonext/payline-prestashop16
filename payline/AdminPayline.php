<?php

class AdminPayline extends AdminController {

	protected $oPayline;

	public function processBulkCaptureOrder(){
		
		if($this->boxes){

			$orderToBeProcessed = 0;
			$orderProcessedSuccess = 0;
			$errors = array();
			foreach($this->boxes as $order_id)
			{
				$orderToBeProcessed ++;
				if($this->oPayline->_canCapture((int)$order_id))
				{

					$response = $this->oPayline->_doTotalCapture((int)$order_id);
					if(isset($response) && $response['result']['code'] == '00000')
						$orderProcessedSuccess++;
					else
						$errors[] = $this->l("this order can't be captured:").$order_id;
				}
				else
					$errors[] = $this->l("This order can't be captured") . ' : '.$order_id;
			}

		}
		if(!sizeof($errors))
			Tools::redirectAdmin(self::$currentIndex.'&conf=5&token='.$this->token);
		else
			$this->errors[] = $orderProcessedSuccess.' '.$this->l('of').' '.$orderToBeProcessed.' '.$this->l(' orders have been successfully captured');
		$this->errors = array_merge($this->errors,$errors);

	}

	public function processBulkEnableSelection(){

		if($this->boxes){

			$orderToBeProcessed = 0;
			$orderProcessedSuccess = 0;
			$errors = array();

			foreach($this->boxes as $order_id){

				$orderToBeProcessed++;

				if($this->oPayline->_canRefund((int)$order_id)){

					$response = $this->oPayline->_doTotalRefund((int)$order_id);

					if(isset($response) && $response['result']['code'] == '00000')
						$orderProcessedSuccess++;
					else
						$errors[] = $this->l("this order can't be refunded:").$order_id;
				}
				else
					$errors[] = $this->l("This order can't be refunded:").$order_id;
			}
		}

		if(!sizeof($errors))
			Tools::redirectAdmin(self::$currentIndex.'&conf=5&token='.$this->token);
		else
			$this->errors[] = $orderProcessedSuccess.' '.$this->l('of').' '.$orderToBeProcessed.' '.$this->l('orders have been successfully refund');
		$this->errors = array_merge($this->errors,$errors);


	}
	public function initToolbar(){}

	public function __construct()
	{
		$this->actions_available = array();
		$this->actions = array();
		
		$this->bootstrap = true;
		$this->table = 'order';
		$this->className = 'Order';
		$this->lang = false;
		$this->addRowAction('view');
		$this->explicitSelect = true;
		$this->allow_export = true;
		$this->deleted = false;
		$this->context = Context::getContext();
		
		if (Tools::getIsset('vieworder') && Tools::getIsset('id_order') && Tools::getValue('id_order')) 
			Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders').'&id_order='.(int)Tools::getValue('id_order').'&vieworder');

		$this->bulk_actions=array(
			'CaptureOrder' => array('text' => $this->l('Capture')),
			'EnableSelection' => array('text' => $this->l('Refund'))
		);

		$this->_select = '
			a.id_order AS id_pdf,
			a.id_currency,
			CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
			osl.`name` AS `osname`,
			os.`color`,
			IF((SELECT COUNT(so.id_order) FROM `'._DB_PREFIX_.'orders` so WHERE so.id_customer = a.id_customer) > 1, 0, 1) as new,
			(SELECT COUNT(od.`id_order`) FROM `'._DB_PREFIX_.'order_detail` od WHERE od.`id_order` = a.`id_order` GROUP BY `id_order`) AS product_number';

		$this->_join = 'LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`)
		INNER JOIN `'._DB_PREFIX_.'address` address ON address.id_address = a.id_address_delivery
		INNER JOIN `'._DB_PREFIX_.'country` country ON address.id_country = country.id_country
		INNER JOIN `'._DB_PREFIX_.'country_lang` country_lang ON (country.`id_country` = country_lang.`id_country` AND country_lang.`id_lang` = '.(int)$this->context->language->id.')
		LEFT JOIN `'._DB_PREFIX_.'order_history` oh ON (oh.`id_order` = a.`id_order`)
		LEFT JOIN `'._DB_PREFIX_.'order_state` os ON (os.`id_order_state` = oh.`id_order_state`)
		LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = '.(int)$this->context->language->id.')';

		$this->_where = 'AND oh.`id_order_history` = (SELECT MAX(`id_order_history`) FROM `'._DB_PREFIX_.'order_history` moh WHERE moh.`id_order` = a.`id_order` AND a.`payment` = \'Payline\' GROUP BY moh.`id_order`)';
		
		$statuses = OrderState::getOrderStates((int)$this->context->language->id);
		foreach ($statuses as $status)
			$this->statuses_array[$status['id_order_state']] = $status['name'];
			
		$this->fields_list = array(
			'id_order' => array(
				'title' => $this->l('ID'),
				'align' => 'text-center',
				'class' => 'fixed-width-xs'
			),
			'reference' => array(
				'title' => $this->l('Reference')
			),
			'new' => array(
				'title' => $this->l('New client'),
				'align' => 'text-center',
				'type' => 'bool',
				'tmpTableFilter' => true,
				'orderby' => false
			),
			'customer' => array(
				'title' => $this->l('Customer'),
				'havingFilter' => true,
			),
		);
		
		if (Configuration::get('PS_B2B_ENABLE'))
		{
			$this->fields_list = array_merge($this->fields_list, array(
				'company' => array(
					'title' => $this->l('Company'),
					'filter_key' => 'c!company'
				),
			));
		}
		
		$this->fields_list = array_merge($this->fields_list, array(
			'total_paid_tax_incl' => array(
				'title' => $this->l('Total'),
				'align' => 'text-right',
				'type' => 'price',
				'currency' => true,
				'callback' => 'setOrderCurrency',
				'badge_success' => true
			),
			'payment' => array(
				'title' => $this->l('Payment')
			),
			'osname' => array(
				'title' => $this->l('Status'),
				'type' => 'select',
				'color' => 'color',
				'list' => $this->statuses_array,
				'filter_key' => 'os!id_order_state',
				'filter_type' => 'int',
				'order_key' => 'osname'
			),
			'date_add' => array(
				'title' => $this->l('Date'),
				'align' => 'text-right',
				'type' => 'datetime',
				'filter_key' => 'a!date_add'
			),
			'id_pdf' => array(
				'title' => $this->l('PDF'),
				'align' => 'text-center',
				'callback' => 'printPDFIcons',
				'orderby' => false,
				'search' => false,
				'remove_onclick' => true
			)
		));

		$this->shopLinkType = 'shop';
		$this->shopShareDatas = Shop::SHARE_ORDER;

		$this->bulk_actions = array(
			'CaptureOrder' => array('text' => $this->l('Capture')),
			'EnableSelection' => array('text' => $this->l('Refund'))
		);
		
		require_once dirname(__FILE__) . '/payline.php';
		$this->oPayline = new Payline();
		
		parent::__construct();
	}
	
	public static function setOrderCurrency($echo, $tr) {
		$order = new Order($tr['id_order']);
		return Tools::displayPrice($echo, (int)$order->id_currency);
	}
	
	public function printPDFIcons($id_order, $tr)
	{
		$order = new Order($id_order);
		$order_state = $order->getCurrentOrderState();
		if (!Validate::isLoadedObject($order_state) || !Validate::isLoadedObject($order))
			return '';

		$this->context->smarty->assign(array(
				'order' => $order,
				'order_state' => $order_state,
				'tr' => $tr
		));		$this->context->smarty->assign(array(
				'order' => $order,
				'order_state' => $order_state,
				'tr' => $tr
		));


		return $this->createTemplate('../../../../modules/payline/views/templates/admin/_print_pdf_icon.tpl')->fetch();
	}

}
