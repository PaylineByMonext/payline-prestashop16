<?php

require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'config', 'config.inc.php'));
require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'init.php'));
require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'payline.php'));
ini_Set('display_errors', true);
error_reporting(-1);
$oPayline = new payline();
		
$bResult = false;

if ($oPayline->active){
	
	$sType = $oPayline->getType($_REQUEST);
	
	switch ($sType){
		
		case 'REC':
			
			$bResult = $oPayline->validateSubscription($_REQUEST);
			
		break;
		
		case 'NX':
			
			$bResult = $oPayline->validateNx($_REQUEST);
			
		break;
			
		default:
			
			$bResult = $oPayline->validateWebPayment($_REQUEST);
			
		break;
		
	} // switch

} // if

echo ($bResult ? 'Notification success' : 'Notification failure. See log for details') . ' ' . $sType;