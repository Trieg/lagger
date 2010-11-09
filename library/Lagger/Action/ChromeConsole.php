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
	
	const cookieName = 'phpcsl';
	const cookieLifetime = 1800;
	const messageLengthLimit = 300;
	const defaultNotifyTimelimit = 2;
	
	protected $type;
	protected $showNotifyWithTimeLimit;
	protected static $isEnabledOnClient;
	protected static $index = 0;

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

	protected function make() {
		if(headers_sent(&$file, &$line)) {
			throw new Exception('setcookie failed because haders are sent (' . $file . ':' . $line . '). Try to use ob_start() to prevent this');
		}
		if(!self::$isEnabledOnClient) {
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
		setcookie(self::cookieName . '_' . $this->getEventIndex(), json_encode($data), time() + self::cookieLifetime, '/');
	}

	protected function isEnabledOnClient() {
		return isset($_COOKIE[self::cookieName . 'c']);
	}

	protected function setEnabledOnServer() {
		if(!isset($_COOKIE[self::cookieName . 's'])) {
			setcookie(self::cookieName . 's', '1', null, '/');
		}
	}

	protected function getEventIndex() {
		return substr(number_format(microtime(1), 3, '', ''), -6) + self::$index ++;
	}
}