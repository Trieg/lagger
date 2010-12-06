<?php

/**
 *
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 *
 */
class Lagger_Action_FileLog extends Lagger_Action {

	protected $filepath;
	protected $template;
	protected $sizeLimit;
	protected $daysLimit;
	protected $onetimeMode;

	const checkLimit = 100;

	public function __construct($template, $filepath, $sizeLimit = null, $daysLimit = null, $onetimeMode = false) {
		if(!file_exists($filepath)) {
			file_put_contents($filepath, '');
		}
		$this->filepath = realpath($filepath); // required for fopen works on script shutdown
		$this->onetimeMode = $onetimeMode;
		$this->template = $template;
		$this->sizeLimit = (int) $sizeLimit;
		$this->daysLimit = (int) $daysLimit;
	}

	protected function make() {
		static $firstTime = true;
		if($firstTime) {
			if($this->onetimeMode) {
				file_put_contents($this->filepath, '');
			}
			$firstTime = false;
		}

		$this->checkLimits();
		$fp = fopen($this->filepath, 'a'); // TODO: lock check/set
		fputs($fp, $this->eventspace->fetch($this->template) . "\n");
		fclose($fp);
	}

	protected function checkLimits() {
		if(!mt_rand(0, self::checkLimit)) {
			if($this->sizeLimit || $this->daysLimit) {
				if(file_exists($this->filepath)) {
					$fp = fopen($this->filepath, 'r');
					$fstat = fstat($fp);
					fclose($fp);
					if($this->daysLimit && (time() - $fstat['mtime']) > ($this->daysLimit * 24 * 60 * 60)) {
						file_put_contents($this->filepath, '');
					}
				}
			}
		}
	}
}
