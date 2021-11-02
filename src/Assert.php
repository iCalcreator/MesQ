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
declare( strict_types = 1 );
namespace Kigkonsult\MesQ;

use InvalidArgumentException;

use function is_dir;
use function is_int;
use function is_readable;
use function is_writable;
use function sprintf;

class Assert
{
    /**
     * Assert directory
     *
     * @param string $queueName
     * @param string $directory
     * @throws InvalidArgumentException
     */
    public static function directory( string $queueName, string $directory ) : void
    {
        static $ERRFMTEMPTY = 'Queue \'%s\', directory can\'t be empty';
        static $ERRFMTISDIR = 'Queue \'%s\', directory %s not exists and/or is not a directory';
        static $ERRFMTWRTBL = 'Queue \'%s\', directory %s is not writeable/readable';
        if( empty( $directory )) {
            self::throwException( sprintf( $ERRFMTEMPTY, $queueName ), 111 );
        }
        if( ! @is_dir( $directory )) {
            self::throwException( sprintf( $ERRFMTISDIR, $queueName, $directory ), 112 );
        }
        if( ! @is_writable( $directory ) || ! @is_readable( $directory )) {
            self::throwException( sprintf( $ERRFMTWRTBL, $queueName, $directory ), 113 );
        }
    }

    /**
     * @param string $queueName
     * @throws InvalidArgumentException
     */
    public static function nonEmptyString( string $queueName ) : void
    {
        static $FMT = 'QueueName can\'t be empty';
        if( empty( $queueName )) {
            self::throwException( $FMT, 121 );
        }
    }

    /**
     * @param int|array $priority
     * @param null|int $min
     * @param null|int $max
     * @throws InvalidArgumentException
     */
    public static function priority( int|array $priority, null|int $min = 0, null|int $max = 9 ) : void
    {
        static $FMTP = 'Priority expected int and %d <= priority <= %d, got %d';
        if( is_int( $priority ) && (( $min > $priority ) || ( $max < $priority ))) {
            self::throwException( sprintf( $FMTP, $priority, $min, $max ), 131 );
        }
    }

    /**
     * @param string $message
     * @param int    $errCode
     */
    public static function throwException( string $message, int $errCode ) : void
    {
        throw new InvalidArgumentException( $message, $errCode );
    }
}
