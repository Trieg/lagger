<?php

/**
 * 
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 * 
 */
class Lagger_ActionFileLog extends Lagger_Action {
	
	protected $filepath;
	protected $template;
	protected $sizeLimit;
	protected $daysLimit;
	
	const checkLimit = 100;

	public function __construct($template, $filepath, $sizeLimit = null, $daysLimit = null) {
		$this->filepath = realpath($filepath);
		$this->template = $template;
		$this->sizeLimit = (int)$sizeLimit;
		$this->daysLimit = (int)$daysLimit;
	}

	protected function make() {
		$this->checkLimits();
		
		$fp = fopen($this->filepath, 'a'); // TODO: lock check/set
		fputs($fp, $this->eventspace->fetch($this->template));
		fclose($fp);
	}

	protected function checkLimits() {
		if (!mt_rand(0, self::checkLimit)) {
			if ($this->sizeLimit || $this->daysLimit) {
				$fp = fopen($this->filepath, 'r');
				$fstat = fstat($fp);
				if ($this->daysLimit && (time() - $fstat['mtime']) > ($this->daysLimit * 24 * 60 * 60)) {
					unlink($this->filepath); // TODO: lock check/set, no unlink - just cut to 1/2 ?
				}
				fclose($fp);
			}
		}
	}
}
