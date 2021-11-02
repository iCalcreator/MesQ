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

use DirectoryIterator;
use Exception;
use InvalidArgumentException;
use RuntimeException;

use function array_slice;
use function count;
use function date;
use function end;
use function glob;
use function in_array;
use function is_array;
use function microtime;
use function print_r;
use function reset;
use function rtrim;
use function serialize;
use function sprintf;
use function trim;
use function var_export;

/**
 * Class MesQ
 *
 * @since 1.03 - 2021-03-16
 */
class MesQ implements MesQinterface
{
    /**
     * @var string
     */
    private static string $SP0 = '';

    /**
     * glob search file pattern
     *
     * @var string
     */
    private static string $PATTERN = "?.*.*.*.*";

    /**
     * @var array MesQ[]
     */
    private static array $instance = [];

    /**
     * @var string
     */
    private string $queueName;

    /**
     * @var float
     */
    private float $startTime;

    /**
     * Full path directory
     *
     * @var string
     */
    private string $directory;

    /**
     * @var string
     */
    private string $queueType = self::FIFO;

    /**
     * Outgoing properties
     */

    /**
     * Message directory file names
     *
     * @var array
     */
    private array $files = [];

    /**
     * Next $files indexNo
     *
     * @var int
     */
    private int $ix = -1;

    /**
     * Max number of file name chunks to read from disk
     *
     * @var int
     */
    private int $readChunkSize = PHP_INT_MAX;

    /**
     * Max number of messages to return
     *
     * @var int
     */
    private int $returnChunkSize = PHP_INT_MAX;


    /**
     * Construct, factory and singleton methods
     */

    /**
     * MesQ constructor, private
     *
     * @param array|string $queueName  config [ queueName, directory ] or unique queue name
     * @param null|string $directory   full path (of existing) directory, must exist with (php) user write/read rights
     * @throws InvalidArgumentException
     */
    private function __construct( array | string $queueName, ? string $directory = null )
    {
        $this->startTime = microtime( true );
        if( is_array( $queueName )) {
            $this->setConfig( $queueName );
        }
        else {
            $this->setQueueName( $queueName );
            $this->setDirectory(( $directory ?? $queueName ));
        }
    }

    /**
     * MesQ factory method
     *
     * @param array|string $queueName  config array or unique queue name
     * @param null|string $directory   full path (of existing) directory, must exist with (php) user write/read rights
     * @return self
     * @throws InvalidArgumentException
     */
    public static function factory( array | string $queueName, ? string $directory = null ) : MesQ
    {
        return new self( $queueName, $directory );
    }

    /**
     * Singleton method, singleton on unique queueName/directory basis
     *
     * @param array|string $queueName  config array or unique queue name
     * @param null|string $directory   full path (of existing) directory, must exist with (php) user write/read rights
     * @return self
     * @throws InvalidArgumentException
     */
    public static function singleton( array | string $queueName, ? string $directory = null ) : MesQ
    {
        if( is_array( $queueName )) { // config
            $sQn  = $queueName[self::QUEUENAME] ?? null;
            Assert::nonEmptyString( (string)$sQn );
            $sDir = $queueName[self::DIRECTORY] ?? $sQn;
            $key  = serialize( $sQn . $sDir );
        }
        else {
            Assert::nonEmptyString( $queueName );
            $key  = serialize( $queueName . ( $directory ?? $queueName ));
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
     * @param null|string $key
     * @return array|false|float|int|string|null
     */
    public function getConfig( ? string $key = null ) : float | bool | int | array | string | null
    {
        static $YMDHIS = 'YmdHis';
        if( empty( $key )) {
            return [
                self::QUEUENAME       => $this->queueName,
                self::DIRECTORY       => $this->directory,
                self::QUEUETYPE       => $this->queueType,
                self::STARTTIME       => $this->startTime,
                self::DATE            => date( $YMDHIS, (int) $this->startTime ),
                self::READCHUNKSIZE   => $this->readChunkSize,
                self::RETURNCHUNKSIZE => $this->returnChunkSize,
            ];
        }
        return match ( $key ) {
            self::QUEUENAME       => $this->queueName,
            self::DIRECTORY       => $this->directory,
            self::QUEUETYPE       => $this->queueType,
            self::READCHUNKSIZE   => $this->readChunkSize,
            self::RETURNCHUNKSIZE => $this->returnChunkSize,
            self::STARTTIME       => $this->startTime,
            self::DATE            => date( $YMDHIS, (int)$this->startTime ),
            default               => false,
        }; // end switch
    }

    /**
     * Set all config
     *
     * @param array $config
     * @return void
     * @throws InvalidArgumentException
     */
    private function setConfig( array $config ) : void
    {
        static $FMT = 'Queue \'%s\', unknown config key %s';
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
                default :
                    throw new InvalidArgumentException( sprintf( $FMT, $this->queueName, $key ));
            } // end switch
        } // end foreach
        if( $this->isQueueNameSet() && ! $this->isDirectorySet()) {
            $this->setDirectory( $this->getQueueName());
        }
    }

    /**
     * @return string config incl. process pid and (float) start timestamp
     */
    public function configToString() : string
    {
        $str = self::$SP0;
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
     * @param array $config   config array
     * @param mixed $message
     * @param null|int   $priority   0 : lowest, 9 : highest
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function qPush( array $config, mixed $message, ? int $priority = null ) : void
    {
        self::factory( $config )->push( $message, ( $priority ?? 0 ));
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
     * @param mixed     $message
     * @param null|int  $priority   0 : lowest, 9 : highest
     * @return void
     * @throws RuntimeException
     * @since 1.03 - 2021-03-16
     */
    public function push( mixed $message, ? int $priority = 0 ) : void
    {
        static $FMT3 = 'Queue \'%s\', serialize error, message : %s';
        try {
            $msg     = serialize( Message::factory( $message ));
        }
        catch( Exception $e ) { // closure detected ?
            throw new RuntimeException(
                sprintf( $FMT3, $this->getQueueName(), print_r( $message, true )),
                null,
                $e
            );
        }
        FileHandler::store( $this->queueName, $this->queueType, $this->directory, $priority ?? 0, $msg );
    }

    /**
     * Push method alias
     *
     * @param mixed     $message
     * @param null|int  $priority
     * @return void
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function insert( mixed $message, ? int $priority = 0 ) : void
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
    private function reset() : void
    {
        $this->files = [];
        $this->ix    = -1;
    }

    /**
     * (Re)load message files array, return bool true on found otherwise false
     *
     * @param int|array|null $priority
     * @return bool
     * @throws RuntimeException  on filesystem read error
     */
    private function loadFiles( null| int | array $priority = null ) : bool
    {
        static $FMT1 = 'Queue \'%s\', (glob) filesystem %s read error';
        $this->reset();
        $globPattern  = $this->getDirectory();
        $globPattern .= $this->isQueueTypePrio() ? self::getPriorityPattern( $priority ) : self::$PATTERN;
        // glob-loaded file names are sorted alphabetically
        if( false === ( $this->files = glob( $globPattern ))) {
            throw new RuntimeException(
                sprintf( $FMT1, $this->getQueueName(), $globPattern )
            );
        }
        $globCnt = count( $this->files );
        if( 0 === $globCnt ) {
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
     * Return bool true if message(s) exist (i.e. force reload of internal static filesystemIterator)
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
    public function messageExist( null|int|array $priority = null ) : bool
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
     * @return mixed
     * @throws InvalidArgumentException
     * @throws RuntimeException on message disk (read) error
     * @since 1.02 - 2021-03-16
     */
    public function getMessage( null|int|array $priority = null ) : mixed
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
        try {
            $content = FileHandler::getFileContents( $this->queueName, $this->files[$this->ix] );
        }
        catch( Exception $e ) {
            $this->reset();
            throw $e;
        }
        ++$this->ix;
        return $this->unserialize( $content );
    }

    /**
     * getMessage method alias
     *
     * @param null|int|array $priority
     * @return mixed
     * @throws RuntimeException on message disk (read) error
     */
    public function pull( null|int|array $priority = null ) : mixed
    {
        return $this->getMessage( $priority );
    }

    /**
     * @param string $string
     * @return mixed
     * @throws RuntimeException
     * @since 1.02 - 2021-03-16
     */
    private function unserialize( string $string ) : mixed
    {
        static $FMT1    = 'Queue \'%s\' , error on unserialize of message (file) : %s, content %s';
        static $FMT2    = 'Queue \'%s\' , false result on unserialize of message (file) : %s, content %s';
        static $FMT3    = 'Unknows class unserialized, %s (Message expected) data %s';
        static $OPTIONS = [ 'allowed_classes' => [ Message::class ]];
        try {
            $unserialized = unserialize( $string, $OPTIONS );
        }
        catch( Exception $e ) {
            $msg = sprintf( $FMT1, $this->queueName, $this->files[$this->ix], $string );
            throw new RuntimeException( $msg, 2, $e );
        }
        if(( false === $unserialized ) && ( $string !== serialize( false ))) {
            throw new RuntimeException( sprintf( $FMT2, $this->queueName, $this->files[$this->ix], $string ));
        }
        if( ! $unserialized instanceof Message ) {
            throw new RuntimeException( sprintf( $FMT3, get_class( $unserialized ), $string ));
        }
        $data = $unserialized->getData();
        unset( $unserialized );
        return $data;
    }

    /**
     * Queue size methods
     */

    /**
     * Return the queue size in number of messages (now), opt for priority, bool false on error
     *
     * @param null|int|array $priority
     * @return bool|int
     */
    public function getQueueSize( null|int|array $priority = null ) : bool | int
    {
        $pattern = $this->isQueueTypePrio() ? self::getPriorityPattern( $priority ) : self::$PATTERN;
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
    public function size( null|int|array $priority = null ) : bool | int
    {
        return $this->getQueueSize( $priority );
    }

    /**
     * Return the directory size in bytes (now)
     *
     * @return int
     */
    public function getDirectorySize() : int
    {
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
     * @return string
     * @throws InvalidArgumentException
     */
    private static function getPriorityPattern( null|int|array $priority = null ) : string
    {
        static $PTRN   = '[%d-%d]';
        static $ERRTXT = 'Unvalid priority ';
        $pattern       = self::$SP0;
        switch( true ) {
            case ( null === $priority ) :
                $pattern = self::$PATTERN;
                break;
            case is_array( $priority ) :
                $min = reset( $priority );
                Assert::priority( $min );
                $min = 9 - $min;
                $max = end( $priority );
                Assert::priority( $max );
                $max = 9 - $max;
                if(( $min > $max ) && ( 2 === count( $priority ))) { // reverse..
                    $pattern = sprintf( $PTRN, $max, $min ) . substr( self::$PATTERN, 1 );
                    break;
                }
                Assert::throwException( $ERRTXT . var_export( $priority, true ), 144 );
                break;
            default :
                Assert::priority( $priority );
                $pattern = ((string) ( 9 - $priority )) . substr( self::$PATTERN, 1 );
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
     * @return self
     * @throws InvalidArgumentException
     */
    public function setQueueName( string $queueName ) : MesQ
    {
        Assert::nonEmptyString( $queueName );
        $this->queueName = $queueName;
        return $this;
    }

    /**
     * @param string $queueType
     * @return self
     * @throws InvalidArgumentException
     */
    public function setQueueType( string $queueType ) : MesQ
    {
        static $FMT = 'Queue \'%s\', invalid queuetype %s';
        if( ! in_array( $queueType, [ self::FIFO, self::LIFO, self::PRIO ], true ) ) {
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
        return ( self::PRIO === $this->queueType );
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
    public function setDirectory( string $directory ) : void
    {
        $directory       = rtrim( trim( $directory ), DIRECTORY_SEPARATOR );
        Assert::directory( $this->getQueueName(), $directory );
        $this->directory = $directory . DIRECTORY_SEPARATOR;
    }

    /**
     * @param int $readChunkSize
     * @return self
     */
    public function setReadChunkSize( int $readChunkSize ) : MesQ
    {
        $this->readChunkSize = $readChunkSize;
        return $this;
    }

    /**
     * @param int $returnChunkSize
     * @return self
     */
    public function setReturnChunkSize( int $returnChunkSize ) : MesQ
    {
        $this->returnChunkSize = $returnChunkSize;
        return $this;
    }
}
