<?php

/**
 * 
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 * 
 */
class Lagger_ActionException extends Lagger_Action {
	
	protected $exceptionClass;
	protected $messageTemplate;
	protected $codeTemplate;
	protected $fileTemplate;
	protected $lineTemplate;

	public function __construct($exceptionClass = null, $messageTemplate = null, $codeTemplate = null, $fileTemplate = null, $lineTemplate = null) {
		if (!$exceptionClass) {
			$exceptionClass = 'Lagger_PhpErrorException';
		}
		if (!is_subclass_of($exceptionClass, 'Exception')) {
			throw new Exception('First argument require to be subclass of Exception');
		}
		$this->exceptionClass = $exceptionClass;
		
		$this->messageTemplate = $messageTemplate ? $messageTemplate : '{message}';
		$this->codeTemplate = $codeTemplate ? $codeTemplate : '{code}';
		$this->fileTemplate = $fileTemplate ? $fileTemplate : '{file}';
		$this->lineTemplate = $lineTemplate ? $lineTemplate : '{line}';
	}

	// TODO: require Exception::getTrace analog
	protected function make() {
		$class = $this->exceptionClass;
		$exception = new $class($this->eventspace->fetch($this->messageTemplate), (int)$this->eventspace->fetch($this->codeTemplate));
		$this->setProtectedPropertyValue($exception, 'file', $this->eventspace->fetch($this->fileTemplate));
		$this->setProtectedPropertyValue($exception, 'line', $this->eventspace->fetch($this->lineTemplate));
		Lagger_Handler::$skipNexInternalException = true;
		throw $exception;
	}

	protected function setProtectedPropertyValue(Exception $object, $property, $value) {
		$propertyReflectoin = new ReflectionProperty($object, $property);
		$propertyReflectoin->setAccessible(true);
		$propertyReflectoin->setValue($object, $value);
	}
}

class Lagger_PhpErrorException extends Exception {
}