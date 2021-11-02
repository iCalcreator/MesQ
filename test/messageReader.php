<?php
/**
 * MesQ, lite PHP disk based message queue manager
 *
 * This file is a part of MesQ.
 *
 * @author    Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * @link      https://kigkonsult.se
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
use function microtime;
use function sprintf;

include '../vendor/autoload.php';
include './test.inc.php';

static $FMT1 = 'read PRIO %s%s';
static $FMT2 = 'Tot  count msgs   : %s%s';
static $FMT3 = 'Prio count msgs   : %s%s';
static $FMT4 = 'Dir size (bytes)  : %d%s';
static $FMT7 = 'pid %d %s : message %s%s';
static $FMT8 = 'pid %s : %sread %d messages in %s sec%s';
static $WAIT = 'wait for any prio message';
static $SP0  = '';
static $TTL  = 'total ';

// load args
[ $queueName, $directory ] = getArgv1and2( $argv );
$config = [ MesQ::QUEUENAME => $queueName, MesQ::DIRECTORY => $directory ];
if( isArgSet( $argv, 3 )) {
    $config[MesQ::READCHUNKSIZE] = (int)$argv[3];
}
if( isArgSet( $argv, 4 )) {
    $config[MesQ::RETURNCHUNKSIZE] = (int)$argv[4];
}
// set up
$time = microtime( true );
$pid  = getmypid();
$prio = null;
$mesq = MesQ::singleton( $config );
if( isArgSet( $argv, 5 )) {
    $prio = (int)$argv[5];
    if( isArgSet( $argv, 6 )) {
        $prio = [ $prio, (int)$argv[6] ]; // PRIO
    }
    $mesq->setQueueType( MesQ::PRIO );
}
echo $mesq->configToString() . PHP_EOL;
// check fot opt priority messages
$cnt  = $cnt2 = 0;
$time2 = $time;
if( $mesq->isQueueTypePrio()) {
    echo sprintf( $FMT1, ( is_array($prio ) ? $prio[0] . '-' . $prio[1] : $prio ), PHP_EOL );
    while( ! $mesq->messageExist( $prio )) {
        if( true !== time_nanosleep( 0, 10000000 )) { // 0.01 sec
            wait( 1 );
        }
        echo $WAIT . PHP_EOL; // test
    }
}
echo sprintf( $FMT2, var_export( $mesq->size(), true ), PHP_EOL ); // may return false
if( $mesq->isQueueTypePrio()) {
    echo sprintf( $FMT3, var_export( $mesq->size( $prio ), true ), PHP_EOL ); // may return false
}
echo sprintf( $FMT4, $mesq->getDirectorySize(), PHP_EOL );
// wait some time
wait( 1 );
// retrieve messages
while( $message = $mesq->getMessage( $prio )) {
    ++$cnt;
    echo sprintf( $FMT7, $pid, getTime( $time ), $message->toString(), PHP_EOL );
    ++$cnt2;
    if( 0 === ( $cnt % 1000 )) {
        echo sprintf( $FMT8, $pid, $SP0, $cnt2, getTime( $time2 ), PHP_EOL );
        $time2 = microtime( true );
        $cnt2  = 0;
    }
    /**/
    // should emulate some php logic here, wait 10000000 (0.01) sec,
    // wait();
    /**/
} // end while
echo sprintf( $FMT8, $pid, $TTL, $cnt, getTime( $time ), PHP_EOL );

function wait( ? int $sec = 0 ) : void
{
    if( true !== time_nanosleep( $sec, 10000000 )) {
        sleep( $sec );
    }
}
