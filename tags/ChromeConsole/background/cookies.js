function CookiesHandler(options, messagesHandler) {	var messagesQueue = [];	var messagesQueueHandling = false;	var messagesHandler;	var cookieNameReg = new RegExp('^phpcsl_');	function onCookieChanged(info) {		if (!info.removed) {			var cookie = info.cookie;			if (cookieNameReg.exec(cookie.name)) {				if (options.enabled) {					var messages = JSON.parse(decodeURIComponent(cookie.value.replace(/\+/g, ' ')));					for ( var i in messages) {						messagesQueue.push(messages[i]);					}				}				chrome.cookies.remove({					url : ((cookie.secure ? 'https://' : 'http://') + cookie.domain + cookie.path),					name : cookie.name,					storeId : cookie.storeId				});			}		}	}	function handleWaitingQueue() {		if (!messagesQueueHandling) {			messagesQueueHandling = true;			if (messagesQueue.length) {				var messages = messagesQueue;				messagesQueue = [];				messagesHandler.handleMessages(messages);			}			messagesQueueHandling = false;		}	}	chrome.cookies.onChanged.addListener(onCookieChanged);	setInterval(handleWaitingQueue, 300);};