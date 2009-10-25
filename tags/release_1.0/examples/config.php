<?php 

define('LOGS_DIR', 'logs');

define('ERRORS_STDOUT', true);
define('ERRORS_STDOUT_TYPES', null);
define('ERRORS_STDOUT_TEMPLATE', '<b>%type%:</b> %message%<br><em>%file% [%line%]</em><br>');
define('ERRORS_STDOUT_REWRITE_PIN', null);

define('ERRORS_LOGING', true);
define('ERRORS_LOGING_TYPES', null);
define('ERRORS_LOGING_FILEPATH', LOGS_DIR.DIRECTORY_SEPARATOR.'errors_log.htm');
define('ERRORS_LOGING_LIMIT_SIZE', 500000);
define('ERRORS_LOGING_LIMIT_DAYS', 180);
define('ERRORS_LOGING_TEMPLATE', '%date% %time% <a href="http://%host%%uri%">http://%host%%uri%</a><br><b>%type%</b>: %message%<br>%file% [%line%]<hr>');

define('ERRORS_SMS', true);
define('ERRORS_SMS_TO', '79627271169,79052187474');
define('ERRORS_SMS_FROM', 'MyWebSite');
define('ERRORS_SMS_MESSAGE', 'Web site error, check log at %date% %time%');

define('ERRORS_EMAIL', false); // TODO: must be TRUE on production
define('ERRORS_EMAIL_FROM', 'lagger@mywebsite.com');
define('ERRORS_EMAIL_TO', 'myemail@gmail.com,adminemail@gmail.com');
define('ERRORS_EMAIL_SUBJECT', 'Error on my website');
define('ERRORS_EMAIL_MESSAGE', "Date: %date% %time%\nURL: http://%host%%uri%\nError(%type%): %message%\nSource: %file% [%line%]");

define('DEBUG_STDOUT', true);
define('DEBUG_STDOUT_PROFILES', false);
define('DEBUG_STDOUT_TEMPLATE', '%message%<br />');
define('DEBUG_STDOUT_REWRITE_PIN', null);

define('DEBUG_LOGING', true);
define('DEBUG_LOGING_TYPES', 'post');
define('DEBUG_LOGING_FILEPATH', LOGS_DIR.DIRECTORY_SEPARATOR.'debug_log.htm');
define('DEBUG_LOGING_LIMIT_SIZE', 500000);
define('DEBUG_LOGING_LIMIT_DAYS', 3);
define('DEBUG_LOGING_TEMPLATE', "%date% %time%<br>%message%<hr>");

// Autoload classes
define('LIB_DIR', '../library/');

function autoloadByDir($class) {
	foreach (array(LIB_DIR, './') as $dir) {
		$filePath = $dir . str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
		if (is_file($filePath)) {
			return require_once ($filePath);
		}
	}
}
spl_autoload_register('autoloadByDir');

require_once('lagger_init.php');