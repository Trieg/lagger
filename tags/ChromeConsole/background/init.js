function init() {

	var options = new Options();
	var menu = new Menu(options);
	var notificationsHandler = new NotificationsHandler(options);
	var messagesHandler = new MessagesHandler(options, notificationsHandler);
	var cookiesHandler = new CookiesHandler(options, messagesHandler);

	var clients = [];

	chrome.extension.onRequest.addListener(function(request, sender, sendResponse) {
		if (request.registerClient) {
			clients[sender.tab.id] = true;
		}
	});

	function updateMenuStatus(tabId) {
		if (clients[tabId]) {
			menu.show();
		}
		else {
			menu.hide();
		}
	}

	chrome.tabs.onSelectionChanged.addListener(updateMenuStatus);
	chrome.tabs.onUpdated.addListener(updateMenuStatus);

	chrome.tabs.onRemoved.addListener(function(tabId) {
		if (clients[tabId]) {
			delete clients[tabId];
		}
	});

	if (options.justUpdated || options.justInstalled) {
		notificationsHandler.showNotification({
			type : 'update',
			subject : 'PHP Console new version',
			text : 'PHP Console v' + options.get('version') + ' installed. See <a href="http://code.google.com/p/lagger/wiki/PHP_Console_changelog" target="_blank">changelog</a>.',
			source : 'don\'t forget to update <a href="http://code.google.com/p/lagger/" target="_blank">Lagger</a> to last version',
			notifyDelay : 10000
		});
	}
}