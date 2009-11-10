<?php

require_once(dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR.'FirePHPCore'.DIRECTORY_SEPARATOR.'FirePHP.class.php');

/**
 * @desc Print error in FirePHP http://www.firephp.org
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 * 
 */
class Lagger_Action_FirePhp extends Lagger_Action{

	protected $messageTemplate;
	protected $labelTemplate;
	protected $labelType;
	
	public function __construct($messageTemplate, $labelTemplate, $labelType) {
		if(!ob_get_level()) {
			ob_start();
		}
		$this->messageTemplate = $messageTemplate;
		$this->labelTemplate = $labelTemplate;
		$this->labelType = $labelType;
	}
	
	protected function make() {
		FirePHP::getInstance(true)->fb($this->eventspace->fetch($this->messageTemplate), $this->eventspace->fetch($this->labelTemplate), $this->labelType);
	}
}