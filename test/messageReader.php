<?php
/**
 * MesQ, PHP disk based message lite queue manager
 *
 * Copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * Link      https://kigkonsult.se
 * Package   MesQ
 * Version   1.05
 * License   LGPL
 *
 * This file is a part of MesQ.
 *
 * This php script read test messages
 *
 * messageReader.php
 * Usage
 * php -f /path/to/MesQ/test/messageReader.php arg1 arg2 arg3
 *
 * arguments :
 * 0 : '/path/to/MesQ/test/messageReader.php'
 * 1 : directory
 * 2 : read chunk size
 * 3 : return chunk size
 * 4 : opt prio, single or min, force queueType to PRIO
 * 5 : opt prio max, only if min is set
 */
declare( strict_types = 1 );
namespace Kigkonsult\MesQ;

use function getmypid;
use function intval;
use function microtime;
use function realpath;
use function sprintf;

include realpath( '../autoload.php' );
include realpath( './test.inc.php' );

static $FMT1 = 'pid %d %s : message %s%s';
static $FMT2 = 'pid %s : %sread %d messages in %s sec%s';
static $WAIT = 'wait for any prio message';
static $FMT4 = 'count messages : ';
static $FMT5 = 'count bytes    : ';
static $SP0  = '';
static $TTL  = 'total ';

// load args and prepare config
list( $queueName, $directory ) = getArgv1and2( $argv );
$config = [ MesQ::QUEUENAME => $queueName, MesQ::DIRECTORY => $directory ];
if( isArgSet( $argv, 3 )) {
    $config[MesQ::READCHUNKSIZE] = intval( $argv[3] );
}
if( isArgSet( $argv, 4 )) {
    $config[MesQ::RETURNCHUNKSIZE] = intval( $argv[4] );
}
// set up
$time = microtime( true );
$pid  = getmypid();
$prio = null;
$mesq = MesQ::singleton( $config );
if( isArgSet( $argv, 5 )) {
    $prio = intval( $argv[5] );
    if( isArgSet( $argv, 6 )) {
        $prio = [ $prio, intval( $argv[6] ) ];
    }
    $mesq->setQueueType( MesQ::PRIO );
}
$mesq = MesQ::singleton( $config ); // test, should be factory method
echo $mesq->configToString() . PHP_EOL;
// check fot opt priority messages
$cnt  = $cnt2 = 0;
$time2 = $time;
if( $mesq->isQueueTypePrio()) {
    while( ! $mesq->messageExist( $prio )) {
        if( true !== time_nanosleep( 0, 10000000 )) { // 0.01 sec
            sleep( 1 );
        }
        echo $WAIT . PHP_EOL; // test
    } // end while
} // end if
echo $FMT4 . var_export( $mesq->size(), true ) . PHP_EOL; // test ###
echo $FMT5 . $mesq->GetDirectorySize() . PHP_EOL;
// retrieve messages
while( $message = $mesq->getMessage( $prio )) {
    $cnt  += 1;
    echo sprintf( $FMT1, $pid, getTime( $time ), $message->ToString(), PHP_EOL );
    $cnt2 += 1;
    if( 0 == ( $cnt % 1000 )) {
        echo sprintf( $FMT2, $pid, $SP0, $cnt2, getTime( $time2 ), PHP_EOL );
        $time2 = microtime( true );
        $cnt2  = 0;
        echo $FMT4 . var_export( $mesq->size(), true ) . PHP_EOL; // test ###
        echo $FMT5 . $mesq->GetDirectorySize() . PHP_EOL;
    } // end if
    /*
    // should emulate some php logic here, wait 10000000 (0.01) sec,
    if( true !== time_nanosleep( 0, 10000000 )) {
        sleep( 1 );
    }
    */
} // end while
echo sprintf( $FMT2, $pid, $TTL, $cnt, getTime( $time ), PHP_EOL );
