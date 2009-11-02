<?php

/**
 * 
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 * 
 */
class Lagger_ExpireList {
	
	protected $storageDir;
	protected $filePostfix;
	
	const checkAllExpires = 100;

	public function __construct($storageDir, $entryPostfix = '.expire') {
		$this->storageDir = realpath($storageDir) . DIRECTORY_SEPARATOR;
		$this->entryPostfix = $entryPostfix;
		if (!is_dir($this->storageDir) && !@mkdir($this->storageDir, 0755, true)) {
			throw new Exception('Directory "' . $this->storageDir . '" not found');
		}
		if (!mt_rand(0, self::checkAllExpires)) {
			$this->checkAllExpired();
		}
	}

	public function isExpired($key, $entryPrefix = null) {
		$filepath = $this->getEntryFilepath($key, $entryPrefix);
		if (is_file($filepath)) {
			if (file_get_contents($filepath) > time()) {
				return false;
			}
			else {
				unlink($filepath);
			}
		}
		return true;
	}

	protected function getEntryFilepath($key, $entryPrefix = null) {
		return $this->storageDir . $entryPrefix . $key . $this->entryPostfix;
	}

	public function setExpire($key, $expireInSeconds, $entryPrefix = null) {
		file_put_contents($this->getEntryFilepath($key, $entryPrefix), time() + $expireInSeconds);
	}

	protected function getAllEntries() {
		$entries = array();
		if ($handle = opendir($this->storageDir)) {
			while (false !== ($file = readdir($handle))) {
				if (substr($file, -1 * strlen($this->entryPostfix)) == $this->entryPostfix) {
					$entries[] = $this->storageDir . $file;
				}
			}
		}
		closedir($handle);
		return $entries;
	}

	public function checkAllExpired() {
		foreach ($this->getAllEntries() as $file) {
			if (file_get_contents($file) <= time()) {
				unlink($file);
			}
		}
	}

	public function clearAll() {
		foreach ($this->getAllEntries() as $file) {
			unlink($file);
		}
	}
}
