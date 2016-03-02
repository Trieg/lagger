## config.php ##

```
define('SKIPER_DIR', LOGS_DIR . DIRECTORY_SEPARATOR . 'skip');
define('SKIPER_EXPIRE', 60 * 60 * 24);
define('SKIPER_HASH_TEMPLATE', '{file}{line}');

define('ERRORS_STDOUT', true);
define('ERRORS_STDOUT_TAGS', null);
define('ERRORS_STDOUT_TEMPLATE', '<div><font color="red"><b>{type}:</b> {message}<br /><em>{file} [{line}]</em></font></div>');

define('ERRORS_LOGING', true);
define('ERRORS_LOGING_TAGS', 'warning,fatal');
define('ERRORS_LOGING_FILEPATH', LOGS_DIR . DIRECTORY_SEPARATOR . 'errors_log.htm');
define('ERRORS_LOGING_LIMIT_SIZE', 500000);
define('ERRORS_LOGING_LIMIT_DAYS', 180);
define('ERRORS_LOGING_TEMPLATE', '{date} {time} <a href="http://{host}{uri}">http://{host}{uri}</a><br /><b>{type}</b>: {message|htmlentities}<br />{file} [{line}]<hr />');

define('ERRORS_SMS', false); // TODO: check /library/SmsSender.php before enable it
define('ERRORS_SMS_TAGS', 'warning,fatal');
define('ERRORS_SMS_TO', '79627271169,79218550471');
define('ERRORS_SMS_FROM', 'MyWebSite');
define('ERRORS_SMS_MESSAGE', 'Web site error, check log at {date} {time}');

define('ERRORS_EMAIL', true); // TODO: must be TRUE on production server
define('ERRORS_EMAIL_TAGS', 'warning,fatal');
define('ERRORS_EMAIL_FROM', 'Lagger <lagger@mywebsite.com>');
define('ERRORS_EMAIL_TO', 'Jack Johnson <jack_admin@gmail.com>, mike_developer@gmail.com');
define('ERRORS_EMAIL_SUBJECT', '{type} error in my website');
define('ERRORS_EMAIL_MESSAGE', "Date: {date} {time}\nURL: http://{host}{uri}\nError({type}): {message}\nSource: {file} [{line}]\n\nPOST:\n{post}\n\nSESSION:\n{session}");

define('DEBUG_STDOUT', true);
define('DEBUG_STDOUT_TAGS', 'test,high');
define('DEBUG_STDOUT_TEMPLATE', '<div><font color="green">{message|htmlentities}</font></div>');

define('DEBUG_LOGING', true);
define('DEBUG_LOGING_TAGS', 'sql');
define('DEBUG_LOGING_FILEPATH', LOGS_DIR . DIRECTORY_SEPARATOR . 'debug_sql_log.csv');
define('DEBUG_LOGING_LIMIT_SIZE', 500000);
define('DEBUG_LOGING_LIMIT_DAYS', 7);
define('DEBUG_LOGING_TEMPLATE', "{date} {time};{process_id|csv};{microtime|csv};{tags|csv};{message|trim|csv}\n");
```

## lagger\_init.php ##

```
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

$emailAction = new Lagger_Action_Mail(ERRORS_EMAIL_FROM, ERRORS_EMAIL_TO, ERRORS_EMAIL_SUBJECT, ERRORS_EMAIL_MESSAGE);
$emailAction->setSkiper($daylySkiper, 'errors_email');

Lagger_Handler::addInternalErrorAction($emailAction);

/**************************************************************
   DEBUG HANDLER
 **************************************************************/

$debug = new Lagger_Handler_Debug($laggerES);

function toDebug($message, $tags = null) {
  if (isset($GLOBALS['debug'])) {
    $GLOBALS['debug']->handle($message, $tags);
  }
}

if (DEBUG_STDOUT) {
  // Allows to rewrite DEBUG_STDOUT_TAGS. Try $_GET['__debug'] = 'high' or $_GET['__debug'] = ''
  $debugTagger = new Lagger_Tagger('__debug'); 

  $debug->addAction(new Lagger_Action_Print(DEBUG_STDOUT_TEMPLATE), DEBUG_STDOUT_TAGS, $debugTagger);
  $debug->addAction(new Lagger_Action_FirePhp('{message}', '{tags}', FirePHP::INFO), DEBUG_STDOUT_TAGS, $debugTagger);
}
if (DEBUG_LOGING) {
  $debug->addAction(new Lagger_Action_FileLog(DEBUG_LOGING_TEMPLATE, DEBUG_LOGING_FILEPATH, DEBUG_LOGING_LIMIT_SIZE, DEBUG_LOGING_LIMIT_DAYS), DEBUG_LOGING_TAGS);
}

// Just for fun in windows servers it will speak the text :)
if(stristr(PHP_OS, 'win') !== false) {
  $debug->addAction(new Lagger_Action_WinSpeak('{message}', 100), 'speak');
}

/**************************************************************
   ERRORS AND EXCEPTIONS HANDLERS
 **************************************************************/

$errors = new Lagger_Handler_Errors($laggerES);
$exceptions = new Lagger_Handler_Exceptions($laggerES);

if (ERRORS_STDOUT) {
  $printAction = new Lagger_Action_Print(ERRORS_STDOUT_TEMPLATE, false);
  $errors->addAction($printAction);
  $exceptions->addAction($printAction);
  
  $errorsFirePhpAction = new Lagger_Action_FirePhp('{message} {file} [{line}]', '{type}', FirePHP::ERROR);
  $errors->addAction($errorsFirePhpAction);
  $exceptions->addAction($errorsFirePhpAction);
}

if (ERRORS_LOGING) {
  $logAction = new Lagger_Action_FileLog(ERRORS_LOGING_TEMPLATE, ERRORS_LOGING_FILEPATH, ERRORS_LOGING_LIMIT_SIZE, ERRORS_LOGING_LIMIT_DAYS);
  $errors->addAction($logAction, ERRORS_LOGING_TAGS);
  $exceptions->addAction($logAction, ERRORS_LOGING_TAGS);
}

if (ERRORS_SMS) {
  $smsAction = new Lagger_Action_Sms(ERRORS_SMS_FROM, ERRORS_SMS_TO, ERRORS_SMS_MESSAGE, true);
  $smsAction->setSkiper($daylySkiper, 'errors_sms');
  $errors->addAction($smsAction, ERRORS_SMS_TAGS);
  $exceptions->addAction($smsAction, ERRORS_SMS_TAGS);
}

if (ERRORS_EMAIL) {
  $errors->addAction($emailAction, ERRORS_EMAIL_TAGS);
  $exceptions->addAction($emailAction, ERRORS_EMAIL_TAGS);
}
```

## index.php ##
```
require_once ('config.php');
require_once ('lagger_init.php');

echo '<h3>Simple debug messages (default tag is "message"). <br />Tags output is configured in: define("DEBUG_STDOUT_TAGS", "test,high")</h3>'; 
toDebug('Debug message with default tag "message"'); // will be not printed
toDebug('Debug message with tag "high"', 'high'); // will be not printed
toDebug('Debug message with tags "high,test"', 'high,test'); // will be printed
toDebug('Debug message with tags "high,test,database"', 'high,test,database'); // will be printed
toDebug('Oops, lagger did it again :)', 'speak'); // will be not printed, but you will hear it (if PHP installed on Windows)

echo '<br><b>You can override tags for debug output by __debug parameter in GET:</b></br>
<a href="?__debug=">Show all</a><br />
<a href="?__debug=high,test,database">Show only tags "high,test,database"</a><br />
<a href="?__reset">Reset to config settings</a><br />'; 

// Debug by tag 'sql'. Check output in '\examples\logs\debug_sql_log.csv', open with Microsoft Excel or Open office 
$sql = 'SELECT * FROM users';
toDebug($sql, 'sql,start');
usleep(300); // exec sql query
toDebug($sql, 'sql,finish');

echo '<h3>Some PHP errors</h3>'; 
$blahamuha = $some['unkownVar'];
file_get_contents('blahamuha.txt');

echo '<h3>If you catch all exceptions to show user error page, so you should do it like this</h3>'; 
try {
  throw new Exception('There is some catched exception');
}
catch (Exception $e) {
  $exceptions->handle($e);
  echo 'Hi, user! <br>There is some problem with our server, check it up tomorrow or better in next summer';
}

echo '<h3>You can set Lagger to generate Exceptions on PHP errors</h3>';
$errors->addAction(new Lagger_Action_Exception(), 'notice,warning,fatal');
try {
  file_get_contents('blahamuha.txt'); // some E_WARNING PHP error
}
catch (ErrorException $e) {
  $exceptions->handle($e);
}

echo '<h3>But otherwise not catched exceptions will break the script</h3>';
file_get_contents('blahamuha_cikatuha.txt'); // some E_WARNING PHP error
echo 'So, this text will be never printed';
```