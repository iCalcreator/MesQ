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
 */
namespace Kigkonsult\MesQ;

Interface MesQinterface
{
    /**
     * Queue config keys
     *
     * Unknown used config keys will raise an InvalidArgumentException
     */

    /**
     * Queue name and directory config keys
     * Mandatory
     * Make sure there is sufficient capacity available in directory !!
     */
    public const QUEUENAME = 'queueName';
    public const DIRECTORY = 'directory';

    /**
     * Queue type key config constant
     */
    public const QUEUETYPE = 'queueType';

    /**
     * Queue type value config constants
     *
     * FIFO : First In First Out, default
     * LIFO : Last In First Out
     * PRIO : Highest Priority First Out, prio value int 0-9, 0-lowest, 9-highest
     */
    public const FIFO = 'FIFO';
    public const LIFO = 'LIFO';
    public const PRIO = 'PRIO';

    /**
     * The config key for the iterating max amount of read messages (file names) chunk size
     * Default PHP_INT_MAX
     * For LIFO/PRIO queues and higher frequence of incoming messages,
     * use a smaller value (10?)
     */
    public const READCHUNKSIZE = 'readChunkSize';

    /**
     * The config key for the return max number of messages
     * Default PHP_INT_MAX
     * Should (must) be set in config or using the method setReturnChunkSize()
     * OR the PHP process script may continue eternally (or until PHP exec timeout)
     */
    public const RETURNCHUNKSIZE = 'returnChunkSize';

    /**
     * In method getConfig() only
     *   time, float (STARTTIME)
     *   'YmdHis' string (DATE)
     */
    public const STARTTIME = 'startTime';
    public const DATE      = 'date';
}
