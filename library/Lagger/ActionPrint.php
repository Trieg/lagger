<?php

/**
 * 
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 * 
 */
class Lagger_ActionPrint extends Lagger_Action {
	
	protected $buffer;
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
			$this->buffer[] = $this->eventspace->fetch($this->template);
		}
		else {
			$this->show($this->eventspace->fetch($this->template));
		}
	}

	public function flush($return=false) {
		if ($this->buffer) {
			$outputString = implode('', $this->bufffer);
			$this->buffer = array();
			if($return) {
				return $outputString;
			}
			else {
				$this->show($outputString);
			}
		}
	}

	protected function show($string) {
		//		if ($ob_level = ob_get_level())
		//			for($i = $ob_level; $i > 0; $i--) {
		//				$contents[$i] = ob_get_contents();
		//				ob_end_clean();
		//			}
		
		echo $string;
		flush();
		
	//		if ($ob_level)
	//			for($i = 1; $i <= $ob_level; $i++) {
	//				ob_start();
	//				echo $contents[$i];
	//				flush();
	//			}
	}

	public function __destruct() {
		$this->flush();
	}
}