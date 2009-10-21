<?php

class Lagger_ActionFileLog extends Lagger_ActionAbstract {

	protected $filepath;
	protected $template;
	protected $sizeLimit;
	protected $daysLimit;

	public function __construct(Lagger_Template $template, $filepath, $sizeLimit = null, $daysLimit = null) {
		$this->filepath = $filepath;
		$this->template = $template;
		$this->sizeLimit = (int)$sizeLimit;
		$this->daysLimit = (int)$daysLimit;
	}

	protected function make() {
		$fp = fopen($this->filepath, 'a'); // TODO: lock check/set
		

		if ($this->sizeLimit || $this->daysLimit) {
			$fstat = fstat($fp);
			
			// if(($this->daysLimit && (time() - $fstat['mtime']) > ($this->daysLimit * 24 * 60 * 60)) || ($this->sizeLimit && $fstat['size'] > $this->sizeLimit)) // TODO: check mdtime against cached ctime.. need some other
			if ($this->daysLimit && (time() - $fstat['mtime']) > ($this->daysLimit * 24 * 60 * 60)) {
				fclose($fp);
				unlink($this->filepath); // TODO: lock check/set, no unlink - just cut to 1/2 ?
				$fp = fopen($this->filepath, 'a');
			}
		}
		
		fputs($fp, $this->template->compile());
		fclose($fp);
	}
}
