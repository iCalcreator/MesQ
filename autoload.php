<?php
/**
 * MesQ, lite PHP disk based message queue manager
 *
 * This file is a part of MesQ.
 *
 * @author    Kjell-Inge Gustafsson, kigkonsult <ical@kigkonsult.se>
 * @copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * @link      https://kigkonsult.se
 * @version   1.2
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
spl_autoload_register(
    function( string $class ) {
        static $BS      = '\\';
        static $PHP     = '.php';
        static $PREFIX  = 'Kigkonsult\\MesQ\\';
        static $SRC     = 'src';
        static $SRCDIR  = null;
        static $TEST    = 'test';
        static $TESTDIR = null;
        if( is_null( $SRCDIR )) {
            $SRCDIR  = __DIR__ . DIRECTORY_SEPARATOR . $SRC . DIRECTORY_SEPARATOR;
            $TESTDIR = __DIR__ . DIRECTORY_SEPARATOR . $TEST . DIRECTORY_SEPARATOR;
        }
        if( 0 != strncmp( $PREFIX, $class, 16 )) {
            return false;
        }
        $class = substr( $class, 16 );
        if( false !== strpos( $class, $BS )) {
            $class = str_replace( $BS, DIRECTORY_SEPARATOR, $class );
        }
        $file = $SRCDIR . $class . $PHP;
        if( file_exists( $file )) {
            include $file;
        }
        else {
            $file = $TESTDIR . $class . $PHP;
            if( file_exists( $file )) {
                include $file;
            }
        }
    }
);
