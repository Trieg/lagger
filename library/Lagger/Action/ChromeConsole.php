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
	const defaultNotifyTimelimit = 1;
	const bufferSizeLimit = 4000;
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
	 * @param string template
	 * @param string type "error" or "debug"
	 */
	public function __construct($type, $showNotifyWithTimeLimit = false) {
		$this->type = $type;
		$this->showNotifyWithTimeLimit = $showNotifyWithTimeLimit === true ? self::defaultNotifyTimelimit : $showNotifyWithTimeLimit;
		
		if(self::$isEnabledOnClient === null) {
			$this->setEnabledOnServer();
			self::$isEnabledOnClient = $this->isEnabledOnClient();
			if(self::$isEnabledOnClient) {
				ob_start();
			}
		}
	}

	protected function isEnabledOnClient() {
		return isset($_COOKIE[self::clientVersionCookie]) && $_COOKIE[self::clientVersionCookie] == self::version;
	}

	protected function setEnabledOnServer() {
		if(!isset($_COOKIE[self::serverVersionCookie]) || $_COOKIE[self::serverVersionCookie] != self::version) {
			setcookie(self::serverVersionCookie, self::version, null, '/');
		}
	}

	protected function make() {
		if(headers_sent(&$file, &$line)) {
			throw new Exception('setcookie failed because haders are sent (' . $file . ':' . $line . ')');
		}
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
	}

	protected function pushMessageToBuffer($message) {
		$encodedMessageLength = strlen(rawurlencode(json_encode($message)));
		if(self::$bufferLength + $encodedMessageLength > self::bufferSizeLimit) {
			$this->flushMessagesBuffer();
		}
		self::$messagesBuffer[] = $message;
		self::$bufferLength += $encodedMessageLength;
	}

	protected static function getNextIndex() {
		return substr(number_format(microtime(1), 3, '', ''), -6) + self::$index ++;
	}

	protected function flushMessagesBuffer() {
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

	protected function sendMessages($messages) {
		setcookie(self::messagesCookiePrefix . self::getNextIndex(), json_encode($messages), null, '/');
	}

	public function __destruct() {
		$this->flushMessagesBuffer();
	}
}