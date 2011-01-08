function Menu(options) {

	var rootItems = [];
	var isHidden = false;
	var self = this;

	self.show = function() {
		if (isHidden) {
			for ( var i in rootItems) {
				chrome.contextMenus.update(rootItems[i], {
					'contexts' : [ 'all' ]
				});
			}
			isHidden = false;
		}
	};

	self.hide = function() {
		if (!isHidden) {
			for ( var i in rootItems) {
				chrome.contextMenus.update(rootItems[i], {
					'contexts' : [ 'audio' ]
				});
			}
			isHidden = true;
		}
	};

	function changeOption(optionName, info, value) {
		if (info.checked != info.wasChecked) {
			if (optionName == 'enabled') {
				options.set(optionName, value);
			}
			else if (optionName == 'notifyDelay') {
				options.set(optionName, value);
			}
			else {
				options.set(optionName, info.checked);
			}
		}
	}

	rootItems.push(chrome.contextMenus.create({
		'title' : 'Enabled',
		'type' : 'radio',
		'checked' : options.enabled,
		'onclick' : function(info) {
			changeOption('enabled', info, true);
		}
	}));
	rootItems.push(chrome.contextMenus.create({
		'title' : 'Disabled',
		'type' : 'radio',
		'checked' : !options.enabled,
		'onclick' : function(info) {
			changeOption('enabled', info, false);
		}
	}));

	var consoleMI = chrome.contextMenus.create({
		'title' : 'Console'
	});
	rootItems.push(consoleMI);
	chrome.contextMenus.create({
		'title' : 'Log debug',
		'type' : 'checkbox',
		'checked' : options.consoleDebug,
		'parentId' : consoleMI,
		'onclick' : function(info) {
			changeOption('consoleDebug', info);
		}
	});
	chrome.contextMenus.create({
		'title' : 'Log errors',
		'type' : 'checkbox',
		'checked' : options.consoleErrors,
		'parentId' : consoleMI,
		'onclick' : function(info) {
			changeOption('consoleErrors', info);
		}
	});

	var notificationsMI = chrome.contextMenus.create({
		'title' : 'Notificaions'
	});
	rootItems.push(notificationsMI);
	var notificationsDelayMI = chrome.contextMenus.create({
		'title' : 'Delay',
		'parentId' : notificationsMI
	});
	chrome.contextMenus.create({
		'title' : 'Notify debug',
		'type' : 'checkbox',
		'checked' : options.notifyDebug,
		'parentId' : notificationsMI,
		'onclick' : function(info) {
			changeOption('notifyDebug', info);
		}
	});
	chrome.contextMenus.create({
		'title' : 'Notify errors',
		'type' : 'checkbox',
		'checked' : options.notifyErrors,
		'parentId' : notificationsMI,
		'onclick' : function(info) {
			changeOption('notifyErrors', info);
		}
	});

	function setDelayFunc(delay) {
		return function(info) {
			changeOption('notifyDelay', info, delay);
		};
	}
	var delays = [ 1, 2, 3, 5, 10 ];
	for ( var i in delays) {
		chrome.contextMenus.create({
			'title' : (delays[i] + ' ' + (delays[i] > 1 ? 'seconds' : 'second')),
			'type' : 'radio',
			'parentId' : notificationsDelayMI,
			'checked' : options.notifyDelay == delays[i],
			'onclick' : setDelayFunc(delays[i])
		});
	}

	function openUrlInNewTab(url) {
		chrome.tabs.create({
			'url' : url
		}, function() {
		});
	}
	rootItems.push(chrome.contextMenus.create({
		'type' : 'separator'
	}));
	var aboutMI = chrome.contextMenus.create({
		'title' : 'About'
	});
	rootItems.push(aboutMI);
	var links = {
		Homepage : 'https://chrome.google.com/extensions/detail/nfhmhhlpfleoednkpnnnkolmclajemef',
		Lagger : 'http://code.google.com/p/lagger',
		Author : 'linkedin.com/in/barbushin',
		Donation : 'http://web-grant.com/donation/php_console'
	};
	for ( var title in links) {
		chrome.contextMenus.create({
			'title' : title,
			'parentId' : aboutMI,
			'onclick' : function() {
				openUrlInNewTab(links[title]);
			}
		});
	}

	self.hide();
}