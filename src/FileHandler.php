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
use RuntimeException;

use function explode;
use function fclose;
use function file_put_contents;
use function filesize;
use function flock;
use function fopen;
use function fread;
use function is_file;
use function microtime;
use function sprintf;
use function strlen;
use function unlink;

/**
 * Manages single file store/read operations
 */
class FileHandler
{
    /**
     * @var int
     */
    private static int $serial = 0;

    /**
     * @param string   $queueName
     * @param string   $queueType
     * @param string   $directory
     * @param int      $priority
     * @param string   $message
     * @return void
     */
    public static function store(
        string  $queueName,
        string  $queueType,
        string  $directory,
        int     $priority,
        string  $message
    ) : void
    {
        static $FMT = 'Queue \'%s\', error on write, file %s, message : %s';
        $fileName    = self::getFileName( $queueName, $queueType, $directory, $priority );
        $result      = file_put_contents( $fileName, $message, LOCK_EX );
        if(( false === $result ) || ( $result !== strlen( $message ))) {
            throw new RuntimeException( sprintf( $FMT, $queueName, $fileName, $message ));
        }
    }

    /**
     * Return unique queue fileName
     *
     * @param string   $queueName
     * @param string   $queueType
     * @param string   $directory
     * @param int      $priority
     * @return string
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    private static function getFileName(
        string $queueName,
        string $queueType,
        string $directory,
        int $priority
    ) : string
    {
        static $FMT1  = '%s%d.%020d.%020d.%06d.';
        static $FMT2  = 'Queue \'%s\', error on finding unique filename %s';
        [ $prio, $usec, $sec ] = self::getPrioUsecSec( $queueType, $priority );
        $timestampPrf = sprintf( $FMT1, $directory, $prio, $sec, $usec, getmypid());
        $ix       = 0;
        $fileName = $timestampPrf . self::getSerial();
        while( @is_file( $fileName )) {
            $fileName = $timestampPrf . self::getSerial();
            if( ++$ix > 10 ) { // emergency break??
                throw new RuntimeException( sprintf( $FMT2, $queueName, $fileName ));
            }
        } // end while
        return $fileName;
    }

    /**
     * @return int
     */
    private static function getSerial() : int
    {
        if( self::$serial === PHP_INT_MAX ) {
            self::$serial = 0;
        }
        return ++self::$serial;
    }

    /**
     * @param string   $queueType
     * @param int|null $priority
     * @return array
     * @throws InvalidArgumentException
     */
    private static function getPrioUsecSec( string $queueType, ?int $priority = 0 ) : array
    {
        static $SP1 = ' ';
        [ $usec, $sec ] = explode( $SP1, microtime());
        $sec        = (int) $sec;
        $usec       = ( (int)$usec * 1000000 );
        $prio       = 0;
        switch( $queueType ) {
            case MesQinterface::LIFO :
                $sec = PHP_INT_MAX - $sec;
                $usec = PHP_INT_MAX - $usec;
                break;
            case MesQinterface::PRIO :
                Assert::priority( $priority );
                $prio = 9 - $priority;
                break;
            default : // FIFO
                break;
        } // end switch
        return [ $prio, $usec, $sec ];
    }

    /**
     * @param string  $queueName
     * @param string  $fileName
     * @return string
     * @throws RuntimeException
     */
    public static function getFileContents( string  $queueName, string $fileName ) : string
    {
        static $FMT3 = 'Queue \'%s\' , error (#1) on open of message (file) : %s';
        static $FMT4 = 'Queue \'%s\' , error (#2) on lock of message (file) : %s';
        static $FMT5 = 'Queue \'%s\' , error (#3) on reading filesize of message (file) : %s';
        static $FMT6 = 'Queue \'%s\' , error (#4), filesize zero of message (file) : %s';
        static $FMT7 = 'Queue \'%s\' , error (#5) on read of message (file) : %s';
        static $FMT8 = 'Queue \'%s\' , error (#6) on delete of message (file) : %s';
        static $RT = 'rb';
        if( false === ( $fp = @fopen( $fileName, $RT ))) {
            throw new RuntimeException( sprintf( $FMT3, $queueName, $fileName ));
        }
        if( false === ( flock( $fp, LOCK_SH ))) {
            fclose( $fp );
            throw new RuntimeException( sprintf( $FMT4, $queueName, $fileName ));
        }
        if( false === ( $filesize = @filesize( $fileName ))) {
            fclose( $fp );
            throw new RuntimeException( sprintf( $FMT5, $queueName, $fileName ));
        }
        if( 0 === $filesize ) {
            fclose( $fp );
            throw new RuntimeException( sprintf( $FMT6, $queueName, $fileName ));
        }
        if( false === ( $content = fread( $fp, $filesize ))) {
            fclose( $fp );
            throw new RuntimeException( sprintf( $FMT7, $queueName, $fileName ));
        }
        fclose( $fp );
        if( false === @unlink( $fileName )) {
            throw new RuntimeException( sprintf( $FMT8, $queueName, $fileName ));
        }
        return $content;
    }
}
