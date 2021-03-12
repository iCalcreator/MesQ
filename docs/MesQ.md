[comment]: # (This file is part of MesQ, PHP disk based message lite queue manager. Copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved, licence LGPLv3)

# MesQ methods

#### Construct, factory and singleton methods

The constructor method is private

```
factory( queueName [, directory ] )
```
* MesQ (static) factory method
* ```queueName``` _string_  unique
* ```queueName``` _array_  config, please review MeeQinterface.php for details
* ```directory``` _string_  existing read-/writeable directory
* Return _MesQ_ class instance
* Throws _InvalidArgumentException_
* _static_


```
singleton( queueName [, directory ] )
```
* MesQ (static) singleton getInstance method, based on the unique _queueName_/_directory_ 
* ```queueName``` _string_  unique
* ```queueName``` _array_  config, please review MesQinterface.php for details
* ```directory``` _string_  existing read-/writeable directory
* Return _MesQ_ class instance
* Throws _InvalidArgumentException_
* _static_

#### Config methods

```
getConfig( [ key ] )
```
* ```key``` _string_  please review MesQinterface.php for details
* Return _array_ config key/value pairs or key _int_|_string_ value, please review MesQinterface.php for details

```
configToString()
```
* Return _string_, nicely rendered config key/value pairs

#### Add messages to queue, logic methods

```
qPush( config, message [, priority ] )
```
* One-liner, insert single message to queue
* ```config``` _array_  config, please review MeeQinterface.php for details
* ```message``` _mixed_  The Message
* ```priority``` _int_, 0-9, 0-lowest, 9-highest, PRIOrity queue type only
* _static_
* Throws _InvalidArgumentException_, _RuntimeException_

```
push( message [, priority ] )
```
* ```mixed``` _mixed_  The Message
* ```priority``` _int_, 0-9, 0-lowest, 9-highest, PRIOrity queue type only
* Throws _InvalidArgumentException_, _RuntimeException_

```
insert( message [, priority ] )
```
* _push_ method alias

#### Fetch messages from queue, logic methods

```
messageExist( [ priority ] )
```
* ```priority``` _int_, 0-9, 0-lowest, 9-highest, PRIOrity queue type only
* ```priority``` _array_, \[ min, max ], PRIOrity queue type only
* Return _bool_ true if message(s) exists, false not
* Throws _RuntimeException_

```
getMessage(  [ priority ] )
```
* For queue type PRIO, messages are returned in priority order, also without arg
* ```priority``` _int_, 0-9, 0-lowest, 9-highest, PRIOrity queue type only
* ```priority``` _array_, \[ min, max ], PRIOrity queue type only
* Return _mixed_,  message or _bool_ false if no message found (i.e. end-of-queue)
* Throws _RuntimeException_

```
pull(  [ priority ] )
```
* _getMessage_ method alias

#### Queue size methods

```
getQueueSize(  [ priority ] )
```
* ```priority``` _int_, 0-9, 0-lowest, 9-highest, PRIOrity queue type only
* ```priority``` _array_, \[ min, max ], PRIOrity queue type only
* Return _int_ the queue size in number of messages (now), _bool_ false on error

```
size(  [ priority ] )
```
* _getQueueSize_ method alias

```
getDirectorySize()
```
* Return _int_, the directory size in bytes (now) 

#### Misc

If the 'Fetch messages from queue'-PHP script is placeced in cron,
you should implement some file-locking logic
to prevent concurrent processes running.
Ex: https://www.php.net/manual/en/function.flock.php#117162

You should always set the 'RETURNCHUNKSIZE' config key
otherwise the 'Fetch messages from queue'-PHP script may
continue forever i.e. until PHP exec script timeout.

---
Go to [README]

[README]:../README.md
