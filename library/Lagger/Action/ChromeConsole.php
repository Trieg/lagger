<?php

/**
 *
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 *
 * @desc Sending messages to Google Chrome console

 	You need to install Google Chrome extension:
 	https://chrome.google.com/extensions/detail/nfhmhhlpfleoednkpnnnkolmclajemef

 */

class Lagger_Action_ChromeConsole extends Lagger_Action {

	const version = 2;
	const messagesCookiePrefix = 'phpcsl_';
	const serverVersionCookie = 'phpcsls';
	const clientVersionCookie = 'phpcslc';
	const cookiesLimit = 50;
	const cookieSizeLimit = 4000;
	const defaultNotifyTimelimit = 1;
	const messageLengthLimit = 300;

	protected static $isEnabledOnClient;
	protected static $isDisabled;
	protected static $messagesBuffer = array();
	protected static $bufferLength = 0;
	protected static $messagesSent = 0;
	protected static $cookiesSent = 0;
	protected static $index = 0;

	protected $type;
	protected $showNotifyWithTimeLimit;

	/**
	 * @param string type "error" or "debug"
	 * @param integer show notifications with time limit (in seconds)
	 */
	public function __construct($type, $showNotifyWithTimeLimit = false) {
		$this->type = $type;
		$this->showNotifyWithTimeLimit = $showNotifyWithTimeLimit === true ? self::defaultNotifyTimelimit : $showNotifyWithTimeLimit;

		if(self::$isEnabledOnClient === null) {
			$this->setEnabledOnServer();
			self::$isEnabledOnClient = $this->isEnabledOnClient();
			if(self::$isEnabledOnClient) {
				if(!ob_get_level()) {
					ob_start();
				}
				register_shutdown_function(array($this, 'flushMessagesBuffer'));
			}
		}
	}

	protected function isEnabledOnClient() {
		return isset($_COOKIE[self::clientVersionCookie]) && $_COOKIE[self::clientVersionCookie] == self::version;
	}

	protected function setEnabledOnServer() {
		if(!isset($_COOKIE[self::serverVersionCookie]) || $_COOKIE[self::serverVersionCookie] != self::version) {
			$this->setCookie(self::serverVersionCookie, self::version);
		}
	}

	protected function make() {
		if(!self::$isEnabledOnClient || self::$isDisabled) {
			return;
		}
		$data['type'] = $this->type;
		$data['subject'] = $this->eventspace->getVarValue('type');
		$data['text'] = substr($this->eventspace->getVarValue('message'), 0, self::messageLengthLimit);
		$file = $this->eventspace->getVarValue('file');
		if($file) {
			$line = $this->eventspace->getVarValue('line');
			$data['source'] = $file . ($line ? ":$line" : '');
		}
		if($this->showNotifyWithTimeLimit) {
			$data['notify'] = (int) $this->showNotifyWithTimeLimit;
		}
		$this->pushMessageToBuffer($data);
		if($this->eventspace->getVarValue('tags') == 'fatal') {
			$this->flushMessagesBuffer();
		}
	}

	protected function pushMessageToBuffer($message) {
		$encodedMessageLength = strlen(rawurlencode(json_encode($message)));
		if(self::$bufferLength + $encodedMessageLength > self::cookieSizeLimit) {
			$this->flushMessagesBuffer();
		}
		self::$messagesBuffer[] = $message;
		self::$bufferLength += $encodedMessageLength;
	}

	protected static function getNextIndex() {
		return substr(number_format(microtime(1), 3, '', ''), -6) + self::$index ++;
	}

	public function flushMessagesBuffer() {
		if(self::$messagesBuffer) {
			$this->sendMessages(self::$messagesBuffer);
			self::$bufferLength = 0;
			self::$messagesSent += count(self::$messagesBuffer);
			self::$messagesBuffer = array();
			self::$cookiesSent ++;
			if(self::$cookiesSent == self::cookiesLimit) {
				self::$isDisabled = true;
				$message = array('type' => 'error', 'subject' => 'PHP CONSOLE', 'text' => 'MESSAGES LIMIT EXCEEDED BECAUSE OF COOKIES STORAGE LIMIT. TOTAL MESSAGES SENT: ' . self::$messagesSent, 'source' => __FILE__, 'notify' => 3);
				$this->sendMessages(array($message));
			}
		}
	}

	protected function setCookie($name, $value) {
		if(headers_sent($file, $line)) {
			throw new Exception('setcookie() failed because haders are sent (' . $file . ':' . $line . '). Try to use ob_start()');
		}
		setcookie($name, $value, null, '/');
	}

	protected function sendMessages($messages) {
		$this->setCookie(self::messagesCookiePrefix . self::getNextIndex(), json_encode($messages));
	}
}