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
// $Id: array_intersect_key.php,v 1.8 2006/02/07 11:44:44 arpad Exp $


/**
 * Replace array_intersect_key()
 *
 * @category    PHP
 * @package     PHP_Compat
 * @link        http://php.net/function.array_intersect_key
 * @author      Tom Buskens <ortega@php.net>
 * @version     $Revision: 1.8 $
 * @since       PHP 5.0.2
 * @require     PHP 4.0.0 (user_error)
 */
function php_compat_array_intersect_key()
{
    $args = func_get_args();
    $array_count = count($args);
    if ($array_count < 2) {
        user_error('Wrong parameter count for array_intersect_key()', E_USER_WARNING);
        return;
    }

    // Check arrays
    for ($i = $array_count; $i--;) {
        if (!is_array($args[$i])) {
            user_error('array_intersect_key() Argument #' .
                ($i + 1) . ' is not an array', E_USER_WARNING);
            return;
        }
    }

    // Intersect keys
    $arg_keys = array_map('array_keys', $args);
    $result_keys = call_user_func_array('array_intersect', $arg_keys);
    
    // Build return array
    $result = array();
    foreach($result_keys as $key) {
        $result[$key] = $args[0][$key];
    }
    return $result;
}


// Define
if (!function_exists('array_intersect_key')) {
    function array_intersect_key()
    {
        $args = func_get_args();
        return call_user_func_array('php_compat_array_intersect_key', $args);   
    }
}
