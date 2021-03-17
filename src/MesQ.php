<?php
/**
 * MesQ, lite PHP disk based message queue manager
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
declare( strict_types = 1 );
namespace Kigkonsult\MesQ;

use DirectoryIterator;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use function array_slice;
use function count;
use function date;
use function end;
use function explode;
use function fclose;
use function filesize;
use function flock;
use function fread;
use function fopen;
use function getmypid;
use function glob;
use function intval;
use function in_array;
use function is_dir;
use function is_array;
use function is_file;
use function is_int;
use function is_readable;
use function is_writeable;
use function microtime;
use function print_r;
use function reset;
use function rtrim;
use function serialize;
use function sprintf;
use function trim;
use function unlink;

/**
 * Class MesQ
 *
 * @since 1.03 - 2021-03-16
 */
class MesQ implements MesQinterface
{
    /**
     * Create file name pattern
     *
     * @var string
     */
    private static $CREATEPATTERN = '%d.%020d.%020d.%06d.';

    /**
     * glob search file name pattern
     *
     * @var string
     */
    private static $SEARCHPATTERN = "?.????????????????????.????????????????????.??????.?*";

    /**
     * Prio part of pattern if prio is array(min,max)
     *
     * @var string
     */
    private static $PRIOPATTERN = '[%d-%d]';

    /**
     * @var self
     */
    private static $instance = [];

    /**
     * @var string
     */
    private $queueName = null;

    /**
     * @var float
     */
    private $startTime = null;

    /**
     * Full path directory
     *
     * @var string
     */
    private $directory = null;

    /**
     * Incoming properties
     */

    /**
     * @var int
     */
    private $queueType = self::FIFO;

    /**
     * @var int
     */
    private static $serial = 0;

    /**
     * The process pid number
     *
     * @var null
     */
    private $pid = null;

    /**
     * Outgoing properties
     */

    /**
     * Messages directory file name array
     *
     * @var array
     */
    private $files = [];

    /**
     * Next $files indexNo
     *
     * @var int
     */
    private $ix = -1;

    /**
     * Max number of file name chunks to read from disk
     *
     * @var int
     */
    private $readChunkSize = PHP_INT_MAX;

    /**
     * Max number of messages to return
     *
     * @var int
     */
    private $returnChunkSize = PHP_INT_MAX;


    /**
     * Construct, factory and singleton methods
     */

    /**
     * MesQ constructor, private
     *
     * @param array|string $queueName  config array or unique queue name
     * @param string $directory   full path (of existing) directory, must exist with (php) user write/read rights
     * @throws InvalidArgumentException
     */
    private function __construct( $queueName, $directory = null )
    {
        $this->startTime = microtime( true );
        if( is_array( $queueName )) {
            $this->setConfig( $queueName );
        }
        else {
            $this->setQueueName( $queueName );
            $this->setDirectory(( $directory ?: $queueName ));
        }
    }

    /**
     * MesQ factory method
     *
     * @param array|string $queueName  config array or unique queue name
     * @param string $directory   full path (of existing) directory, must exist with (php) user write/read rights
     * @return static
     * @throws InvalidArgumentException
     */
    public static function factory(  $queueName, $directory = null ) : MesQ
    {
        return new self( $queueName, $directory );
    }

    /**
     * Singleton method, singleton on unique queueName/directory basis
     *
     * @param array|string $queueName  config array or unique queue name
     * @param string $directory   full path (of existing) directory, must exist with (php) user write/read rights
     * @return static
     * @throws InvalidArgumentException
     */
    public static function singleton(  $queueName, $directory = null ) : MesQ
    {
        if( is_array( $queueName )) { // config
            $sQn  = isset( $queueName[self::QUEUENAME] ) ? $queueName[self::QUEUENAME] : null;
            self::assertQueueName( (string) $sQn );
            $sDir = isset( $queueName[self::DIRECTORY] ) ? $queueName[self::DIRECTORY] : $sQn;
            $key  = serialize( $sQn . (string) $sDir );
        }
        else {
            self::assertQueueName( $queueName );
            $key  = serialize( $queueName . (string) ( $directory ?: $queueName ));
        }
        if( ! isset( self::$instance[$key] )) {
            self::$instance[$key] = new self( $queueName, $directory );
        }
        return self::$instance[$key];
    }

    /**
     * Config methods
     */

    /**
     * Return config as array incl. process pid and (float) start timestamp
     *
     * @param string $key
     * @return int|string|bool|array  unknown key return bool false
     */
    public function getConfig( $key = null ) : array
    {
        static $YMDHIS = 'YmdHis';
        if( null === $key ) {
            return [
                self::QUEUENAME       => $this->queueName,
                self::DIRECTORY       => $this->directory,
                self::QUEUETYPE       => $this->queueType,
                self::PID             => $this->getPid(),
                self::STARTTIME       => $this->startTime,
                self::DATE            => date( $YMDHIS, (int) $this->startTime ),
                self::READCHUNKSIZE   => $this->readChunkSize,
                self::RETURNCHUNKSIZE => $this->returnChunkSize,
            ];
        }
        switch( $key ) {
            case self::QUEUENAME :
                return $this->queueName;
                break;
            case self::DIRECTORY :
                return $this->directory;
                break;
            case self::QUEUETYPE :
                return $this->queueType;
                break;
            case self::READCHUNKSIZE :
                return $this->readChunkSize;
                break;
            case self::RETURNCHUNKSIZE :
                return $this->returnChunkSize;
                break;
            case self::PID :
                return $this->getPid();
                break;
            case self::STARTTIME :
                return $this->startTime;
                break;
            case self::DATE :
                return date( $YMDHIS, (int) $this->startTime );
            default :
                return false;
                break;
        } // end switch
    }

    /**
     * Set all config
     *
     * @param array $config
     * @return void
     * @throws InvalidArgumentException
     */
    private function setConfig( array $config )
    {
        foreach( $config as $key => $value ) {
            switch( $key ) {
                case self::QUEUENAME :
                    $this->setQueueName( $value );
                    break;
                case self::DIRECTORY :
                    $this->setDirectory( $value );
                    break;
                case self::QUEUETYPE :
                    $this->setQueueType( $value );
                    break;
                case self::READCHUNKSIZE :
                    $this->setReadChunkSize( $value );
                    break;
                case self::RETURNCHUNKSIZE :
                    $this->setReturnChunkSize( $value );
                    break;
            } // end switch
        } // end foreach
        if( $this->isQueueNameSet() && ! $this->isDirectorySet()) {
            $this->setDirectory( $this->getQueueName());
        }
    }

    /**
     * Return nice rendered config incl. process pid and (float) start timestamp
     *
     * @return string
     */
    public function configToString() : string
    {
        $str = null;
        foreach( $this->getConfig() as $key => $value ) {
            $str .= str_pad( $key, 16 ) . $value . PHP_EOL;
        }
        return $str;
    }

    /**
     * Incoming logic methods
     */

    /**
     * One-liner, insert single message to queue
     *
     * @param array     $config   config array
     * @param mixed     $message
     * @param null|int  $priority   0 : lowest, 9 : highest
     * @return void
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function qPush( array $config, $message, $priority = null )
    {
        self::factory( $config )->push( $message, $priority );
    }

    /**
     * Insert new message to queue
     *
     * Saves the (serialized) message with a unique filename
     * based on (fixed formatted) queueType, opt prio, timestamp, pid and a serial number
     *
     * Usage
     * <code>
     *     ...
     *     MesQ::factory( <queueName>, <directory> )
     *         ->push( <message> );
     *     ...
     * </code>
     *
     * @param mixed    $message
     * @param null|int $priority   0 : lowest, 9 : highest
     * @return void
     * @throws RuntimeException
     * @since 1.03 - 2021-03-16
     */
    public function push( $message, $priority = 0 )
    {
        static $FMT3 = 'Queue \'%s\', serialize error, file %s, message : %s';
        static $FMT4 = 'Queue \'%s\', error on write, file %s, message : %s';
        $fileName    = $this->getFileName( $priority );
        try {
            $msg     = serialize( $message );
        }
        catch( Exception $e ) { // closure detected ?
            throw new RuntimeException(
                sprintf( $FMT3, $this->getQueueName(), $fileName, print_r( $message, true )),
                $e
            );
        }
        $result = file_put_contents( $fileName, $msg, LOCK_EX );
        if(( false === $result ) || ( $result != strlen( $msg ))) {
            throw new RuntimeException( sprintf( $FMT4, $this->getQueueName(), $fileName, $msg ));
        }
    }

    /**
     * @param null|int $priority
     * @return string
     */
    private function getFileName( $priority = 0 ) : string
    {
        static $FMT2  = 'Queue \'%s\', error on finding unique filename %s';
        list( $prio, $usec, $sec ) = self::getPrioUsecSec( $this->queueType, $priority );
        $timestampPrf = sprintf( self::$CREATEPATTERN, $prio, $sec, $usec, $this->getPid());
        $ix           = 0;
        $fileName     = $this->directory . $timestampPrf . self::getSerial();
        while( @is_file( $fileName )) {
            $fileName = $this->directory . $timestampPrf . self::getSerial();
            if( ++$ix > 10 ) { // emergency break??
                throw new RuntimeException( sprintf( $FMT2, $this->getQueueName()));
            }
        } // end while
        return $fileName;
    }

    /**
     * @param string   $queueType
     * @param null|int $priority
     * @return array
     */
    private static function getPrioUsecSec( string $queueType, $priority = 0 ) : array
    {
        static $SP1   = ' ';
        list( $usec, $sec ) = explode( $SP1, microtime());
        $sec  = intval( $sec );
        $usec = intval( $usec * 1000000 );
        $prio = 0;
        switch( $queueType ) {
            case self::LIFO :
                $sec  = PHP_INT_MAX - $sec;
                $usec = PHP_INT_MAX - $usec;
                break;
            case self::PRIO :
                self::assertPriority( $priority );
                $prio = 9 - $priority;
                break;
            default : // FIFO
                break;
        } // end switch
        return [ $prio, $usec, $sec ];
    }

    /**
     * Push method alias
     *
     * @param mixed $message
     * @param int   $priority
     * @return void
     */
    public function insert( $message, $priority = 0 )
    {
        $this->push( $message, $priority );
    }

    /**
     * Outgoing logic methods
     */

    /**
     * Reset internal filename array and indexNo
     *
     * @return void
     */
    private function reset()
    {
        $this->files = [];
        $this->ix    = -1;
    }

    /**
     * (Re)load message files array, return bool true on found otherwise false
     *
     * @param null|int|array $priority
     * @return bool
     * @throws RuntimeException  on filesystem read error
     */
    private function loadFiles( $priority = null ) : bool
    {
        static $FMT1 = 'Queue \'%s\', (glob) filesystem %s read error';
        $this->reset();
        $pattern  = $this->isQueueTypePrio() ? self::getPriorityPattern( $priority ) : self::$SEARCHPATTERN;
        // glob-loaded file names are sorted alphabetically
        if( false === ( $this->files = glob( $this->directory . $pattern ))) {
            throw new RuntimeException(
                sprintf( $FMT1, $this->getQueueName(), $this->directory . $pattern )
            );
        }
        $globCnt = count( $this->files );
        if( 0 == $globCnt ) {
            return false;
        }
        // check chunk size
        if( $globCnt > $this->readChunkSize ) {
            $this->files = array_slice( $this->files, 0, $this->readChunkSize );
        }
        $this->ix = 0;
        return true;
    }

    /**
     * Return bool true if message(s) exist (i.e. reload internal static filesystemIterator)
     *
     * Usage
     * <directory> must exist with (php) user write/read rights
     * <code>
     *     ...
     *     $mesQ = MesQ::singleton( <queueName>, <directory> )
     *         ->setReadChunkSize( 100 );
     *     if( $mesQ->messageExists() {
     *         while( $message = $mesQ->getMessage()) {
     *             ...
     *         }
     *     }
     *     ...
     * </code>
     *
     * @param null|int|array $priority
     * @return bool
     * @throws RuntimeException
     */
    public function messageExist( $priority = null ) : bool
    {
        return $this->loadFiles( $priority );
    }

    /**
     * Return next message or bool false on empty or return max size of messages reached
     *
     * If used without arg priority, messages are returned in priority order
     *
     * Usage
     * <directory> must exist with (php) user write/read rights
     * <code>
     *     ...
     *     $mesQ = MesQ::singleton( <queueName>, <directory> )
     *         ->setReadChunkSize( 100 );
     *     while( $message = $mesQ->getMessage()) {
     *         ...
     *     }
     *     ...
     * </code>
     *
     * @param null|int|array $priority
     * @return false|mixed
     * @throws RuntimeException on message disk (read) error
     * @since 1.02 - 2021-03-16
     */
    public function getMessage( $priority = null )
    {
        if( $this->ix >= $this->returnChunkSize ) {
            $this->reset();
            return false;
        } // end if
        if(( empty( $this->files ) ||
            ! isset( $this->files[$this->ix] )) &&
            ! $this->messageExist( $priority )) {
            return false;
        } // end if
        $content   = $this->getFileContents( $this->files[$this->ix] );
        $this->ix += 1;
        return $this->unserialize( $content );
    }

    /**
     * getMessage method alias
     *
     * @param null|int|array $priority
     * @return false|mixed
     * @throws RuntimeException on message disk (read) error
     */
    public function pull( $priority = null )
    {
        return $this->getMessage();
    }

    /**
     * @param $fileName
     * @return string
     */
    private function getFileContents( $fileName ) : string
    {
        static $FMT3 = 'Queue \'%s\', error on open of message (file) : %s';
        static $FMT4 = 'Queue \'%s\', error on lock of message (file) : %s';
        static $FMT5 = 'Queue \'%s\', error on reading filesize of message (file) : %s';
        static $FMT6 = 'Queue \'%s\', error, filesize zero of message (file) : %s';
        static $FMT7 = 'Queue \'%s\', error on read of message (file) : %s';
        static $FMT8 = 'Queue \'%s\', error on delete of message (file) : %s';
        static $RT   = 'rt';
        $errFmt      = null;
        while( true ) {
            if( false === ( $fp = @fopen( $fileName, $RT ))) {
                $errFmt = $FMT3;
                break;
            }
            if( false === ( flock( $fp, LOCK_SH ))) {
                fclose( $fp );
                $errFmt = $FMT4;
                break;
            }
            if( false === ( $filesize = @filesize( $fileName ))) {
                fclose( $fp );
                $errFmt = $FMT5;
                break;
            }
            if( empty( $filesize )) {
                fclose( $fp );
                $errFmt = $FMT6;
                break;
            }
            if( false === ( $content = @fread( $fp, $filesize ))) {
                fclose( $fp );
                $errFmt = $FMT7;
                break;
            }
            fclose( $fp );
            if( false === @unlink( $fileName )) {
                $errFmt = $FMT8;
            }
            break;
        } // end while
        if( ! empty( $errFmt )) {
            $this->reset();
            throw new RuntimeException( sprintf( $errFmt, $this->getQueueName(), $fileName ));
        }
        return $content;
    }

    /**
     * @param string $content
     * @return mixed
     * @throws RuntimeException
     * @since 1.02 - 2021-03-16
     */
    private function unserialize( string $content )
    {
        static $FMT1 = 'Queue \'%s\', error on unserialize of message (file) : %s, content %s';
        static $FMT2 = 'Queue \'%s\', false result on unserialize of message (file) : %s, content %s';
        try {
            $unserialized = unserialize( $content );
        }
        catch( Exception $e ) {
            $msg = sprintf( $FMT1, $this->queueName, $this->files[$this->ix], $content );
            throw new RuntimeException( $msg, null, $e );
        }
        if(( false === $unserialized ) && ( $content !== serialize( false ))) {
            throw new RuntimeException( sprintf( $FMT2, $this->queueName, $this->files[$this->ix], $content ));
        }
        return $unserialized;
    }

    /**
     * Queue size methods
     */

    /**
     * Return the queue size in number of messages (now), opt for message priority, bool false on error
     *
     * @param null|int|array $priority
     * @return bool|int
     */
    public function getQueueSize( $priority = null )
    {
        $pattern = $this->isQueueTypePrio() ? self::getPriorityPattern( $priority ) : self::$SEARCHPATTERN;
        if( false === ( $messages = glob( $this->getDirectory() . $pattern ))) {
            return false;
        }
        return count( $messages );
    }

    /**
     * getQueueSize method alias
     *
     * @param null|int|array $priority
     * @return bool|int
     */
    public function size ( $priority = null )
    {
        return $this->getQueueSize( $priority );
    }

    /**
     * Return the directory size in bytes (now)
     *
     * @return int
     */
    function getDirectorySize() {
        $bytesTotal = 0;
        foreach( new DirectoryIterator( $this->getDirectory()) as $fileObject ) {
            if( $fileObject->isFile()) {
                $bytesTotal += $fileObject->getSize();
            }
        }
        return $bytesTotal;
    }

    /**
     * Priority methods
     */

    /**
     * @param null|int|array $priority
     * @throws InvalidArgumentException
     */
    private static function assertPriority( $priority )
    {
        static $FMTP  = 'Priority expected int and 0 <= priority <= 9, got ';
        if( ! is_int( $priority ) || ( 0 > $priority ) || ( 9 < $priority )) {
            throw new InvalidArgumentException( $FMTP . print_r( $priority, true ));
        }
    }

    /**
     * @param null|int|array $priority
     */
    private static function getPriorityPattern( $priority = null )
    {
        switch( true ) {
            case ( null === $priority ) :
                $pattern = self::$SEARCHPATTERN;
                break;
            case is_array( $priority ) :
                $min = reset( $priority );
                self::assertPriority( $min );
                $min = 9 - $min;
                $max = end( $priority );
                self::assertPriority( $max );
                $max = 9 - $max;
                if( 2 == count( $priority ) && ( $min > $max )) { // reverse..
                    $pattern = sprintf( self::$PRIOPATTERN, $max, $min ) . substr( self::$SEARCHPATTERN, 1 );
                }
                else {
                    self::assertPriority( $priority ); // force exception
                }
                break;
            default :
                self::assertPriority( $priority );
                $pattern = (string) ( 9 - $priority ) . substr( self::$SEARCHPATTERN, 1 );
                break;
        } // end switch
        return $pattern;
    }

    /**
     * Getter and setter methods
     */

    /**
     * @return string
     */
    public function getQueueName() : string
    {
        return $this->queueName;
    }

    /**
     * @return bool
     */
    public function isQueueNameSet() : bool
    {
        return ( null !== $this->queueName );
    }

    /**
     * @param string $queueName
     * @return static
     * @throws InvalidArgumentException
     */
    public function setQueueName( string $queueName ) : MesQ
    {
        self::assertQueueName( $queueName );
        $this->queueName = $queueName;
        return $this;
    }

    /**
     * @param string $queueName
     * @throws InvalidArgumentException
     */
    private static function assertQueueName( string $queueName )
    {
        static $FMT = 'QueueName can\'t be empty';
        if( empty( $queueName )) {
            throw new InvalidArgumentException( $FMT );
        }
    }

    /**
     * @param string $queueType
     * @return static
     * @throws InvalidArgumentException
     */
    public function setQueueType( string $queueType ) : MesQ
    {
        static $FMT = 'Queue \'%s\', invalid queuetype %s';
        if( ! in_array( $queueType, [ self::FIFO, self::LIFO, self::PRIO ] )) {
            throw new InvalidArgumentException( sprintf( $FMT, $this->getQueueName(), $queueType ));
        }
        $this->queueType = $queueType;
        return $this;
    }

    /**
     * @return bool
     */
    public function isQueueTypePrio() : bool
    {
        return ( self::PRIO == $this->queueType );
    }

    /**
     * @return string
     */
    public function getDirectory() : string
    {
        return $this->directory;
    }

    /**
     * @return bool
     */
    public function isDirectorySet() : bool
    {
        return ( null !== $this->directory );
    }

    /**
     * @param string $directory  full path (of existing) directory
     * @return void
     * @throws InvalidArgumentException
     */
    public function setDirectory( $directory )
    {
        $directory       = rtrim( trim( $directory ), DIRECTORY_SEPARATOR );
        self::assertDirectory( $this->getQueueName(), $directory );
        $this->directory = $directory . DIRECTORY_SEPARATOR;
    }

    /**
     * Assert directory
     *
     * @param string $queueName
     * @param string $directory
     * @throws InvalidArgumentException
     */
    private static function assertDirectory( string $queueName, string $directory )
    {
        static $ERRFMTEMPTY = 'Queue \'%s\', directory can\'t be empty';
        static $ERRFMTISDIR = 'Queue \'%s\', directory %s not exists and/or is not a directory';
        static $ERRFMTWRTBL = 'Queue \'%s\', directory %s is not writeable/readable';
        if( empty( $directory )) {
            throw new InvalidArgumentException( sprintf( $queueName, $ERRFMTEMPTY ));
        }
        if( ! @is_dir( $directory )) {
            throw new InvalidArgumentException( sprintf( $ERRFMTISDIR, $queueName, $directory ));
        }
        if( ! @is_writeable( $directory ) || ! @is_readable( $directory )) {
            throw new InvalidArgumentException( sprintf( $ERRFMTWRTBL, $queueName, $directory ));
        }
    }

    /**
     * @return int
     */
    private static function getSerial() : int
    {
        if( self::$serial == PHP_INT_MAX ) {
            self::$serial = 0;
        }
        return ++self::$serial;
    }

    /**
     * Return the process pid number
     *
     * @return int
     */
    private function getPid() : int
    {
        if( empty( $this->pid )) {
            $this->pid = getmypid();
        }
        return $this->pid;
    }

    /**
     * @param int $readChunkSize
     * @return static
     */
    public function setReadChunkSize( int $readChunkSize ) : MesQ
    {
        $this->readChunkSize = $readChunkSize;
        return $this;
    }

    /**
     * @param int $returnChunkSize
     * @return static
     */
    public function setReturnChunkSize( int $returnChunkSize ) : MesQ
    {
        $this->returnChunkSize = $returnChunkSize;
        return $this;
    }
}
