<?php

/**
 * 
 * @see http://code.google.com/p/lagger
 * @author Barbushin Sergey http://www.linkedin.com/in/barbushin
 * 
 * @desc This action provides debuging in AJAX-mode sending all errors
          to COOKIES and then displaying then in browser JavaScript console
 
 	YOU CAN USE IT BY GOOGLE CHROME EXTENSION:
 	 
	 	1. Open Google Chrome Extensions local page chrome://extensions/
	 	2. Click "Developer mode"
	 	3. Click "Load unpacked extension"
	 	4. Set path to /Lagger/Action/CookieChromeExt directory
	 	5. Check if "Lagger PHP Debuger" extension is loaded
 	  
	OR YOU CAN USE IT BY CALLING THIS JAVASCRIPT CODE:
	
 	 if(isEnabled()) {
			setInterval(getDebugMessagesFromCookies, 1000);
		}
		
		function isEnabled() {
			return new RegExp(';\\s*phd=1;').exec(';' + document.cookie + ';') || false;
		}
		
		function getDebugMessagesFromCookies() {
			var regexp = new RegExp(';\\s*(phd_(.*?)_(.*?))=([^;]+)', 'g');
			var _messages = [];
			var _order = []; 
			while((m = regexp.exec(';' + document.cookie + ';')) != null) {
				_order.push(m[3]);
				_messages[m[3]] = {
						type: m[2],
						cookie: m[1],
						text: decodeURIComponent(m[4]).replace(/\+/g, ' ')
				};
			}
			_order.sort();
			for(var i in _order) {
				document.cookie = _messages[_order[i]].cookie + '=; expires=Thu, 01-Jan-70 00:00:01 GMT;';
				var message = _messages[_order[i]];
				if(message.type == 'error') {
					console.error(message.text);
				}
				else {
					console.log(message.text);
				}
			}
		}

 */

class Lagger_Action_Cookie extends Lagger_Action {
	
	protected $template;
	protected $type;
	protected $lifetime;
	protected static $index = 0;

	/**
	 * @param string template
	 * @param string type "error" or "debug"
	 */
	public function __construct($template, $type, $lifetime = 30) {
		$this->template = $template;
		$this->type = $type;
		$this->lifetime = $lifetime;
		if(!isset($_COOKIE['phd'])) {
			setcookie('phd', '1');
		}
	}

	protected function make() {
		// if(headers_sent(&$file, &$line)) {
			// throw new Exception('You cannot use Lagger_Action_Cookie when headers are sent ('.$file.':'.$line.'). Try to use ob_start() to prevent this');
		// }
		setcookie('phd_' . $this->type . '_' . $this->getEventIndex(), $this->eventspace->fetch($this->template), time() + $this->lifetime);
	}

	protected function getEventIndex() {
		return substr(number_format(microtime(1), 3, '', ''), -6) + self::$index ++;
	}
}
