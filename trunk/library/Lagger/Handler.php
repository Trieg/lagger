<?php

/**
 * 
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 * 
 */
abstract class Lagger_Handler {
	
	protected $eventspace;
	protected $actions = array();
	protected $currentAction;
	protected $handling;
	public static $skipNexInternalException;
	protected static $internalErrorsActions = array();
	
	const tagSeparator = ',';

	public function __construct(Lagger_Eventspace $eventspace) {
		$this->eventspace = $eventspace;
		$this->init();
	}

	protected function init() {
	}

	public function getEventspace() {
		return $this->eventspace;
	}

	public function addAction(Lagger_Action $action, $tags = null, Lagger_Tagger $tagger = null) {
		if ($tagger && $tagger->tagsRewrited()) {
			$tags = $tagger->getNewTags();
		}
		if ($tags === '') {
			$tags = null;
		}
		if ($tags || $tags === null) {
			$this->actions[] = array('objects' => $action, 'tags' => $tags ? array_map('trim', explode(self::tagSeparator, $tags)) : array());
		}
		return $this;
	}

	protected function handleActions(array $eventVars, $eventTags = null) {
		if (!$this->handling) { // TODO: require some handler for internal Lagger errors
			$this->handling = true;
			$eventVars['tags'] = $eventTags;
			if (!isset($eventVars['handler'])) {
				$eventVars['handler'] = get_class($this);
			}
			$this->eventspace->resetVarsValues($eventVars);
			$throwException = null;
			foreach ($this->getActionsByTags($eventTags) as $action) {
				try {
					$this->currentAction = $action['objects'];
					$action['objects']->callMake($this->eventspace);
				}
				catch (Exception $e) {
					if (self::$skipNexInternalException) {
						self::$skipNexInternalException = false;
						$throwException = $e;
					}
					else {
						self::handleInternalError($this->eventspace, get_class($e), 'There is internal error during handling "' . get_class($this->currentAction) . '": ' . print_r($e, true));
					}
				}
			}
			$this->handling = false;
			if($throwException) {
				throw $throwException;
			}
		}
	}

	protected function getActionsByTags($eventTags) {
		$actions = array();
		$eventTags = array_map('trim', explode(self::tagSeparator, $eventTags));
		foreach ($this->actions as $action) {
			if ($this->isTagsMatches($action['tags'], $eventTags)) {
				$actions[] = $action;
			}
		}
		return $actions;
	}

	protected function isTagsMatches($actionTags, $eventTags) {
		return !$actionTags || array_intersect($actionTags, $eventTags);
	}

	/**************************************************************
	 INTERNAL ERROR HANDLING
	 **************************************************************/
	
	public static function addInternalErrorAction(Lagger_Action $action) {
		self::$internalErrorsActions[] = $action;
	}

	protected static function handleInternalError(Lagger_Eventspace $eventspace, $type, $message) {
		$newEventspace = clone $eventspace;
		$eventVars = array('message' => $message, 'type' => $type);
		$newEventspace->resetVarsValues($eventVars);
		foreach (self::$internalErrorsActions as $action) {
			$action->callMake($newEventspace);
		}
	}

	public function __destruct() {
		if ($this->handling) {
			self::handleInternalError($this->eventspace, 'LAGGER_INTERNAL_FATAL', 'Unkown internal FATAL error in handling "' . get_class($this->currentAction) . '"');
		}
	}
}