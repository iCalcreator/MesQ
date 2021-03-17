<?php
/**
 * MesQ, PHP disk based message lite queue manager
 *
 * Copyright 2021 Kjell-Inge Gustafsson, kigkonsult, All rights reserved
 * Link      https://kigkonsult.se
 * Package   MesQ
 * Version   1.05
 * License   LGPL
 *
 * This file is a part of MesQ.
 *
 * This php script contains common functions for messageLoader/messageReader
 */
declare( strict_types = 1 );
namespace Kigkonsult\MesQ;

use function is_dir;
use function is_readable;
use function is_writable;
use function microtime;
use function number_format;
use function rand;

/**
 * @param int $length
 * @return string
 */
function generateRandomString( int $length ) : string
{
    static $characters = ' !"#¤%&/()=?*><|;,:._-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    static $charLngth  = 83;
    $randomString      = null;
    for( $i = 0; $i < $length; $i++ ) {
        $randomString .= $characters[rand( 0, $charLngth)];
    }
    return $randomString;
}

function getArgv1and2( array $argv )
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

function isArgSet( $arg, $ix )
{
    static $SP0 = '';
    return ( isset( $arg[$ix] ) && ( $SP0 != $arg[$ix] ));
}
