<?php

class Lagger_HandlerExceptions extends Lagger_HandlerAbstract {
	
	protected $config = array('default_code' => E_USER_ERROR, 'merge_old_handler' => false, 'restore_old_handler' => false);
	protected $oldExceptionHandler;

	protected function init() {
		parent::init();
		$this->oldExceptionHandler = set_exception_handler(array($this, 'handleException'));
	}

	public function handleException(Exception $exception) {
		$code = $exception->getCode() ? $exception->getCode() : $this->config['default_code'];
		
		$this->handle($code, $exception->getMessage(), $exception->getFile(), $exception->getLine());
		
		if ($this->oldExceptionHandler && $this->config['merge_old_handler']) {
			user_func_array($this->oldExceptionHandler, array($exception));
		}
	}

	public function __destruct() {
		if ($this->oldExceptionHandler && $this->cofig['restore_old_handler']) {
			set_handler($this->oldExceptionHandler);
		}
	}
}