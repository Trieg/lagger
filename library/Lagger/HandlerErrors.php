<?php

class Lagger_HandlerErrors extends Lagger_HandlerAbstract {
	
	protected $config = array('default_code' => E_USER_ERROR, 'merge_old_handler' => 0, 'restore_old_handler' => 0, 'restore_old_ini_sets' => 0, 'ini_sets' => array('display_errors' => 0, 'html_errors' => 0, 'display_startup_errors' => 1, 'ignore_repeated_errors' => 0, 'ignore_repeated_source' => 0));
	protected $oldIniSets;
	protected $oldErrorHandler;

	protected function init() {
		parent::init();
		foreach ($this->config['ini_sets'] as $attribute => $value) {
			$this->oldIniSets[$attribute] = ini_set($attribute, $value);
		}
		$this->oldErrorHandler = set_error_handler(array($this, 'handle'));
	}

	public function handle($code = null, $message = null, $file = null, $line = null) {
		parent::handle($code ? $code : $this->config['default_code'], $message, $file, $line);
		
		if ($this->oldErrorHandler && $this->config['merge_old_handler']) {
			user_func_array($this->oldErrorHandler, array($code, $message, $file, $line));
		}
	}

	public function __destruct() {
		if ($this->oldIniSets && $this->config['restore_old_ini_sets']) {
			foreach ($this->oldIniSets as $attribute => $value) {
				ini_set($attribute, $value);
			}
		}
		
		if ($this->oldErrorHandler && $this->config['restore_old_handler']) {
			set_error_handler($this->oldErrorHandler);
		}
	}
}
