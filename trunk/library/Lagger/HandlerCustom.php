<?php

class Lagger_HandlerCustom extends Lagger_HandlerAbstract {
	
	protected static $instance;

	public function __construct($config = array()) {
		$this->updateConfig($config);
		
		if (!self::$instance)
			self::$instance = $this;
	}

	public static function getInstance($config = array()) {
		if (!self::$instance) {
			$class = __CLASS__;
			self::$instance = new $class($config);
		}
		
		return self::$instance;
	}

	public static function shandle($message = null, $profiles = null) {
		self::getInstance()->handle($profiles, $message);
	}

	protected function isRuleMatchCodes($ruleStr, $profilesStr) {
		return !$ruleStr || !array_diff(array_map('trim', explode(',', $ruleStr)), array_map('trim', explode(',', $profilesStr)));
	}
}

