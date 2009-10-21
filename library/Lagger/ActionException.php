<?php

class Lagger_ActionException extends Lagger_ActionAbstract {
	
	protected $exceptionClass;
	
	protected $messageTemplate;
	protected $typeTemplate;
	protected $fileTemplate;
	protected $lineTemplate;

	public function __construct($exceptionClass = null, Lagger_Template $messageTemplate = null, Lagger_Template $typeTemplate = null, Lagger_Template $fileTemplate = null, Lagger_Template $lineTemplate = null) {
		if(!$exceptionClass) {
			$exceptionClass = 'Lagger_PhpErrorException';
		}
		if (!is_subclass_of($exceptionClass, 'Exception')) {
			throw new Exception(__METHOD__ . ' must recieve name of Exception subclass as first argument');
		}
		$this->exceptionClass = $exceptionClass;
		
		if (!$messageTemplate) {
			$messageTemplate = new Lagger_Template('%message%');
		}
		$this->messageTemplate = $messageTemplate;
		
		if (!$typeTemplate) {
			$typeTemplate = new Lagger_Template('%type%');
		}
		$this->typeTemplate = $typeTemplate;
		
		if (!$fileTemplate) {
			$fileTemplate = new Lagger_Template('%file%');
		}
		$this->fileTemplate = $fileTemplate;
		
		if (!$lineTemplate) {
			$lineTemplate = new Lagger_Template('%line%');
		}
		$this->lineTemplate = $lineTemplate;
	}

	protected function make() {
		$class = $this->exceptionClass;
		$exception = new $class($this->messageTemplate->compile(), defined($this->typeTemplate->compile()) ? constant($this->typeTemplate->compile()) : (int)$this->typeTemplate->compile());
		$this->setProtectedPropertyValue($exception, 'file', $this->fileTemplate->compile());
		$this->setProtectedPropertyValue($exception, 'line', $this->lineTemplate->compile());
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