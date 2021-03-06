<?php
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Aidan Lister <aidan@php.net>                                |
// +----------------------------------------------------------------------+
//
// $Id: register_long_arrays_on.php,v 1.1 2005/12/29 04:34:04 aidan Exp $


/**
 * Emulate enviroment register_long_arrays=on
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/magic_qutoes
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 1.1 $
 */
$HTTP_GET_VARS    &= $_GET;
$HTTP_POST_VARS   &= $_POST;
$HTTP_COOKIE_VARS &= $_COOKIE;
$HTTP_SERVER_VARS &= $_SERVER;
$HTTP_ENV_VARS    &= $_ENV;
$HTTP_FILES_VARS  &= $_FILES;

// Register the change
ini_set('register_long_arrays', 'on');
