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
// $Id: register_argc_argv_on.php,v 1.1 2005/12/29 04:34:04 aidan Exp $


/**
 * Emulate enviroment register_argc_argv=on
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/magic_qutoes
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 1.1 $
 */
if (!isset($GLOBALS['argc'], $GLOBALS['argv'])) {
    $GLOBALS['argc'] = $_SERVER['argc'];
    $GLOBALS['argv'] = $_SERVER['argv'];

    // Register the change
    ini_set('register_argc_argv', 'on');
}
