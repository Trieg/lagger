var CLIENT_VERSION = 2;

if (isEnabledOnServer()) {
	if(!isEnabledOnClient()) {
		setEnabledOnClient();
	}
	var isActive = true;
	window.onfocus = function() {
		isActive = true;
	};
	window.onblur = function() {
		isActive = false;
	};
	setInterval(getMessagesFromCookies, 1000);
}

function isEnabledOnServer() {
	var m = new RegExp(';\\s*phpcsls=(.*?);', 'g').exec(';' + document.cookie + ';');
	if(m) {
		var SERVER_VERSION = m[1];
		if (SERVER_VERSION == CLIENT_VERSION) {
			return true;
		}
		else if(SERVER_VERSION < CLIENT_VERSION) {
			alert('PHP Console FAILED: You\'re using old version of Lagger. Please update it from http://code.google.com/p/lagger');
		}
		else if(SERVER_VERSION > CLIENT_VERSION) {
			alert('PHP Console FAILED: You\'re using old version of "PHP Console" extension. Please update it');
		}
	}
}

function isEnabledOnClient() {
	return new RegExp(';\\s*phpcslc=' + CLIENT_VERSION + ';').exec(';' + document.cookie + ';') || false;
}

function setEnabledOnClient() {
	document.cookie = 'phpcslc=' + CLIENT_VERSION + '; path=/;';
	window.location = document.location;
}

function getMessagesFromCookies() {
	if (!isActive) {
		return true;
	}
	var regexp = new RegExp(';\\s*(phpcsl_(.*?))=([^;]+)', 'g');
	var _messages = [];
	var _order = [];
	for(var i=10; i; i--) {
		while ((m = regexp.exec(';' + document.cookie + ';')) != null) {
			var k = parseInt(m[2]);
			eval('_messages[k] = ' + decodeURIComponent(m[3]).replace(/\+/g, ' '));
			document.cookie = m[1] + '=0; expires=Thu, 01-Jan-70 00:00:01 GMT;path=/;';
			_order.push(k);
		}
	}
	_order.sort();
	var _messagesToShow = [];
	for ( var i in _order) {
		var _m = _messages[_order[i]];
		for ( var k in _m) {
			var message = _m[k];
			sendToConsole(message);
			if (message.notify) {
				_messagesToShow.push(message);
			}
		}
	}
	if(_messagesToShow.length) {
		showNotifications(_messagesToShow);
	}
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

function showNotifications(messages) {
	chrome.extension.sendRequest( {
		showNotifications : messages
	});
}
