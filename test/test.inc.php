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
 *
 * This php script contains common functions for messageLoader/messageReader
 */
declare( strict_types = 1 );
namespace Kigkonsult\MesQ;

use Exception;

use function is_dir;
use function is_readable;
use function is_writable;
use function microtime;
use function number_format;
use function random_int;

/**
 * @param int $length
 * @return string
 * @throws Exception
 */
function generateRandomString( int $length ) : string
{
    static $characters = ' !"#¤%&/()=?*><|;,:._-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    static $charLngth  = 83;
    static $SP0        = '';
    $randomString      = $SP0;
    for( $i = 0; $i < $length; $i++ ) {
        $randomString .= $characters[random_int( 0, $charLngth)];
    }
    return $randomString;
}

/**
 * @param array $argv
 * @return array
 */
function getArgv1and2( array $argv ) : array
{
    if( ! isArgSet( $argv, 1 )) {
        exit;
    }
    $queueName = $argv[1];
    $directory = null;
    if( isArgSet( $argv, 2 )) {
        $directory = $argv[2];
        if( ! is_dir( $directory ) ||
            ! is_writable( $directory ) ||
            ! is_readable( $directory )) {
            exit;
        }
    }
    return [ $queueName, $directory ];
}

/**
 * @param float $time
 * @return string
 */
function getTime( float $time ) : string
{
    static $DOT = '.';
    static $SP0 = '';
    return number_format(( microtime( true ) - $time ), 6, $DOT, $SP0 );
}

/**
 * @param array $arg
 * @param int   $ix
 * @return bool
 */
function isArgSet( array $arg, int $ix ) : bool
{
    static $SP0 = '';
    return ( isset( $arg[$ix] ) && ( $SP0 !== $arg[$ix] ));
}
