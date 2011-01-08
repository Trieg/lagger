function MessagesHandler(options, notificationsHandler) {

	var self = this;

	function sendMessagesToConsole(messages) {
		chrome.tabs.getSelected(null, function(tab) {
			chrome.tabs.sendRequest(tab.id, {
				sendMessagesToConsole : true,
				messages : messages
			});
		});
	}

	self.handleMessages = function(messages) {
		var consoleMessages = [];
		var notifyMessages = [];

		for ( var i in messages) {
			var message = messages[i];
			if ((options.consoleErrors && message.type == 'error') || (options.consoleDebug && message.type == 'debug')) {
				consoleMessages.push(messages[i]);
			}
			if ((options.notifyErrors && message.type == 'error') || (options.notifyDebug && message.type == 'debug')) {
				notifyMessages.push(messages[i]);
			}
		}
		if (consoleMessages.length) {
			sendMessagesToConsole(consoleMessages);
		}
		if (notifyMessages.length) {
			notificationsHandler.showNotifications(notifyMessages);
		}
	};
}