function Options() {

	var self = this;
	self.justInstalled = false;
	self.justUpdated = false;

	var defaults = {
		enabled : true,
		consoleDebug : true,
		consoleErrors : true,
		notifyDebug : true,
		notifyErrors : true,
		notifyDelay : 1
	};

	self.set = function(option, value) {
		localStorage[option] = value;
	};

	self.get = function(option) {
		var value = localStorage.getItem(option);
		if (value == 'true') {
			return true;
		}
		if (value == 'false') {
			return false;
		}
		return value;
	};

	function getVersion() {
		var xhr = new XMLHttpRequest();
		xhr.open('GET', chrome.extension.getURL('manifest.json'), false);
		xhr.send(null);
		var manifest = JSON.parse(xhr.responseText);
		return manifest.version;
	}

	// construct

	function getterFunc(option) {
		return function() {
			return self.get(option);
		};
	}
	function setterFunc(option) {
		return function(value) {
			self.set(option, value);
		};
	}
	for ( var option in defaults) {
		if (self.get(option) === null) {
			self.set(option, defaults[option]);
		}
		self.__defineGetter__(option, getterFunc(option));
		self.__defineSetter__(option, setterFunc(option));
	}

	var version = getVersion();
	if (!this.get('version')) {
		self.justInstalled = true;
		this.set('version', version);
	}
	else if (this.get('version') != version) {
		self.justUpdated = true;
		this.set('version', version);
	}
};
