if(isEnabled()) {
	focused=true;
	window.onfocus = function() { focused=true; };
	window.onblur = function() { focused=false; };
	
	setInterval(getDebugMessagesFromCookies, 1000);
}

function isEnabled() {
	return new RegExp(';\\s*phd=1;').exec(';' + document.cookie + ';') || false;
}

function getDebugMessagesFromCookies() {
	if(!focused) {
		return true;
	}
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
		document.cookie = _messages[_order[i]].cookie + '=; expires=Thu, 01-Jan-70 00:00:01 GMT;path=/;';
		var message = _messages[_order[i]];
		if(message.type == 'error') {
			console.error(message.text);
		}
		else {
			console.log(message.text);
		}
	}
}