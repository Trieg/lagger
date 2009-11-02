<?php

/**
 * 
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 * 
 */
class Lagger_Tagger {
	
	public function __construct($sessionData, 
	
	
	protected function checkRewriteTags($tagsVarName=null, $accessPin=null, $accessPinVarName = '__pin') {
		if ($tagsVarName) {
			// if (!session_id()) {
				// session_start();
			// }
			$sessionVar = $tagsVarName . $accessPinVarName . $accessPin;
			if (array_key_exists($sessionVar, $_SESSION)) {
				if (isset($_GET[self::rewriteTagsResetVar])) {
					unset($_SESSION[$sessionVar]);
					return false;
				}
				elseif (array_key_exists($tagsVarName, $_GET)) {
					$_SESSION[$sessionVar] = $_GET[$tagsVarName];
				}
				return $_SESSION[$sessionVar];
			}
			elseif (array_key_exists($tagsVarName, $_GET)) {
				if (!$accessPin || (array_key_exists($accessPinVarName, $_GET) && $_GET[$accessPinVarName] == $accessPin)) {
					$_SESSION[$sessionVar] = $_GET[$tagsVarName];
					return $_GET[$tagsVarName];
				}
			}
		}
		return false;
	}
	
	public function callMake(Lagger_Eventspace $eventspace) {
		$this->eventspace = $eventspace;
		
		if ($this->skiper) {
			if (!$this->skiper->isSkiped($this->skiperGroup)) {
				$this->skiper->setSkip($this->skiperGroup);
				$this->make();
			}
		}
		else {
			$this->make();
		}
	}
	
	public function setSkiper(Lagger_Skiper $skiper, $skiperGroup = null) {
		$this->skiper = $skiper;
		$this->skiperGroup = $skiperGroup ? $skiperGroup . '_' : null;
	}

	abstract protected function make();
}
