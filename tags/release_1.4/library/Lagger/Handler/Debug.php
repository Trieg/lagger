<?php

/**
 * 
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 * 
 */
class Lagger_Handler_Debug extends Lagger_Handler{

	protected $defaultTags = 'message';
	
	public function handle($message = null, $tags = null) {		
		$this->handleActions(array('message' => $message), $tags ? $tags : $this->defaultTags);
	}

	protected function isTagsMatches($actionTags, $eventTags) {
		return !$actionTags || count(array_intersect($actionTags, $eventTags)) == count($actionTags);
	}
}
