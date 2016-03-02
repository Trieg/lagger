## Important ##
There is also GIT hosted version of this library, see <a href='https://github.com/barbushin/lagger'>Lagger on GitHub</a>.

## Features ##

There are 3 event handlers classes:

  * Lagger\_Handler\_Errors - to handle PHP-system errors (including FATAL)
  * Lagger\_Handler\_Exceptions - to handle exceptions
  * Lagger\_Handler\_Debug - to handle custom debug messages

There are 7 classes of actions that can be maked on handling some event:

  * Lagger\_Action\_Print - send messages to STDOUT
  * Lagger\_Action\_Email - send Email
  * Lagger\_Action\_Sms - send SMS
  * Lagger\_Action\_FileLog - write to log-file
  * Lagger\_Action\_Exception - throw Exception
  * Lagger\_Action\_ChromeConsole - send messages to Google Chrome console by <a href='https://chrome.google.com/webstore/detail/nfhmhhlpfleoednkpnnnkolmclajemef'>PHP Console</a> extension
  * Lagger\_Action\_WinSpeak - speak message (just for fun, work on Windows servers)

And some other important features:

  * Ignoring handling of repeated(same) events (by Lagger\_Skiper)
  * Using templates to define actions messages
  * Defining tags for events and configuring handlers actions to catch events of specific tags
  * Reconfiguring handlers dynamicaly by specific GET request (using Lagger\_Tagger)
  * Handling internal errors
  * Just 20kb of 100% OOP source code

If you have any ideas or issues about Lagger, please post them to:
  * <a href='http://code.google.com/p/lagger/issues/list'>Issues list</a>

Everybody who use this library, don't forget to subscribe on updates by RSS:
  * <a href='http://code.google.com/feeds/p/lagger/downloads/basic'>New releases</a>
  * <a href='http://code.google.com/feeds/p/lagger/svnchanges/basic'>SVN Source Changes</a>
  * <a href='http://code.google.com/feeds/p/lagger/updates/basic'>All updates</a>



### Recommended ###
  * Google Chrome extension <a href='http://goo.gl/b10YF'>PHP Console</a>.
  * Google Chrome extension <a href='http://goo.gl/kNix9'>JavaScript Errors Notifier</a>.