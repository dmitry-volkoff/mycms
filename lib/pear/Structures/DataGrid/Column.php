<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2005 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at                              |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Andrew Nagy <asnagy@webitecture.org>                         |
// +----------------------------------------------------------------------+
//
// $Id: Column.php,v 1.23 2006/02/24 13:40:29 wiesemann Exp $

/**
 * Structures_DataGrid_Column Class
 *
 * This class represents a single column for the DataGrid.
 *
 * @version  $Revision: 1.23 $
 * @author   Andrew S. Nagy <asnagy@webitecture.org>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */
class Structures_DataGrid_Column
{
    /**
     * The name of the column
     * @var string
     */
    var $columnName;

    /**
     * The name of the field to map to
     * @var string
     */
    var $fieldName;

    /**
     * The field name to order by. Optional
     * @var array
     */
    var $orderBy;

    /**
     * The attributes to use for the cell. Optional
     * @var array
     */
    var $attribs;

    /**
     * The value to be used if a cell is empty
     * @var string
     */
    var $autoFillValue;

    /**
     * A function to be called for each cell to modify the output
     * @var array
     */
    var $formatter;

    /**
     * Constructor
     *
     * Creates default table style settings
     *
     * @param   string      $columnName     The name of the column to be printed
     * @param   string      $fieldName      The name of the field for the column
     *                                      to be mapped to
     * @param   string      $orderBy        The field to order the data by
     * @param   string      $attribs        The HTML attributes for the TR tag
     * @param   boolean     $autoFill       Whether or not to use the autoFill
     * @param   string      $autoFillValue  The value to use for the autoFill
     * @param   mixed       $formatter      A defined function to call upon
     *                                      rendering to allow for special
     *                                      formatting.  This allows for
     *                                      call-back function to print out a 
     *                                      link or a form element, or whatever 
     *                                      you can possibly think of.
     * @see http://www.php.net/manual/en/language.pseudo-types.php
     * @access  public
     */
    function Structures_DataGrid_Column($columnName, $fieldName = null,
                                        $orderBy = null, $attribs = array(),
                                        $autoFillValue = null,
                                        $formatter = null)
    {
        $this->columnName = $columnName;
        $this->fieldName = $fieldName;
        $this->orderBy = $orderBy;
        $this->attribs = $attribs;
        $this->autoFillValue = $autoFillValue;
        $this->formatter = $formatter;
    }

    /**
     * Set auto fill value
     *
     * Defines a value to be printed if a cell in the column is null.
     *
     * @param   string      $str        The value to use for the autoFill
     * @access  public
     */
    function setAutoFillValue($str)
    {
        $this->autoFillValue = $str;
    }

    /**
     * Set Formatter
     *
     * Defines the function and paramters to be called by the formatter method.
     *
     * @param   mixed   Callback PHP pseudo-type (Array or String)
     * @see http://www.php.net/manual/en/language.pseudo-types.php
     * @access  public
     */
    function setFormatter($str)
    {
        $this->formatter = $str;
    }

    /**
     * Formatter
     *
     * Calls a predefined function to develop custom output for the column. The
     * defined function can accept paramaters so that each cell in the column
     * can be unique based on the record.  The function will also automatically
     * receive the record array as a parameter.  All parameters passed into the
     * function will be in one array.
     *
     * Example:
     * <code>
     * <?php
     * ...
     * $linkTitle = 'Edit';
     * $column->formatter = 'printLink($linkTitle=' . $linkTitle . ')';
     * $dg->addColumn($column);
     * $dg->render();
     * function printLink($params) {
     *      extract($params);
     *      return '<a href="edit.php?id=' . $record['id'] . ">' . $linkTitle . 
     *             '</a>';
     * }
     * ?>
     * </code>
     *
     * @access  public
     */
    function formatter($record)
    {
        // Define the parameter list
        $paramList = array();
        $paramList['record'] = $record;
        $paramList['fieldName'] = $this->fieldName;
        $paramList['columnName'] = $this->columnName;
        $paramList['orderBy'] = $this->orderBy;
        $paramList['attribs'] = $this->attribs;

        // $this->formatter may be an array with a class name and the formatter
        // ==> split into class name and the formatter
        $class = '';
        if (is_array($this->formatter)) {
            $class = $this->formatter[0];
            $formatter = $this->formatter[1];
        } else {
            $formatter = $this->formatter;
        }

        // Determine callback and additional parameters
        if (is_string($formatter) and $size = strpos($formatter, '(')) {
            $orig_formatter = $formatter;
            // Retrieve the name of the function to call
            $formatter = substr($formatter, 0, $size);
            if (strstr($formatter, '->')) { 
                $formatter = explode('->', $formatter);
            } elseif (strstr($formatter, '::')) {
                $formatter = explode('::', $formatter);
            }

            // Build the list of parameters
            $length = strlen($orig_formatter) - $size - 2;
            $parameters = substr($orig_formatter, $size + 1, $length);
            $parameters = ($parameters === '') ? array() : split(',', $parameters);

            // Process the parameters
            foreach($parameters as $param) {
                if ($param != '') {
                    $param = str_replace('$', '', $param);
                    if (strpos($param, '=') != false) {
                        $vars = split('=', $param);
                        $paramList[trim($vars[0])] = trim($vars[1]);
                    } else {
                        $paramList[$param] = $$param;
                    }
                }
            }
        }

        // $this->formatter may be an array with a class name and the formatter
        // ==> join class name and the formatter back into an array
        if ($class !== '') {
            $formatter = array($class, $formatter);
        }

        // Call the formatter
        if (is_callable($formatter)) {
            $result = call_user_func($formatter, $paramList);
        } else {
            $result = false;
            PEAR::raiseError('Unable to process formatter '.implode('+', $formatter), '1',
                             PEAR_ERROR_TRIGGER);
        }

        return $result;
    }

}

?>
