<?php

class SmsSender {

	protected $user;

	protected $password;

	protected $testMode;

	protected $logFilepath;

	public function __construct($user = SMS_GATEWAY_LOGIN, $password = SMS_GATEWAY_PASSWORD, $logFilepath = SMS_LOG_FILEPATH, $testMode = SMS_TEST_MODE) {
		$this->user = $user;
		$this->password = $password;
		$this->logFilepath = $logFilepath;
		$this->testMode = $testMode;
	}

	protected function log(array $logData) {
		$f = fopen($this->logFilepath, 'a');
		fputs($f, print_r($logData, true) . "\n\n");
		fclose($f);
	}

	public function send($from, $to, $message, $translit=false) {
	}
}