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
 * 1 : directory
 * 2 : startIndex
 * 3 : number of messages to generate
 * 4 : queue type, FIFO default
 */
declare( strict_types = 1 );
namespace Kigkonsult\MesQ;

use function in_array;
use function getmypid;
use function microtime;
use function random_int;
use function sprintf;

include '../vendor/autoload.php';
include './test.inc.php';

static $FMT1 = 'pid %d %s : message %s%s';
static $FMT2 = 'pid %d : created %d messages in %s sec%s';

// load args
[ $queueName, $directory ] = getArgv1and2( $argv );
$start   = isArgSet( $argv, 3 ) ? (int)$argv[2] : 3;
$count   = isArgSet( $argv, 4 ) ? (int)$argv[4] : 1000;
$queueType = ( isArgSet( $argv, 5 ) &&
    in_array( $argv[5], [ MesQ::FIFO, MesQ::LIFO, MesQ::PRIO ], true ) )
    ? $argv[5]
    : MesQ::FIFO;
// set up
$time    = microtime( true );
$pid     = getmypid();
$payload = generateRandomString( 4096 ); // 2048 );
$mesq    = MesQ::factory( $queueName, $directory )
    ->setQueueType( $queueType );
echo $mesq->configToString() . PHP_EOL;
$prio    = null;
// load !!
for( $x1 = 1; $x1 <= $count; $x1++ ) {
    $testMsg = TestMessage::factory( $start++, $payload );
    if( MesQ::PRIO === $queueType ) {
        $prio = random_int( 0, 9 );
        $testMsg->setPriority( $prio );
    }
    $mesq->push( $testMsg, $prio );
    echo sprintf( $FMT1, $pid, getTime( $time ), $testMsg->toString(), PHP_EOL );
}
echo sprintf( $FMT2, $pid, $count, getTime( $time ), PHP_EOL );
