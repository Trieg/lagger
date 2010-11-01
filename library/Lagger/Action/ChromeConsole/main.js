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
	return new RegExp(';\\s*phpcsls=1;').exec(';' + document.cookie + ';') || false;
}

function isEnabledOnClient() {
	return new RegExp(';\\s*phpcslc=1;').exec(';' + document.cookie + ';') || false;
}

function setEnabledOnClient() {
	document.cookie = 'phpcslc=1; path=/;';
	document.location = document.location;
}

function isEnabled() {
	return new RegExp(';\\s*phpcsl=1;').exec(';' + document.cookie + ';') || false;
}

function getCookieVars(cookie) {
	var vars = {}, hash;
	var hashes = cookie.slice(cookie.indexOf('?') + 1).split('&');
	for ( var i = 0; i < hashes.length; i++) {
		hash = hashes[i].split('=');
		vars[decodeURIComponent(hash[0])] = decodeURIComponent(hash[1]);
	}
	return vars;
}

function getMessagesFromCookies() {
	if (!isActive) {
		return true;
	}
	var regexp = new RegExp(';\\s*(phpcsl_(.*?))=([^;]+)', 'g');
	var _messages = [];
	var _order = [];
	while ((m = regexp.exec(';' + document.cookie + ';')) != null) {
		eval('var _message = ' + decodeURIComponent(m[3]).replace(/\+/g, ' '));
		_message.cookie = m[1];
		_messages[m[2]] = _message;
		_order.push(m[2]);
	}
	_order.sort();
	for ( var i in _order) {
		var message = _messages[_order[i]];
		document.cookie = message.cookie + '=0; expires=Thu, 01-Jan-70 00:00:01 GMT;path=/;';
		delete message.cookie;
		sendToConsole(message);
		if (message.notify) {
			showNotification(message);
		}
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

function showNotification(message) {
	chrome.extension.sendRequest( {
		showNotification : message
	});
}
