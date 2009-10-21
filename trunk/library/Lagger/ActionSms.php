<?php

class Lagger_ActionSms extends Lagger_ActionAbstract {
	
	protected $from;
	protected $to = array();
	protected $translit;
	protected $messageTemplate;

	public function __construct($from, $to, Lagger_Template $messageTemplate, $translit = true) {
		$this->from = $from;
		$this->to = is_array($to) ? $to : explode(',', $to);
		$this->messageTemplate = $messageTemplate;
		$this->translit = $translit;
	}

	protected function make() {
		$smsSender = new SmsSender();
		foreach ($this->to as $to) {
			$smsSender->send($this->from, trim($to), $this->messageTemplate->compile(), $this->translit);
		}
	}
}
