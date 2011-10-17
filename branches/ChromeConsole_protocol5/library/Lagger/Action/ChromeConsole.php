<?php

/**
 *
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 *
 * @desc Sending messages to Google Chrome console
 *
 * You need to install Google Chrome extension PHP Console:
 * https://chrome.google.com/webstore/detail/jafmfknfnkoekkdocjiaipcnmkklaajd
 *
 * All class properties and methods are static because it's required to let
 * them work on script shutdown when FATAL error occurs.
 *
 */
class Lagger_Action_ChromeConsole extends Lagger_Action {

	const serverProtocol = 5;
	const cookieSizeLimit = 4000;
	const messageLengthLimit = 300;
	const cookiesLimit = 50;
	const clientProtocolCookie = 'phpcslc';
	const serverProtocolCookie = 'phpcsls';
	const messagesCookiePrefix = 'phpcsl_';
	const clientPasswordCookie = 'phpcslp';
	const serverAuthCookie = 'phpcsla';

	protected static $isInitialized = false;
	protected static $isEnabled = true;
	protected static $requestHost;
	protected static $requestUrl;
	protected static $redirectUrl;
	protected static $password;
	protected static $processUid;
	protected static $messagesBuffer = array();
	protected static $cookiesSent = 0;
	protected static $index = 0;

	protected $stripBaseSourcePath = 0;

	public function __construct($stripBaseSourcePath = null, $password = null) {
		if(!self::$isInitialized) {
			self::$isInitialized = true;
			self::$processUid = mt_rand() . mt_rand();
			self::$requestHost = (stripos($_SERVER['SERVER_PROTOCOL'], 'https') === false ? 'http://' : 'https://') . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
			self::$requestUrl = self::$requestHost . $_SERVER['REQUEST_URI'];
			if(strlen(self::$requestUrl) > 150) {
				self::$requestUrl = substr(self::$requestUrl, 0, 147) . '...';
			}
			self::sendServerIsActive();
			self::$isEnabled = self::isEnabledOnClient() && self::isValidPassword($password);
			if(self::$isEnabled) {
				register_shutdown_function(array(get_class($this), 'flushMessagesBuffer'));
				ob_start();
			}
		}
		if($stripBaseSourcePath) {
			$this->stripBaseSourcePath = realpath($stripBaseSourcePath);
		}
	}

	protected static function sendServerIsActive() {
		if(!isset($_COOKIE[self::serverProtocolCookie]) || $_COOKIE[self::serverProtocolCookie] != self::serverProtocol) {
			self::setCookie(self::serverProtocolCookie, self::serverProtocol);
		}
	}

	protected static function isEnabledOnClient() {
		return isset($_COOKIE[self::clientProtocolCookie]) && $_COOKIE[self::clientProtocolCookie] == self::serverProtocol;
	}

	protected static function isValidPassword($password) {
		$isValidPassword = true;
		if($password) {
			$isValidPassword = isset($_COOKIE[self::clientPasswordCookie]) && $_COOKIE[self::clientPasswordCookie] === $password;
			self::setCookie(self::serverAuthCookie, $isValidPassword ? 'ok' : 'failed');
		}
		elseif(isset($_COOKIE[self::serverAuthCookie])) {
			self::setCookie(self::serverAuthCookie, null, true);
		}
		return $isValidPassword;
	}

	protected function make() {
		if(self::$isEnabled) {
			$messageData = $this->eventspace->getVarValue('message', false);
			self::prepareVarToSerialize($messageData);
			$message = array(
				'tags' => $this->eventspace->getVarValue('tags'),
				'subject' => $this->eventspace->getVarValue('type'),
				'text' => $messageData
			);

			$file = $this->eventspace->getVarValue('file');
			if($file) {
				if($this->stripBaseSourcePath) {
					$file = preg_replace('!^' . preg_quote($this->stripBaseSourcePath, '!') . '!', '', $file);
				}
				$line = $this->eventspace->getVarValue('line');
				$message['source'] = $file . ($line ? ':' . $line : '');
			}

			$trace = $this->eventspace->getVarValue('trace');
			if($trace) {
				if($this->stripBaseSourcePath) {
					$trace = preg_replace('!(#\d+ )' . preg_quote($this->stripBaseSourcePath, '!') . '!s', '\\1', $trace);
				}
				$message['trace'] = explode("\n", $trace);
			}

			self::pushMessageToBuffer($message);
			/*
			 * TODO: test if it'r really requried
			if(strpos($this->eventspace->getVarValue('tags'), ',fatal')) {
				self::flushMessagesBuffer();
			}*/
		}
	}

	protected static function pushMessageToBuffer($message) {
		self::$messagesBuffer[] = $message;
	}

	protected static function getNextCookieIndex() {
		return substr(number_format(microtime(1), 3, '', ''), -6) + self::$index++;
	}

	public static function flushMessagesBuffer() {
		if(self::$messagesBuffer && self::$isEnabled) {
			$cookies = array();
			$cookieMessages = array();
			$cookieMessagesSize = 0;
			foreach(self::$messagesBuffer as $message) {
				$encodedMessageLength = strlen(rawurlencode(json_encode($message))) + 30;
				if($encodedMessageLength > self::cookieSizeLimit) {
					$message['text'] = '[Message content is too big and can\'t be sent]';
					$encodedMessageLength = strlen(rawurlencode(json_encode($message))) + 30;
				}
				if($cookieMessagesSize + $encodedMessageLength > self::cookieSizeLimit) {
					$cookies[] = $cookieMessages;
					$cookieMessages = array();
					$cookieMessagesSize = 0;
				}
				$cookieMessages[] = $message;
				$cookieMessagesSize += $encodedMessageLength;
			}
			self::$redirectUrl = self::getRedirectUrl();
			if(strlen(self::$redirectUrl) > 150) {
				self::$redirectUrl = substr(self::$redirectUrl, 0, 147) . '...';
			}
			$cookies[] = $cookieMessages;
			foreach($cookies as $cookieMessages) {
				if(self::$cookiesSent >= self::cookiesLimit) {
					self::$isEnabled = false;
					self::sendCookiesLimitExceeded();
					break;
				}
				self::sendMessagesCookie($cookieMessages);
				self::$cookiesSent++;
			}
			self::$messagesBuffer = array();
		}
	}

	protected static function sendCookiesLimitExceeded() {
		self::sendMessagesCookie(array(array(
				'type' => 'ahtung',
				'subject' => 'PHP CONSOLE',
				'text' => 'Cookies limit is exceeded. Try to increase limit or add messages filter.',
				'source' => __FILE__ . ':' . __LINE__
			)));
	}

	protected static function setCookie($name, $value, $isTemporary = false) {
		if(headers_sent($file, $line)) {
			die(__METHOD__ . ' error: setcookie() failed because headers was sent in ' . $file . ':' . $line . '. Try to use ob_start()');
		}
		setcookie($name, $value, null, '/');
		if($isTemporary) {
			setcookie($name, false, null, '/');
		}
	}

	protected static function sendMessagesCookie(array $messages) {
		$cookieId = self::getNextCookieIndex();
		$data = array(
			'PID' => self::$processUid,
			'cookieId' => $cookieId,
			'requestUrl' => self::$requestUrl,
			'redirectUrl' => self::$redirectUrl,
			'messages' => $messages
		);
		$cookieData = defined('JSON_UNESCAPED_UNICODE') ? json_encode($data, JSON_UNESCAPED_UNICODE) : json_encode($data);
		$cookieData = str_replace('\u0000*\u0000', '', $cookieData); // required after $array = (array) $object;
		self::setCookie(self::messagesCookiePrefix . $cookieId, $cookieData, true);
	}

	protected static function getRedirectUrl() {
		$headers = headers_list();
		if(preg_match('/^Location\:\s*((http)?([\\/\\\\])?(.+))/i', end($headers), $matches)) {
			return ($matches[2] || $matches[3] ? '' : $_SERVER['REQUEST_URI']) . $matches[1];
		}
	}

	public static function prepareVarToSerialize(&$var, $key = null) {
		static $objectsHashes;
		if($key === null) {
			$objectsHashes = array();
		}
		if(is_array($var)) {
			array_walk_recursive($var, array(__CLASS__, 'prepareVarToSerialize'));
		}
		elseif(is_object($var)) {
			$hash = spl_object_hash($var);
			if(in_array($hash, $objectsHashes)) {
				$var = 'RECURSION: ' . get_class($var);
			}
			else {
				$objectsHashes[] = $hash;
				$var = (array)$var;
				array_walk_recursive($var, array(__CLASS__, 'prepareVarToSerialize'));
			}
		}
		elseif(is_scalar($var) && strlen($var) > self::messageLengthLimit) {
			$var = substr($var, 0, self::messageLengthLimit) . '...';
		}
	}

	public function __destruct() {
		self::flushMessagesBuffer();
	}
}
