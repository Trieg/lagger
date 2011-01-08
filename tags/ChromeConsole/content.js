var client = new function() {

	var self = this;
	var isEnabled = false;
	var clientProtocol = 3;
	var fatalNotified = false;

	function isEnabledOnServer() {
		var m = new RegExp(';\\s*phpcsls=(.*?);', 'g').exec(';' + document.cookie + ';');
		if (m) {
			var serverProtocol = m[1];
			if (serverProtocol < clientProtocol) {
				if (!fatalNotified) {
					sendToNotifications({
						notifyDelay : 30,
						type : 'ahtung',
						subject : 'PHP Console failed',
						text : 'You\'re using old version of Lagger on ' + location.host + '. Please update it from <a href="http://code.google.com/p/lagger" target="_blank">Lagger homepage</a>.'
					});
					fatalNotified = true;
				}
			}
			if (serverProtocol > clientProtocol) {
				if (!fatalNotified) {
					sendToNotifications({
						notifyDelay : 30,
						type : 'ahtung',
						subject : 'PHP Console disabled',
						text : 'You\'re using old version of "PHP Console" extension. Please update it from <a href="https://chrome.google.com/extensions/detail/nfhmhhlpfleoednkpnnnkolmclajemef"  target="_blank">PHP Console homepage</a>.'
					});
					fatalNotified = true;
				}
			}
			else {
				return true;
			}
		}
	}

	function isEnabledOnClient() {
		return new RegExp(';\\s*phpcslc=' + clientProtocol + ';').exec(';' + document.cookie + ';') || false;
	}

	function setEnabledOnClient() {
		document.cookie = 'phpcslc=' + clientProtocol + '; path=/;';
		window.location = document.location;
	}

	function sendToConsole(message) {
		var text = message.subject + ': ' + message.text + (message.source ? ' [' + message.source + ']' : '');
		if (message.type == 'error') {
			console.error(text);
		}
		else {
			console.log(text);
		}
	}

	function sendToNotifications(message) {
		chrome.extension.sendRequest({
			showNotification : true,
			message : message
		});
	}

	function onExtensionRequest(request, sender, response) {
		if (isEnabled) {
			if (request.sendMessagesToConsole) {
				for ( var i in request.messages) {
					sendToConsole(request.messages[i]);
				}
			}
		}
	}

	function registerClient() {
		chrome.extension.sendRequest({
			registerClient : true
		});
	}

	// construct

	if (isEnabledOnServer()) {
		if (!isEnabledOnClient()) {
			setEnabledOnClient();
		}
		isEnabled = true;
		registerClient();
		chrome.extension.onRequest.addListener(onExtensionRequest);
	}
};