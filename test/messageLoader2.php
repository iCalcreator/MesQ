<?php
/**
 * MesQ, PHP disk based message lite queue manager
 *
 * Copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * Link      https://kigkonsult.se
 * Package   MesQ
 * Version   1.0
 * License   LGPL
 *
 * This file is a part of MesQ.
 *
 * This php script generate test messages
 * messageLoader.php
 *
 * Produce 4 kB+ messages, TestMessage class instances
 *
 * Usage
 * php -f /path/to/MesQ/test/messageLoader.php arg1 arg2 arg3
 *
 * arguments :
 * 0 : '/path/to/MesQ/test/messageLoader.php'
 * 1 : queueName
 * 2 : directory
 * 3 : startIndex
 * 4 : number of messages to generate
 * 5 : queue type, FIFO default
 */
declare( strict_types = 1 );
namespace Kigkonsult\MesQ;

use function in_array;
use function getmypid;
use function intval;
use function microtime;
use function rand;
use function realpath;
use function sprintf;

include realpath( '../autoload.php' );
include realpath( './test.inc.php' );

static $FMT1 = 'pid %d %s : message %s%s';
static $FMT2 = 'pid %d : created %d messages in %s sec%s';

// load args and prepare config
list( $queueName, $directory ) = getArgv1and2( $argv );
$config    = [ MesQ::QUEUENAME => $queueName, MesQ::DIRECTORY => $directory ];
$queueType = ( isArgSet( $argv, 5 ) &&
    in_array( $argv[5], [ MesQ::FIFO, MesQ::LIFO, MesQ::PRIO ] ))
    ? $argv[5]
    : MesQ::FIFO;
$config[MesQ::QUEUETYPE] = $queueType;
$start   = isArgSet( $argv, 3 ) ? intval( $argv[2] ) : 3;
$count   = isArgSet( $argv, 4 ) ? intval( $argv[4] ) : 1000;
// set up
$time    = microtime( true );
$pid     = getmypid();
$payload = generateRandomString( 4096 ); // 2048 );
$prio    = null;
// load !!
for( $x1 = 1; $x1 <= $count; $x1++ ) {
    $testMsg = TestMessage::factory( $start++, $payload );
    if( MesQ::PRIO == $queueType ) {
        $prio = rand( 0, 9 );
        $testMsg->setPriority( $prio );
    }
    MesQ::qPush( $config, $testMsg, $prio );
    echo sprintf( $FMT1, $pid, getTime( $time ), $testMsg->ToString(), PHP_EOL );
} // end for
echo sprintf( $FMT2, $pid, $count, getTime( $time ), PHP_EOL );
