#!/bin/bash
#
# MesQ, lite PHP disk based message queue manager
#
# This file is a part of MesQ.
#
# author    Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
# copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
# link      https://kigkonsult.se
# license   Subject matter of licence is the software MesQ.
#           The above copyright, link, package and version notices,
#           this licence notice shall be included in all copies or
#           substantial portions of the MesQ.
#
#           MesQ is free software: you can redistribute it and/or modify
#           it under the terms of the GNU Lesser General Public License as
#           published by the Free Software Foundation, either version 3 of
#           the License, or (at your option) any later version.
#
#           MesQ is distributed in the hope that it will be useful,
#           but WITHOUT ANY WARRANTY; without even the implied warranty of
#           MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#           GNU Lesser General Public License for more details.
#
#           You should have received a copy of the
#           GNU Lesser General Public License
#           along with MesQ.
#           If not, see <https://www.gnu.org/licenses/>.
#
# MesQ FIFO test, 9000 (each 4kB+ object) messages, usage :
#
# testDir (below) is the (temp) message storage, empty after successful exec
# Prepare this script : queueName, directory, queueType etc
# Open a command window
# cd /path/to/MesQ/test
# ./test.sh
#
# review results in load.log, read.log and log.err, truncated before exec
#

queueName='incoming'
# testDir='/path/to/queueDirectory'
testDir='/home/kig/projects/MesQ/test/testDir'
# FIFO, LIFO, PRIO (with random prio)
queueType='FIFO'

if [ ! -d  $testDir ];
then
  mkdir $testDir
fi
touch load.log read.log log.err
# reset logs
echo -n '' > load.log
echo -n '' > read.log
if [ -s log.err ];
then
    echo -n '' > log.err
fi

# start up 9 loaders, asap
php -f messageLoader.php $queueName $testDir 1000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 2000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 3000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 4000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 5000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 6000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 7000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 8000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 9000 1000 $queueType >>load.log 2>>log.err &

## you may have to fire of this later due to to short latency

## read all messages
# php -f messageReader.php $queueName $testDir >>read.log 2>>log.err

## read PRIO messages, in chunks of max 10, return max 10000, prio 0-9
php -f messageReader.php $queueName $testDir 10 10000 0 9 >>read.log 2>>log.err

## read PRIO messages, in chunks of max 10, return max 10000, prio 7-9
# php -f messageReader.php $queueName $testDir 10 10000 7 9 >>read.log 2>>log.err

## read 100 messages and then quit
# php -f messageReader.php $queueName $testDir 0 100 >>read.log 2>>log.err
