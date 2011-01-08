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

	const clientProtocolCookie = 'phpcslc';
	const serverProtocolCookie = 'phpcsls';
	const serverProtocol = 3;
	const messagesCookiePrefix = 'phpcsl_';
	const cookiesLimit = 50;
	const cookieSizeLimit = 4000;
	const defaultNotifyTimelimit = 1;
	const messageLengthLimit = 1000;

	protected static $isEnabledOnClient;
	protected static $isDisabled;
	protected static $messagesBuffer = array();
	protected static $bufferLength = 0;
	protected static $messagesSent = 0;
	protected static $cookiesSent = 0;
	protected static $index = 0;

	public function __construct() {
		if(self::$isEnabledOnClient === null) {
			self::setEnabledOnServer();
			self::$isEnabledOnClient = self::isEnabledOnClient();
			if(self::$isEnabledOnClient) {
				ob_start();
			}
		}
	}

	protected static function isEnabledOnClient() {
		return isset($_COOKIE[self::clientProtocolCookie]) && $_COOKIE[self::clientProtocolCookie] == self::serverProtocol;
	}

	protected static function setEnabledOnServer() {
		if(!isset($_COOKIE[self::serverProtocolCookie]) || $_COOKIE[self::serverProtocolCookie] != self::serverProtocol) {
			self::setCookie(self::serverProtocolCookie, self::serverProtocol);
		}
	}

	protected function make() {
		if(!self::$isEnabledOnClient || self::$isDisabled) {
			return;
		}
		$message['type'] = strpos($this->eventspace->getVarValue('tags'), 'error,') !== false ? 'error' : 'debug';
		$message['subject'] = $this->eventspace->getVarValue('type');
		$message['text'] = substr($this->eventspace->getVarValue('message'), 0, self::messageLengthLimit);
		$file = $this->eventspace->getVarValue('file');
		if($file) {
			$line = $this->eventspace->getVarValue('line');
			$message['source'] = $file . ($line ? ":$line" : '');
		}
		self::pushMessageToBuffer($message);
		if(strpos($this->eventspace->getVarValue('tags'), ',fatal')) {
			self::flushMessagesBuffer();
		}
	}

	protected static function pushMessageToBuffer($message) {
		$encodedMessageLength = strlen(rawurlencode(json_encode($message)));
		if(self::$bufferLength + $encodedMessageLength > self::cookieSizeLimit) {
			self::flushMessagesBuffer();
		}
		self::$messagesBuffer[] = $message;
		self::$bufferLength += $encodedMessageLength;
	}

	protected static function getNextIndex() {
		return substr(number_format(microtime(1), 3, '', ''), -6) + self::$index ++;
	}

	public static function flushMessagesBuffer() {
		if(self::$messagesBuffer) {
			self::sendMessages(self::$messagesBuffer);
			self::$bufferLength = 0;
			self::$messagesSent += count(self::$messagesBuffer);
			self::$messagesBuffer = array();
			self::$cookiesSent ++;
			if(self::$cookiesSent == self::cookiesLimit) {
				self::$isDisabled = true;
				$message = array('type' => 'error', 'subject' => 'PHP CONSOLE', 'text' => 'MESSAGES LIMIT EXCEEDED BECAUSE OF COOKIES STORAGE LIMIT. TOTAL MESSAGES SENT: ' . self::$messagesSent, 'source' => __FILE__, 'notify' => 3);
				self::sendMessages(array($message));
			}
		}
	}

	protected static function setCookie($name, $value) {
		if(headers_sent($file, $line)) {
			throw new Exception('setcookie() failed because haders are sent (' . $file . ':' . $line . '). Try to use ob_start()');
		}
		setcookie($name, $value, null, '/');
	}

	protected static function sendMessages($messages) {
		self::setCookie(self::messagesCookiePrefix . self::getNextIndex(), json_encode($messages));
	}

	public function __destruct() {
		self::flushMessagesBuffer();
	}
}