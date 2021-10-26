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

use Closure;
use stdClass;

use function microtime;
use function number_format;
use function str_pad;

/**
 * Class TestMessage
 *
 * This class instrances represent test messages
 *
 * @package Kigkonsult\MesQ
 */
class TestMessage
{
    /**
     * @var int
     */
    private int $indexNo = 0;

    /**
     * @var string
     */
    private ?string $text;

    /**
     * Message priority 0-99
     *
     * @var int
     */
    private int $priority = 0;

    /**
     * @var float
     */
    private $loadTime = 0;

    /**
     * Property with closure
     *
     * @var Closure
     */
    private ?Closure $closureProp1;

    /**
     * @return null|Closure
     */
    public function getClosureProp1() : ?callable
    {
        return $this->closureProp1;
    }

    /**
     * Property with closure in array
     *
     * @var array
     */
    private array $closureProp2 = [];

    /**
     * Property, object
     *
     * @var TestMessage2
     */
    private ?TestMessage2 $closureProp3;

    /**
     * TestMessage constructor.
     *
     * @param int       $indexNo
     * @param string    $text
     * @param null|int  $priority
     */
    public function __construct( int $indexNo, string $text, ? int $priority = null )
    {
        $this->setLoadTime( microtime( true ));
        $this->setIndexNo( $indexNo );
        $this->setText( $text );
        if( null !== $priority ) {
            $this->setPriority( (int)$priority );
        }
        /*
        $this->closureProp1 = function() {
            return ' closureProp1';
        };
        $this->closureProp2 = [
            function () {
                return ' closureProp2';
            }
        ];
        $this->closureProp3 = new TestMessage2();
        */
    }

    /**
     * TestMessage factory method
     *
     * @param int    $indexNo
     * @param string $text
     * @return TestMessage
     */
    public static function factory( int $indexNo, string $text ) : TestMessage
    {
        return new self( $indexNo, $text );
    }

    /**
     * @return string
     */
    public function toString() : string
    {
        static $SP1 = ' ';
        static $DOT = '.';
        static $SP0 = '';
        return
            str_pad((string) $this->getIndexNo(), 6, $SP1, STR_PAD_LEFT ) .
            $SP1 .
            number_format( $this->getLoadTime(), 6, $DOT, $SP0 ) .
            $SP1 .
            str_pad((string) $this->getPriority(), 3, $SP1, STR_PAD_LEFT )
            ;
            /*
            .
            $SP1 .
            $this->getClosureProp1()() .
            $SP1 .
            $this->closureProp2[0]() .
            $SP1 .
            $this->closureProp3->toString();
            */
    }

    /**
     * @return int
     */
    public function getIndexNo() : int
    {
        return $this->indexNo;
    }

    /**
     * @param int $indexNo
     * @return TestMessage
     */
    public function setIndexNo( int $indexNo ) : TestMessage
    {
        $this->indexNo = $indexNo;
        return $this;
    }

    /**
     * @return string
     */
    public function getText() : string
    {
        return $this->text;
    }

    /**
     * @param string $text
     * @return TestMessage
     */
    public function setText( string $text ) : TestMessage
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return int
     */
    public function getPriority() : int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return TestMessage
     */
    public function setPriority( int $priority ) : TestMessage
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return float
     */
    public function getLoadTime() : float {
        return $this->loadTime;
    }

    /**
     * @param float $loadTime
     * @return TestMessage
     */
    public function setLoadTime( float $loadTime ) : TestMessage {
        $this->loadTime = $loadTime;
        return $this;
    }
}
class TestMessage2
{
    /**
     * TestMessage2 constructor.
     */
    public function __construct()
    {
        $this->closureProp21 = static function() {
            return ' closureProp21';
        };
        $this->getClosureProp21 = function()
        {
            return $this->closureProp21;
        };
        $this->closureProp22 = [
            function () {
                return ' closureProp22';
            }
        ];
        $this->prop23 = new stdClass();
    }

    /**
     * @return string
     */
    public function toString() : string
    {
        $str  = $this->getClosureProp21();
        $str .= $this->closureProp22[0]();
        $str .= ' ' . get_class( $this->prop23 );
        return $str;
    }
}
