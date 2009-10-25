<?php

/**************************************************************
	 REGISTER EVENTSPACE VARS
 **************************************************************/

$laggerES = new Lagger_Eventspace();
$laggerES->registerReference('host', $_SERVER['HTTP_HOST']);
$laggerES->registerReference('uri', $_SERVER['REQUEST_URI']);
$laggerES->registerReference('post', $_POST);
$laggerES->registerReference('session', $_SESSION); // Session must be already started!
$laggerES->registerCallback('date', 'date', array('Y-m-d'));
$laggerES->registerCallback('time', 'date', array('H:i:s'));
$laggerES->registerCallback('microtime', 'microtime', array(true));
$laggerES->registerVar('session_id', session_id());
$laggerES->registerVar('process_id', substr(md5(mt_rand()), 25));

/**************************************************************
	 REGISTER EVENTSPACE MODIFIERS
 **************************************************************/

function varToStringLine($value) {
	return str_replace(array("\r\n", "\r", "\n"), ' ', is_scalar($value) ? $value : var_export($value, 1));
}
$laggerES->registerModifier('line', 'varToStringLine');

function quoteCSV($string) {
	return varToStringLine(str_replace(';', '\\;', $string));
}
$laggerES->registerModifier('csv', 'quoteCSV');

/**************************************************************
	 SKIPER
 **************************************************************/

$daylySkiper = new Lagger_Skiper($laggerES, SKIPER_HASH_TEMPLATE, SKIPER_EXPIRE, new Lagger_ExpireList(SKIPER_DIR, '.dayly_skiper'));

/**************************************************************
	 LAGGER INTERNAL ERRORS AND EXCEPTIONS HANDLING
 **************************************************************/

$emailAction = new Lagger_ActionMail(ERRORS_EMAIL_FROM, ERRORS_EMAIL_TO, ERRORS_EMAIL_SUBJECT, ERRORS_EMAIL_MESSAGE);
$emailAction->setSkiper($daylySkiper, 'errors_email');

Lagger_Handler::addInternalErrorAction($emailAction);

/**************************************************************
	 DEBUG HANDLER
 **************************************************************/

$debug = new Lagger_HandlerDebug($laggerES);

function toDebug($message, $tags = null) {
	if (isset($GLOBALS['debug'])) {
		$GLOBALS['debug']->handle($message, $tags);
	}
}

if (DEBUG_STDOUT) {
	$debug->addAction(new Lagger_ActionPrint(DEBUG_STDOUT_TEMPLATE), DEBUG_STDOUT_TAGS, '__debug', DEBUG_STDOUT_REWRITE_PIN);
}
if (DEBUG_LOGING) {
	$debug->addAction(new Lagger_ActionFileLog(DEBUG_LOGING_TEMPLATE, DEBUG_LOGING_FILEPATH, DEBUG_LOGING_LIMIT_SIZE, DEBUG_LOGING_LIMIT_DAYS), DEBUG_LOGING_TAGS, '__deblog', DEBUG_STDOUT_REWRITE_PIN);
}

/**************************************************************
	 ERRORS AND EXCEPTIONS HANDLERS
 **************************************************************/

$errors = new Lagger_HandlerErrors($laggerES);
$exceptions = new Lagger_HandlerExceptions($laggerES);

if (ERRORS_STDOUT) {
	$printAction = new Lagger_ActionPrint(ERRORS_STDOUT_TEMPLATE, false);
	$errors->addAction($printAction, ERRORS_STDOUT_TAGS, '__errors', ERRORS_STDOUT_REWRITE_PIN);
	$exceptions->addAction($printAction, ERRORS_STDOUT_TAGS, '__errors', ERRORS_STDOUT_REWRITE_PIN);
}

if (ERRORS_LOGING) {
	$logAction = new Lagger_ActionFileLog(ERRORS_LOGING_TEMPLATE, ERRORS_LOGING_FILEPATH, ERRORS_LOGING_LIMIT_SIZE, ERRORS_LOGING_LIMIT_DAYS);
	$errors->addAction($logAction, ERRORS_LOGING_TAGS);
	$exceptions->addAction($logAction, ERRORS_LOGING_TAGS);
}

if (ERRORS_SMS) {
	$smsAction = new Lagger_ActionSms(ERRORS_SMS_FROM, ERRORS_SMS_TO, ERRORS_SMS_MESSAGE, true);
	$smsAction->setSkiper($daylySkiper, 'errors_sms');
	$errors->addAction($smsAction, ERRORS_SMS_TAGS);
	$exceptions->addAction($smsAction, ERRORS_SMS_TAGS);
}

if (ERRORS_EMAIL) {
	$errors->addAction($emailAction, ERRORS_EMAIL_TAGS);
	$exceptions->addAction($emailAction, ERRORS_EMAIL_TAGS);
}