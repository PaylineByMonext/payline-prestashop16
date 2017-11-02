<?php

require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'config', 'config.inc.php'));

require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'init.php'));

require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'lib', 'include.php'));

require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'payline.php'));

$oPayline = new payline();

if ($oPayline->active) {

	if(isset($_GET['cardInd']) && $_GET['cardInd'] != '' && isset($_GET['type'])){

		$oPayline->walletPayment($_GET['cardInd'], $_GET['type']);

	} elseif(isset($_POST['directmode']) && $_POST['directmode'] == 'direct'){

		$oPayline->directPayment($_POST);

	} else{

		$oPayline->redirectToPaymentPage($_POST);

	} // if

} // if