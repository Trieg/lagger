<?php

abstract class Lagger_ActionAbstract {

	protected $skipper;
	protected $skipperGroup;

	public function callMake() {
		if (! $this->skipper || ! $this->skipper->isSkip($this->skipperGroup)) {
			$this->make();
		}
		
		if ($this->skipper) {
			$this->skipper->setSkip($this->skipperGroup);
		}
	}

	public function setSkipper(Lagger_Skipper $skipper, $skipperGroup = null) {
		$this->skipper = $skipper;
		$this->skipperGroup = $skipperGroup ? $skipperGroup . '_' : null;
	}

	abstract protected function make();
}
