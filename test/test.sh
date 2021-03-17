#!/bin/bash
#
# MesQ, PHP lite disk based message queue manager
#
# Copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
# Link      https://kigkonsult.se
# Package   MesQ
# Version   1.05
# License   LGPL
#
# This file is a part of MesQ.
#
# MesQ FIFO test, 9000 (each 4kB+ object) messages
# The test is a 'worst case scenario',
#   creating messages asap in 9 concurrent processes
#   starting return messages before all are inserted in queue
# testDir (below) is the (temp) message storage, empty after successful exec
#
# usage :
#
# Prepare and save this script : queueName, directory, queueType etc
# Open a command window
# cd /path/to/MesQ/test
# ./test.sh
#
# review results in load.log, read.log and log.err, truncated before exec
#

queueName='incoming'
testDir='/path/to/queueDirectory'
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

# start up 8 loaders using the push method, asap
php -f messageLoader.php $queueName $testDir 1000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 2000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 3000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 4000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 5000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 6000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 7000 1000 $queueType >>load.log 2>>log.err &
php -f messageLoader.php $queueName $testDir 8000 1000 $queueType >>load.log 2>>log.err &
# start up the 9th loader using the qPush method
php -f messageLoader2.php $queueName $testDir 9000 1000 $queueType >>load.log 2>>log.err &

# read PRIO messages, in chunks of max 10, return max 10000, prio 0-9
# php -f messageReader.php $queueName $testDir 10 10000 0 9 >>read.log 2>>log.err

# read PRIO messages, in chunks of max 1000, return max 10000, prio 7-9
# php -f messageReader.php $queueName $testDir 1000 10000 7 9 >>read.log 2>>log.err

# read 100 messages and then quit
# php -f messageReader.php $queueName $testDir 0 100 >>read.log 2>>log.err

# read messages in chunks of max 10, return all (ex for LIFO/PRIO)
php -f messageReader.php $queueName $testDir 10 >>read.log 2>>log.err

## you may have to fire of this later due to to short latency
## If fired of in the command window, expand $queueName $testDir first
# read ALL messages regardless of queueType
# php -f messageReader.php $queueName $testDir >>read.log 2>>log.err
