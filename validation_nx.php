<?php

require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'config', 'config.inc.php'));
require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'init.php'));
require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'payline.php'));

$oPayline = new payline();

if ($oPayline->validateNx($_REQUEST)) {
	
	echo 'Notification success';
	
} else {
	
	echo 'Notification failure. See log for details';
		
}
