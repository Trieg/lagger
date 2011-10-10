<?php

/**
 *
 * @desc This class provides namespace of events variables
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 *
 */
class Lagger_Eventspace {

	const leftTag = '{';
	const rightTag = '}';
	const modifierTag = '|';

	const varIsValue = 0;
	const varIsCallback = 1;

	protected $vars = array();
	protected $varsValues = array();
	protected $varsStringValues = array();
	protected $modifiers = array();

	public function fetch($template) {
		if(preg_match_all('/' . preg_quote(self::leftTag) . '(.+?)' . preg_quote(self::rightTag) . '/', $template, $matches)) {
			$replaces = array();
			foreach($matches[1] as $varsAndModifiersString) {
				$varsAndModifiers = explode(self::modifierTag, $varsAndModifiersString);
				$value = $this->getVarValue($varsAndModifiers[0]);
				foreach(array_slice($varsAndModifiers, 1) as $modifier) {
					$value = $this->applyModifier($modifier, $value);
				}
				$replaces[] = $value;
			}
			return str_replace($matches[0], $replaces, $template);
		}
		return $template;
	}

	/**************************************************************
	MODIFIERS
	 **************************************************************/

	public function registerModifier($name, $callback) {
		if(!is_callable($callback)) {
			throw new Exception('Modifier "' . $name . '" is not callable');
		}
		$this->modifiers[$name] = $callback;
	}

	public function applyModifier($name, $value) {
		if(isset($this->modifiers[$name])) {
			return call_user_func($this->modifiers[$name], $value);
		}
		elseif(function_exists($name)) {
			return call_user_func($name, $value);
		}
		else {
			throw new Exception('Unkown modifier "' . $name . '". Function with name "' . $name . '" does not exists');
		}
	}

	/**************************************************************
	VARS REGISTRATION
	 **************************************************************/

	public function registerVar($name, $value) {
		$this->setVar($name, array(self::varIsValue, $value));
	}

	public function registerReference($name, &$value) {
		$this->setVar($name, array(self::varIsValue, &$value));
	}

	public function registerCallback($name, $callback, $arguments = array()) {
		if(!is_callable($callback)) {
			throw new Exception('Var "' . $name . '" is not callable');
		}
		$this->setVar($name, array(self::varIsCallback, $callback, $arguments));
	}

	protected function setVar($name, $var) {
		if(isset($this->vars[$name])) {
			throw new Exception('Var "' . $name . '" is already registered');
		}
		$this->vars[$name] = $var;
	}

	/**************************************************************
	VARS VALUES
	 **************************************************************/

	public function resetVarsValues(array $appendedVarsValues = array()) {
		$this->varsValues = array();
		$this->varsStringValues = array();
		foreach($appendedVarsValues as $var => $value) {
			$this->setVarValue($var, $value);
		}
	}

	public function getVarsValues($asString = true) {
		return $asString ? $this->varsStringValues : $this->varsValues;
	}

	public function getVarValue($varName, $asString = true) {
		if(array_key_exists($varName, $this->varsValues)) {
			return $asString ? $this->varsStringValues[$varName] : $this->varsValues[$varName];
		}
		if(!array_key_exists($varName, $this->vars)) {
			return null;
		}
		$value = $this->compileVar($this->vars[$varName]);
		$this->setVarValue($varName, $value);
		return $asString ? $this->varsStringValues[$varName] : $this->varsValues[$varName];
	}

	protected function setVarValue($varName, $value) {
		$this->varsValues[$varName] = $value;
		$this->varsStringValues[$varName] = is_scalar($value) || $value === null ? $value : print_r($value, true);
	}

	public function __get($varName) {
		return $this->getVarValue($varName);
	}

	protected function compileVar($var) {
		if($var[0] == self::varIsCallback) {
			$value = call_user_func_array($var[1], $var[2]);
		}
		else {
			$value = $var[1];
		}
		return $value;
	}
}
