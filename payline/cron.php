<?php
/* Crontab file for direct debit payment */
require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'config', 'config.inc.php'));
require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', '..', 'init.php'));
require_once implode(DIRECTORY_SEPARATOR, array(dirname(__FILE__), 'payline.php'));
ini_Set('display_errors', true);
error_reporting(-1);

if (Tools::getIsset('secureKey') && Tools::getValue('secureKey') && Configuration::get('PAYLINE_CRON_SECURE_KEY') == Tools::getValue('secureKey')) {
	$oPayline = new payline();
	$oPayline->runCrontab();
}
die;