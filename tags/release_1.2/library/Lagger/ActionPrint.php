<?php

/**
 * 
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 * 
 */
class Lagger_ActionPrint extends Lagger_Action {
	
	protected static $buffer;
	protected $buffering;
	protected $template;

	public function __construct($template, $buffering = false) {
		$this->template = $template;
		$this->buffering = $buffering;
	}

	public function startBuffering() {
		$this->buffering = true;
	}

	public function stopBuffering() {
		$this->buffering = false;
	}

	protected function make() {
		if ($this->buffering) {
			self::$buffer[] = $this->eventspace->fetch($this->template);
		}
		else {
			self::show($this->eventspace->fetch($this->template));
		}
	}

	public static function flush($return=false) {
		if (self::$buffer) {
			$outputString = implode(' ', self::$buffer);
			self::$buffer = array();
			if($return) {
				return $outputString;
			}
			else {
				self::show($outputString);
			}
		}
	}

	protected static function show($string) {
		echo $string;
	}

	public function __destruct() {
		self::flush();
	}
}