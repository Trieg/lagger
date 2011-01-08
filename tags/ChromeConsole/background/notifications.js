function NotificationsHandler(options) {

	var notifies = [];
	var current = null;
	var timeout = null;
	var firstDelay = 3000;
	var limit = 50;
	var lockTimeout = false;
	var self = this;

	chrome.extension.onRequest.addListener(function(request, sender, sendResponse) {
		if (request.showNotifications) {
			showNotifications(request.showNotifications);
		}
		else if (request.changeTimeout) {
			if (request.changeTimeout.stop) {
				lockTimeout = true;
				stopTimeout();
			}
			else {
				lockTimeout = false;
				startTimeout();
			}
		}
	});

	function displayNotification(message) {
		var query = '?';
		for ( var k in message) {
			query = query + encodeURIComponent(k) + '=' + encodeURIComponent(message[k]) + '&';
		}
		var notifyPageURL = chrome.extension.getURL('notify.html') + query;
		var notification = webkitNotifications.createHTMLNotification(notifyPageURL);

		notifies.push({
			notification : notification,
			message : message
		});
		var i = notifies.length - 1;
		notification.onclose = function(a) {
			if (timeout) {
				self.clearAllNotifications();
			}
			else {
				handleNextNotification(false);
			}
		};
		return notification;
	}

	function clearNotification(i) {
		if (i == current) {
			stopTimeout();
			current = null;
		}
		if (notifies[i]) {
			if (notifies[i].notification) {
				notifies[i].notification.cancel();
			}
			delete notifies[i];
		}
	}

	self.clearAllNotifications = function() {
		for ( var k in notifies) {
			clearNotification(k);
		}
	};

	function handleNextNotification(noTimeout) {
		if (current) {
			return;
		}
		for ( var i in notifies) {
			if (notifies[i]) {
				current = i;
				if (!noTimeout) {
					startTimeout();
				}
				return;
			}
		}
	}

	function startTimeout() {
		if (lockTimeout) {
			return;
		}
		var i = current;
		if (notifies[i]) {
			timeout = setTimeout(function() {
				stopTimeout();
				if (lockTimeout) {
					return;
				}
				clearNotification(i);
			}, (notifies[i].message.notifyDelay ? notifies[i].message.notifyDelay : options.notifyDelay) * 1000);
		}
	}

	function stopTimeout() {
		if (timeout) {
			clearTimeout(timeout);
			timeout = null;
		}
	}

	self.showNotifications = function(messages) {
		var notifications = [];
		for ( var i in messages) {
			notifications.push(displayNotification(messages[i]));
			if (notifications.length > limit) {
				break;
			}
		}
		for ( var i in notifications) {
			notifications[i].show();
		}
		stopTimeout();
		setTimeout(function() {
			handleNextNotification(false);
		}, 3000);
	};

	self.showNotification = function(message) {
		self.showNotifications([ message ]);
	};

	function onExtensionRequest(request, sender, sendResponse) {
		if (request.showNotification) {
			self.showNotification(request.message);
		}
	}

	// construct

	chrome.extension.onRequest.addListener(onExtensionRequest);
}
