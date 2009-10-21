<?php

class Lagger_HandlerAbstract {
	
	protected $actions = array();
	protected $config = array();
	protected $handling;

	protected static $lastMessage;
	protected static $lastCode;
	protected static $lastType;
	protected static $lastFile;
	protected static $lastLine;

	protected static $codesAliases = array(E_ERROR => 'fatal', E_WARNING => 'warning', E_PARSE => 'fatal', E_NOTICE => 'notice', E_CORE_ERROR => 'fatal', E_CORE_WARNING => 'warning', E_COMPILE_ERROR => 'fatal', E_COMPILE_WARNING => 'warning', E_USER_ERROR => 'fatal', E_USER_WARNING => 'warning', E_USER_NOTICE => 'notice');
	protected static $typesAliases = array(E_ERROR => 'E_ERROR', E_WARNING => 'E_WARNING', E_PARSE => 'E_PARSE', E_NOTICE => 'E_NOTICE', E_CORE_ERROR => 'E_CORE_ERROR', E_CORE_WARNING => 'E_CORE_WARNING', E_COMPILE_ERROR => 'E_COMPILE_ERROR', E_COMPILE_WARNING => 'E_COMPILE_WARNING', E_USER_ERROR => 'E_USER_ERROR', E_USER_WARNING => 'E_USER_WARNING', E_USER_NOTICE => 'E_USER_NOTICE');
	
	const rewriteRuleDisableVar = '__reset';

	public function __construct($config = array()) {
		$this->updateConfig($config);
		$this->init();
	}

	protected function updateConfig($newConfigNode, &$oldConfigNode = false) {
		if (!$oldConfigNode) {
			$oldConfigNode = & $this->config;
		}
		
		if (is_array($newConfigNode))
			foreach ($newConfigNode as $attrbiute => $value)
				if (isset($oldConfigNode[$attrbiute]))
					if (is_scalar($oldConfigNode[$attrbiute])) {
						$oldConfigNode[$attrbiute] = is_scalar($newConfigNode[$attrbiute]) ? $newConfigNode[$attrbiute] : $value;
					}
					elseif (is_array($oldConfigNode[$attrbiute]) && is_array($value)) {
						$this->updateConfig($value, $oldConfigNode[$attrbiute]);
					}
	}

	protected function init() {
		static $inited;
		
		if (!$inited) {
			Lagger_Template::registerVarReference('message', self::$lastMessage);
			Lagger_Template::registerVarReference('code', self::$lastCode);
			Lagger_Template::registerVarReference('type', self::$lastType);
			Lagger_Template::registerVarReference('file', self::$lastFile);
			Lagger_Template::registerVarReference('line', self::$lastLine);
			
			$inited = true;
		}
	}

	public function addAction(Lagger_ActionAbstract $action, $rule = null, $rewriteRuleVar = null, $rewriteRuleAccessPin = null, $rewriteRuleAccessVar = '__pin') {
		if (($rewriteRule = $this->checkRewriteRule($rewriteRuleVar, $rewriteRuleAccessPin, $rewriteRuleAccessVar)) !== false)
			$rule = $rewriteRule;
		
		if ($rule !== false && $rule !== 0 && $rule !== '0')
			$this->actions[] = array('action' => $action, 'rule' => $rule);
	}

	protected function checkRewriteRule($rewriteRuleVar, $rewriteRuleAccessPin, $rewriteRuleAccessVar) {
		if ($rewriteRuleVar) {
			if (!session_id()) {
				session_start();
			}
			
			$sessionVar = $rewriteRuleVar . $rewriteRuleAccessVar . $rewriteRuleAccessPin;
			
			if (array_key_exists($sessionVar, $_SESSION)) {
				if (isset($_GET[self::rewriteRuleDisableVar])) {
					unset($_SESSION[$sessionVar]);
					return false;
				}
				elseif (array_key_exists($rewriteRuleVar, $_GET)) {
					$_SESSION[$sessionVar] = $_GET[$rewriteRuleVar];
					return $_GET[$rewriteRuleVar];
				}
				else {
					return $_SESSION[$sessionVar];
				}
			}
			elseif (array_key_exists($rewriteRuleVar, $_GET)) {
				if ((!$rewriteRuleAccessPin || (array_key_exists($rewriteRuleAccessVar, $_GET) && $_GET[$rewriteRuleAccessVar] == $rewriteRuleAccessPin))) {
					$_SESSION[$sessionVar] = $_GET[$rewriteRuleVar];
					return $_GET[$rewriteRuleVar];
				}
			}
		}
		
		return false;
	}

	public function handle($code = null, $message = null, $file = null, $line = null) {
		if ($this->handling) {
			return false;
		}
		else {
			$this->handling = true;
		}
		
		self::$lastMessage = $message;
		self::$lastCode = $code && isset(self::$codesAliases[$code]) ? self::$codesAliases[$code] : $code;
		self::$lastType = $code && isset(self::$typesAliases[$code]) ? self::$typesAliases[$code] : $code;
		self::$lastFile = $file;
		self::$lastLine = $line;
		
		Lagger_Template::resetVarsValues();
		
		foreach ($this->getActionsByCodes(self::$lastCode) as $action) {
			$action['action']->callMake();
		}
		
		$this->handling = false;
	}

	protected function getActionsByCodes($codes) {
		$actions = array();
		
		foreach ($this->actions as $action) {
			if ($this->isRuleMatchCodes($action['rule'], $codes)) {
				$actions[] = $action;
			}
		}
		return $actions;
	}

	protected function isRuleMatchCodes($ruleStr, $codesStr) {
		return !$ruleStr || (array_intersect(array_map('trim', explode(',', $ruleStr)), array_map('trim', explode(',', $codesStr))));
	}
}