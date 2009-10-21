<?php

class Lagger_Template {
	
	const left_tag = '%';
	const right_tag = '%';
	const modifier_separator = '|';
	
	protected $template;
	protected $templateVars = array();
	protected static $varsDefinitions = array();
	protected static $varsValues = array();
	protected static $modifiersDefinitions = array();

	public function __construct($template) {
		if (preg_match_all('/' . self::left_tag . '(.+?)' . self::right_tag . '/', str_replace(array('\\' . self::left_tag, '\\' . self::right_tag), '', $template), $matches))
			foreach ($matches[1] as $varsAndModifiersString) {
				$varsAndModifiers = explode(self::modifier_separator, $varsAndModifiersString);
				$this->templateVars[$varsAndModifiersString] = array($varsAndModifiers[0], $varsAndModifiersString, array_splice($varsAndModifiers, 1));
			}
		
		$this->template = str_replace(array('\\' . self::left_tag, '\\' . self::right_tag), array(self::left_tag, self::right_tag), $template);
	}

	public function compile() {
		$string = $this->template;
		
		foreach ($this->templateVars as &$var) {
			$value = $this->getVarValue($var[0]);
			
			if ($var[2])
				foreach ($var[2] as $modifier)
					$value = $this->applyModifier($modifier, $value);
			else
				$value = $this->compileVarValue($value);
			
			$string = str_replace(self::left_tag . $var[1] . self::right_tag, $value, $string);
		}
		
		return $string;
	}

	public static function registerModifier($name, $callback) {
		if (!is_callable($callback))
			throw new Exception('Second argument must be valid callback');
		
		self::$modifiersDefinitions[$name] = $callback;
	}

	public function applyModifier($name, $value) {
		return isset(self::$modifiersDefinitions[$name]) ? call_user_func_array(self::$modifiersDefinitions[$name], array($value)) : $value;
	}

	public function __toString() {
		return $this->compile();
	}

	public static function registerVar($var, $value, $callback = false, $callbackArguments = array()) {
		if (array_key_exists($var, self::$varsDefinitions))
			throw new BacktracedException(__CLASS__ . ': Var "' . $var . '" is already registered');
		
		$new_var = array();
		
		if ($callback) {
			if (is_callable($value)) {
				$new_var['type'] = 'callback';
				$new_var['callback'] = $value;
				$new_var['arguments'] = $callbackArguments;
			}
			else
				throw new BacktracedException(__CLASS__ . ': Var "' . $var . '" is setted as callback and is not callable');
		}
		else {
			$new_var['type'] = 'var';
			$new_var['value'] = & $value;
		}
		
		self::$varsDefinitions[$var] = $new_var;
		
		return true;
	}

	public static function registerVarReference($var, &$value, $callback = false, $callbackArguments = array()) {
		if (array_key_exists($var, self::$varsDefinitions))
			throw new BacktracedException(__CLASS__ . ': Var "' . $var . '" is already registered');
		
		$new_var = array();
		
		if ($callback) {
			if (is_callable($value)) {
				$new_var['type'] = 'callback';
				$new_var['callback'] = & $value;
				$new_var['arguments'] = $callbackArguments;
			}
			else
				throw new BacktracedException(__CLASS__ . ': Var "' . $var . '" is setted as callback and is not callable');
		}
		else {
			$new_var['type'] = 'var';
			$new_var['value'] = & $value;
		}
		
		self::$varsDefinitions[$var] = $new_var;
		
		return true;
	}

	public static function isVarRegistered($var) {
		return array_key_exists($var, self::$varsDefinitions);
	}

	public static function resetVarsValues() {
		self::$varsValues = array();
	}

	protected function getVarValue($varName) {
		if (!array_key_exists($varName, self::$varsDefinitions))
			return false;
		
		if (!array_key_exists($varName, self::$varsValues))
			self::$varsValues[$varName] = $this->compileVar(self::$varsDefinitions[$varName]);
		
		return self::$varsValues[$varName];
	}

	protected function compileVar($var) {
		return $var['type'] == 'callback' ? $this->compileVarValue(call_user_func_array($var['callback'], $var['arguments'])) : $this->compileVarValue($var['value']);
	}

	protected function compileVarValue($value) {
		if (is_scalar($value) || is_null($value))
			return $value;
		elseif (is_array($value) || is_object($value))
			return var_export($value);
		
		return 'ERROR:Lagger_UNKOWN_VAR_TYPE';
	}
}