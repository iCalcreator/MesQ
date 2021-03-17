[comment]: # (This file is part of MesQ, PHP disk based message lite queue manager. Copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved, licence LGPLv3)

# MesQ

## About

MesQ is a PHP lite disk based message queue manager

* Assemple and queue (un-)frequently (incoming) messages
* process once or scheduled

MesQ  supports FIFO, LIFO and PRIOrity message queues

Message can be any of scalar, array or object types
  

## Usage

Requires (below)
* unique \<queueName>
* existing read-/writeable \<directory>


For the MesQ config keys, please review src/MesQinterface.php.

#### Add single message to queue

``` php
<?php
namespace Kigkonsult\MesQ;

require 'vendor/autoload.php';

...
$config = [
    MesQ::QUEUENAME => <queueName>,
    MesQ::DIRECTORY => <directory>,
    MesQ::QUEUETYPE => MesQ::FIFO, // default
];

MesQ::qPush( $config, <message> );
...
```

#### Add messages to queue

``` php
<?php
namespace Kigkonsult\MesQ;

require 'vendor/autoload.php';

...
$mesQ = MesQ::factory( 
    [
        MesQ::QUEUENAME => <queueName>,
        MesQ::DIRECTORY => <directory>
    ];
);
foreach( <messages> as $message ) {
    $mesQ->push( $message );
} // end foreach
...
```

#### Process queued messages

``` php
<?php
namespace Kigkonsult\MesQ;

require 'vendor/autoload.php';

...
$config = [
    MesQ::QUEUENAME       => <queueName>,
    MesQ::DIRECTORY       => <directory>,
    MesQ::RETURNCHUNKSIZE => 1000
];

$mesQ = MesQ::factory( $config );
if( $mesQ->messageExists() {
    while( $message = $mesQ->getMessage()) {
        ...
        // process message, max 1000
        ...
    } // end while
} // end if

...
```

For more detailed usage, read [MesQ] doc, ReleaseNotes and for test, review test/test.sh. 

## Installation

[Composer], from the Command Line:

```
composer require kigkonsult/mesq
```

In your composer.json:

``` json
{
    "require": {
        "kigkonsult/mesq": "dev-master"
    }
}
```

## Sponsorship
Donation using [paypal.me/kigkonsult] are appreciated.
For invoice, [e-mail]</a>.

## Licence

MesQ is licensed under the LGPLv3 License.

[Composer]:https://getcomposer.org/
[e-mail]:mailto:ical@kigkonsult.se
[MesQ]:docs/MesQ.md
[paypal.me/kigkonsult]:https://paypal.me/kigkonsult
