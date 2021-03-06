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
// $Id: magic_quotes_gpc_off.php,v 1.2 2005/12/29 09:01:47 arpad Exp $


/**
 * Emulate enviroment magic_quotes_gpc=off
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/magic_quotes
 * @author      Aidan Lister <aidan@php.net>
 * @version     $Revision: 1.2 $
 */
if (get_magic_quotes_gpc()) {
    // Recursive stripslashes function
    function php_compat_stripslashesr($value)
    {
        if (!is_array($value)) {
            return stripslashes($value);
        }
        $result = array();
        foreach ($value as $k => $v) {
            $result[stripslashes($k)] = php_compat_stripslashesr($v);
        }
        return $value;
    }

    $_POST = array_map('php_compat_stripslashesr', $_POST);
    $_GET = array_map('php_compat_stripslashesr', $_GET);
    $_COOKIE = array_map('php_compat_stripslashesr', $_COOKIE);

    // Register the change
    ini_set('magic_quotes_gpc', 'off');
}
