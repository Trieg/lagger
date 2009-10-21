<?php

class Lagger_ActionMail extends Lagger_ActionAbstract {

	protected $from;
	protected $to;
	protected $subjectTemplate;
	protected $bodyTemplate;

	public function __construct($from, $to, Lagger_Template $subjectTemplate, Lagger_Template $bodyTemplate) {
		$this->from = $from;
		$this->to = $to;
		$this->subjectTemplate = $subjectTemplate;
		$this->bodyTemplate = $bodyTemplate;
	}

	protected function make() {
		$mail = new Mail($this->from, $this->to, $this->subjectTemplate->compile(), $this->bodyTemplate->compile());
		$mail->send();
	}
}
