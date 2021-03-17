<?php
/**
 * MesQ, PHP disk based message lite queue manager
 *
 * Copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * Link      https://kigkonsult.se
 * Package   MesQ
 * Version   1.05
 * License   Subject matter of licence is the software MesQ.
 *           The above copyright, link, package and version notices,
 *           this licence notice shall be included in all copies or
 *           substantial portions of the MesQ.
 *
 *           MesQ is free software: you can redistribute it and/or modify
 *           it under the terms of the GNU Lesser General Public License as
 *           published by the Free Software Foundation, either version 3 of
 *           the License, or (at your option) any later version.
 *
 *           MesQ is distributed in the hope that it will be useful,
 *           but WITHOUT ANY WARRANTY; without even the implied warranty of
 *           MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *           GNU Lesser General Public License for more details.
 *
 *           You should have received a copy of the
 *           GNU Lesser General Public License
 *           along with MesQ.
 *           If not, see <https://www.gnu.org/licenses/>.
 *
 * This file is a part of MesQ.
 */
namespace Kigkonsult\MesQ;

Interface MesQinterface
{
    /**
     * Queue config keys
     *
     * Unknown used config keys are ignored
     */

    /**
     * Queue name and directory config keys
     * Mandatory
     * Make sure there is sufficient capacity available in directory !!
     */
    const QUEUENAME = 'queueName';
    const DIRECTORY = 'directory';

    /**
     * Queue type key config constant
     */
    const QUEUETYPE = 'queueType';

    /**
     * Queue type value config constants
     *
     * FIFO : First In First Out, default
     * LIFO : Last In First Out
     * PRIO : Highest Priority First Out, prio value int 0-9, 0-lowest, 9-highest
     */
    const FIFO = 'FIFO';
    const LIFO = 'LIFO';
    const PRIO = 'PRIO';

    /**
     * The config key for the iterating max amount of read messages (file names) chunk size
     * Default PHP_INT_MAX
     * For LIFO/PRIO queues and higher frequence of incoming messages,
     * use a smaller value (10?)
     */
    const READCHUNKSIZE = 'readChunkSize';

    /**
     * The config key for the return max number of messages
     * Default PHP_INT_MAX
     * Should (must) be set in config or using the method setReturnChunkSize()
     * OR the PHP process script may continue eternally (or until PHP exec timeout)
     */
    const RETURNCHUNKSIZE = 'returnChunkSize';

    /**
     * In method getConfig() only
     *   process pid number
     *   time, float (STARTTIME) and 'YmdHis' string (DATE)
     */
    const PID       = 'pid';
    const STARTTIME = 'startTime';
    const DATE      = 'date';
}
