<?php

class Lagger_ExpiredList {
	
	protected $storageDir;
	protected $entryPostfix;
	
	const checkAllExpires = 100;

	public function __construct($storageDir, $entryPostfix = '.expire') {
		$this->storageDir = rtrim($storageDir, '/\\') . DIRECTORY_SEPARATOR;
		$this->entryPostfix = $entryPostfix;
		
		if (!is_dir($this->storageDir)) {
			if (!@mkdir($this->storageDir, 0777, true)) {
				throw new Exception(__METHOD__ . " {$this->storageDir} not found");
			}
		}
		
		if (!mt_rand(0, self::checkAllExpires - 1))
			$this->checkAllExpired();
	}

	public function isExpired($key, $entryPrefix = null) {
		$filepath = $this->storageDir . $entryPrefix . $key . $this->entryPostfix;
		
		if (is_file($filepath)) {
			if (file_get_contents($filepath) <= time()) {
				unlink($filepath);
			}
			else {
				return false;
			}
		}
		
		return true;
	}

	public function setExpire($key, $expireInSeconds, $entryPrefix = null) {
		file_put_contents($this->storageDir . $entryPrefix . $key . $this->entryPostfix, time() + $expireInSeconds);
	}

	protected function getAllFiles() {
		$storages = array();
		$sublen = -1 * strlen($this->entryPostfix);
		
		if ($handle = opendir($this->storageDir))
			while (false !== ($file = readdir($handle)))
				if (substr($file, $sublen) == $this->entryPostfix)
					$storages[] = $this->storageDir . $file;
		
		closedir($handle);
		
		return $storages;
	}

	public function checkAllExpired() {
		foreach ($this->getAllFiles() as $file)
			if (file_get_contents($file) <= time())
				unlink($file);
	}

	public function clearAll() {
		foreach ($this->getAllFiles() as $file)
			unlink($file);
	}
}
