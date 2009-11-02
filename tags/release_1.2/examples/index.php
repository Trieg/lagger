<?php

require_once ('config.php');
require_once ('lagger_init.php');

echo '<h3>Simple debug message (by default tag is "message"). Output is configured in: define("DEBUG_STDOUT_TAGS", "test,high")</h3>'; 
toDebug('Debug message with default tag "message"'); // will be not printed
toDebug('Debug message with tag "high"', 'high'); // will be not printed
toDebug('Debug message with tags "high,test"', 'high,test'); // will be printed
toDebug('Debug message with tags "high,test,database"', 'high,test,database'); // will be printed


// Debug by tag 'sql'. Check output in '\examples\logs\debug_sql_log.csv', open with Microsoft Excel or Open office 
$sql = 'SELECT * FROM users';
toDebug('Sql started: ' . $sql, 'sql,start');usleep(300); // exec sql query
toDebug('Sql finished: ' . $sql, 'sql,finish');


echo '<h3>Some E_NOTICE php error</h3>'; 
$blahamuha = $some['unkownVar'];


echo '<h3>Some E_WARNING php error</h3>'; 
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
$errors->addAction(new Lagger_ActionException(), 'notice,warning,fatal');
try {
	file_get_contents('blahamuha.txt'); // some E_WARNING php error
}
catch (ErrorException $e) {
	$exceptions->handle($e);
}

echo '<h3>But otherwise not catched exceptions will break the script</h3>';
file_get_contents('blahamuha_cikatuha.txt'); // some E_WARNING php error
echo 'So, this text will be never printed';