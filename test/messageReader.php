<?php
/**
 * MesQ, lite PHP disk based message queue manager
 *
 * This file is a part of MesQ.
 *
 * @author    Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * @link      https://kigkonsult.se
 * @version   1.2
 * @license   Subject matter of licence is the software MesQ.
 *            The above copyright, link, package and version notices,
 *            this licence notice shall be included in all copies or
 *            substantial portions of the MesQ.
 *
 *            MesQ is free software: you can redistribute it and/or modify
 *            it under the terms of the GNU Lesser General Public License as
 *            published by the Free Software Foundation, either version 3 of
 *            the License, or (at your option) any later version.
 *
 *            MesQ is distributed in the hope that it will be useful,
 *            but WITHOUT ANY WARRANTY; without even the implied warranty of
 *            MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *            GNU Lesser General Public License for more details.
 *
 *            You should have received a copy of the
 *            GNU Lesser General Public License
 *            along with MesQ.
 *            If not, see <https://www.gnu.org/licenses/>.
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
 */
declare( strict_types = 1 );
namespace Kigkonsult\MesQ;

use function getmypid;
use function intval;
use function microtime;
use function realpath;
use function sprintf;

include realpath( '../vendor/autoload.php' );
include realpath( './test.inc.php' );

static $FMT1 = 'pid %d %s : message %s%s';
static $FMT2 = 'pid %s : %sread %d messages in %s sec%s';
static $WAIT = 'wait for any prio message';
static $SP0  = '';
static $TTL  = 'total ';

// load args
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
$mesq = MesQ::singleton( $config );
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
    }
}
echo 'count messages : ' . var_export( $mesq->size(), true ) . PHP_EOL; // test ###
// echo 'count messages : ' . $mesq->size() . PHP_EOL;
echo 'count bytes    : ' . $mesq->getDirectorySize() . PHP_EOL;
// retrieve messages
while( $message = $mesq->getMessage( $prio )) {
    $cnt  += 1;
    echo sprintf( $FMT1, $pid, getTime( $time ), $message->toString(), PHP_EOL );
    $cnt2 += 1;
    if( 0 == ( $cnt % 1000 )) {
        echo sprintf( $FMT2, $pid, $SP0, $cnt2, getTime( $time2 ), PHP_EOL );
        $time2 = microtime( true );
        $cnt2  = 0;
    }
    /*
    // should emulate some php logic here, wait 10000000 (0.01) sec,
    if( true !== time_nanosleep( 0, 10000000 )) {
        sleep( 1 );
    }
    */
} // end while
echo sprintf( $FMT2, $pid, $TTL, $cnt, getTime( $time ), PHP_EOL );
