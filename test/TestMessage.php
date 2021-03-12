<?php
/**
 * MesQ, lite PHP disk based message queue manager
 *
 * Copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * Link      https://kigkonsult.se
 * Package   MesQ
 * Version   1.0
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
 * This file is a part of Gectrl.
 */
declare( strict_types = 1 );
namespace Kigkonsult\MesQ;

use function intval;
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
    private $indexNo = 0;

    /**
     * @var string
     */
    private $text = null;

    /**
     * Message priority 0-99
     *
     * @var int
     */
    private $priority = 0;

    /**
     * @var float
     */
    private $loadTime = 0;

    /**
     * TestMessage constructor.
     *
     * @param int    $indexNo
     * @param string $text
     * @param int    $priority
     */
    public function __construct( int $indexNo, string $text, $priority = null )
    {
        $this->setIndexNo( $indexNo );
        $this->setText( $text );
        if( null !== $priority ) {
            $this->setPriority( intval( $priority ));
        }
        $this->setLoadTime( microtime( true ));
    }

    /**
     * TestMessage factory method
     *
     * @param int    $indexNo
     * @param string $text
     * @return TestMessage
     */
    public static function factory( int $indexNo, string $text )
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
            str_pad((string) $this->getPriority(), 2, $SP1, STR_PAD_LEFT );
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
