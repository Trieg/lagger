<?php

class Lagger_ActionPrint extends Lagger_ActionAbstract {
	
	protected $buffer;
	protected $buffering;
	protected $template;

	public function __construct(Lagger_Template $template, $buffering = false) {
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
		if ($this->buffering)
			$this->buffer[] = $this->template->compile();
		else
			$this->show($this->template->compile());
	}

	public function flush($return = false) {
		if ($this->buffer) {
			$flush = $return ? implode('', $this->buffer) : null;
			if (!$return) {
				
				foreach ($this->buffer as $string) {
					$this->show($string);
				}
			}
			$this->buffer = array();
			return $flush;
		}
	}

	protected function show($string) {
		if ($ob_level = ob_get_level())
			for($i = $ob_level; $i > 0; $i--) {
				$contents[$i] = ob_get_contents();
				ob_end_clean();
			}
		
		echo $string;
		flush();
		
		if ($ob_level)
			for($i = 1; $i <= $ob_level; $i++) {
				ob_start();
				echo $contents[$i];
				flush();
			}
	}

	public function __destruct() {
		$this->flush();
	}
}