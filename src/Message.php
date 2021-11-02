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

use Exception;
use RuntimeException;
use Serializable;

class Message implements Serializable
{
    /**
     * @var mixed
     */
    private mixed $data;

    /**
     * @param mixed $data
     * @return static
     */
    public static function factory( mixed $data ) : self
    {
        $instance = new self();
        $instance->setData( $data );
        return $instance;
    }

    /**
     * @inheritDoc
     * @retun string
     * @throws RuntimeException
     */
    public function serialize() : string
    {
        static $FMT2 = 'serialize error on (json) %s';
        try {
            $msg = serialize( $this->data );
        }
        catch( Exception $e ) { // closure detected ?
            throw new RuntimeException(
                sprintf( $FMT2, print_r( $this->data, true )),
                211,
                $e
            );
        }
        return $msg;
    }

    /**
     * @inheritDoc
     * @param $data string
     * @return void
     * @throws RuntimeException
     */
    public function unserialize( $data ) : void
    {
        static $FMT1    = 'Error on unserialize of data %s';
        static $FMT2    = 'False result on unserialize of data %s';
        static $OPTIONS = [ 'allowed_classes' => true ];
        try {
            $this->data = unserialize( $data, $OPTIONS );
        }
        catch( Exception $e ) {
            $msg = sprintf( $FMT1, $data );
            throw new RuntimeException( $msg, null, $e );
        }
        if(( false === $this->data ) && ( $data !== serialize( false ))) {
            throw new RuntimeException( sprintf( $FMT2, $data ));
        }
    }

    /**
     * @return mixed
     */
    public function getData() : mixed
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return Message
     */
    public function setData( mixed $data ) : Message
    {
        $this->data = $data;
        return $this;
    }
}
