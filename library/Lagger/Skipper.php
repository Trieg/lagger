<?php

class Lagger_Skipper {
	
	protected $hashTemplate;
	protected $expire;
	protected $expiredList;

	public function __construct(Lagger_Template $hashTemplate, $expireInSeconds, Lagger_ExpiredList $expiredList) {
		$this->hashTemplate = $hashTemplate;
		$this->expire = $expireInSeconds;
		$this->expiredList = $expiredList;
	}

	public function isSkip($skipperGroup = null) {
		return !$this->expiredList->isExpired(md5($this->hashTemplate->compile()), $skipperGroup);
	}

	public function setSkip($skipperGroup = null) {
		return $this->expiredList->setExpire(md5($this->hashTemplate->compile()), $this->expire, $skipperGroup);
	}

	public function reset() {
		$this->expiredList->clearAll();
	}
}