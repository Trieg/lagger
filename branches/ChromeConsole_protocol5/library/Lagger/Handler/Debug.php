<?php

/**
 *
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 *
 */
class Lagger_Handler_Debug extends Lagger_Handler {

	const defaultTags = 'debug';

	public function handle($message = null, $tags = null, $withTraceAndSource = false, $debugCallLevel = 0) {
		if(!$tags) {
			$tags = self::defaultTags;
		}
		$eventVars = array(
			'message' => $message,
			'type' => $tags
		);
		if($withTraceAndSource) {
			$traceData = debug_backtrace();
			if($traceData) {
				$eventVars['trace'] = self::convertTraceToString($traceData, $file, $line, $debugCallLevel + 1);
				$eventVars['file'] = $file;
				$eventVars['line'] = $line;
			}
		}
		$this->handleActions($eventVars, $tags);
	}

	protected function isTagsMatches($eventTags, $incTags, $excTags) {
		return (!$excTags || !array_intersect($eventTags, $excTags)) && (!$incTags || count(array_intersect($incTags, $eventTags)) == count($incTags));
	}
}
