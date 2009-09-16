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
// | Authors: Andrew Nagy <asnagy@webitecture.org>                        |
// |          Olivier Guilyardi <olivier@samalyse.com>                    |
// +----------------------------------------------------------------------+
//
// $Id: Common.php,v 1.21 2006/03/03 22:43:00 olivierg Exp $

/**
 * Structures_DataGrid_Renderer_Common Class
 *
 * Base class of all Renderer drivers
 *
 * Recognized options :
 *
 * - buildHeader            : whether to build the header (default : true)
 * - buildFooter            : whether to build the footer (default : true)
 * - fillWithEmptyRows      : ensures that all pages have the same number of 
 *                            rows (default : false) 
 * - numberAlign            : whether to right-align numeric values (default : true)
 * - defaultCellValue       : what value to put by default into empty cells
 * - defaultColumnValues    : per-column default cell value. This is an array
 *                            of the form : array(fieldName => value, ...)
 * - disableColumnSorting   : by default sorting is enabled on all columns. With this
 *                            option it is possible to disable sorting on specific columns.
 *                            This is an array of the form : array(fieldName, ...).
 *                            This option only affects drivers that support sorting.
 * - encoding               : the content encoding. If the mbstring extension is 
 *                            present the default value is set from 
 *                            mb_internal_encoding(), otherwise it is ISO-8859-1
 * 
 * --- DRIVER INTERFACE ---
 *
 * Methods that drivers MUST implement :
 *     - init()
 *     - buildBody()
 *     - flatten()
 * 
 * Methods that drivers MAY implement :    
 *     - Constructor
 *     - defaultCellFormatter()
 *     - buildHeader()
 *     - buildFooter()
 *     - finalize()
 *     - render()
 * 
 * Read/Write property :
 *     - $_container 
 * 
 * Read-Only properties    
 *     - $_columns
 *     - $_records
 *     - $_columnsNum
 *     - $_recordsNum
 *     - $_totalRecordsNum
 *     - $_currentSort
 *     - $_page
 *     - $_pageLimit
 *     - $_requestPrefix
 *     - $_sortableFields
 *     - $_options
 *     
 * Options that drivers should handle :
 *     - encoding
 *     - fillWithEmptyRows
 *     - numberAlign
 * 
 * @version  $Revision: 1.21 $
 * @author   Olivier Guilyardi <olivier@samalyse.com>
 * @access   public
 * @package  Structures_DataGrid
 * @category Structures
 */ 
class Structures_DataGrid_Renderer_Common 
{
    /**
     * Rendering container
     *
     * This variable can be of any type. Its type is specific to a given
     * driver.
     * 
     * Drivers can read and write to this property. 
     * 
     * @var mixed
     * @access protected
     */
    var $_container = null;

    /**
     * Columns' fields names and labels
     * 
     * Drivers can read the content of this property but must not change it.
     * 
     * @var array Structure: 
     *            array(<columnIndex> => array(field => <fieldName>, 
     *                                         label=> <label>), ...)
     *            Where <columnIndex> is zero-based
     * @access protected
     */
    var $_columns = array();

    /**
     * Records content
     *
     * Drivers can read the content of this property but must not change it.
     * 
     * @var array Structure: 
     *            array(
     *              <rowIndex> => array(
     *                 <columnIndex> => array (<cellValue>, ...), 
     *              ...), 
     *            ...)
     *            Where <rowIndex> and <columnIndex> are zero-based
     * @access protected
     */
    var $_records = array();

    /**
     * Fields/directions the data is currently sorted by
     *
     * Drivers can read the content of this property but must not change it.
     *
     * @var array Structure: array (fieldName => direction, ....)
     * @access protected
     */
    var $_currentSort = array();

    /**
     * Number of columns
     * @var int
     * @access protected
     */
    var $_columnsNum;

    /**
     * Number of records in the current page
     * 
     * Drivers can read the content of this property but must not change it.
     *
     * @var int
     * @access protected
     */
    var $_recordsNum;

    /**
     * Total number of records as reported by the datasource
     * 
     * Drivers can read the content of this property but must not change it.
     *
     * @var int
     * @access protected
     */
    var $_totalRecordsNum;

    /**
     * Current page
     * 
     * Drivers can read the content of this property but must not change it.
     *
     * @var int
     * @access protected
     */
    var $_page = 1;

    /**
     * Number of records per page
     * 
     * Drivers can read the content of this property but must not change it.
     *
     * @var int
     * @access protected
     */
    var $_pageLimit = null;

    /**
     * GET/POST/Cookie parameters prefix
     * 
     * Drivers can read the content of this property but must not change it.
     *
     * @var string
     * @access protected
     */
    var $_requestPrefix = '';

    /**
     * Which fields the datagrid may be sorted by
     * 
     * Drivers can read the content of this property but must not change it.
     *
     * @var array Field names
     * @access protected
     */
    var $_sortableFields = array();
    
    /**
     * Common and driver-specific options
     * 
     * Drivers can read the content of this property but must not change it.
     *
     * @var array
     * @access protected
     * @see Structures_DataGrid_Renderer_Common::setOption()
     * @see Structures_DataGrid_Renderer_Common::_addDefaultOptions()
     */
    var $_options = array();

    /**
     * Columns objects 
     * 
     * Beware : this is a private property, it is not meant to be accessed
     * by drivers. Use the $_columns property instead
     * 
     * @var array
     * @access private
     * @see Structures_DataGrid_Renderer_Common::_columns
     */
    var $_columnObjects = array();

    /**
     * Whether the datagrid has been built or not
     * @var bool
     * @access private
     * @see Structures_DataGrid_Renderer_Common::isBuilt()
     */
    var $_isBuilt = false;

    /**
     * Instantiate the driver and set default options
     *
     * Drivers may overload this method in order to change/add default options.
     *
     * @access  public
     * @see Structures_DataGrid_Renderer_Common::_addDefaultOptions()
     */
    function Structures_DataGrid_Renderer_Common()
    {
        $this->_options = array(
            
            /* Protected options, that the drivers should handle */    
            'encoding'              => function_exists('mb_internal_encoding')
                                       ? mb_internal_encoding() : 'ISO-8859-1',
            'fillWithEmptyRows'     => false,
            'numberAlign'           => true,
                                       
            /* Private options, that must not be accessed by drivers */
            'buildHeader'           => true, 
            'buildFooter'           => true,  
            'defaultCellValue'      => null,
            'defaultColumnValues'   => array(),
            'disableColumnSorting'  => array(), 

        );
    }

    /**
     * Adds some default options.
     *
     * This method is meant to be called by drivers. It allows adding some
     * default options. 
     *
     * @access protected
     * @param array $options An associative array of the from:
     *                       array(optionName => optionValue, ...)
     * @return void
     * @see Structures_DataGrid_Renderer_Common::setOption()
     */
    function _addDefaultOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Set options
     *
     * @param   mixed   $options    An associative array of the form :
     *                              array("option_name" => "option_value",...)
     * @access  public
     */
    function setOptions($options)
    {
        $this->_options = array_merge($this->_options, $options);
    }

    /**
     * Attach a container object
     *
     * @param object Container of the class supported by the driver
     * @access public
     */
    function setContainer(&$container)
    {
        $this->_container =& $container;
    }

    /**
     * Return the container used by the driver
     * 
     * @return object Container of the class supported by the driver
     * @access public
     */
    function &getContainer()
    {
        return $this->_container;
    }

    /**
     * Provide columns and records data
     * 
     * @param array $columns Array of Structures_DataGrid_Column objects
     * @param array $records 2D array of records values
     * @access public
     */
    function setData(&$columns, &$records)
    {
        $this->_columnObjects = &$columns;
        $this->_records = &$records;
    }
  
    /**
     * Specify how the datagrid is currently sorted
     *
     * @var array 
     * @param array $spec Form: array (fieldName => direction, ....)
     * @access public
     */
    function setCurrentSorting($spec)
    {
        $this->_currentSort = $spec;
    }

    /**
     * Specify page and row limits
     * 
     * @param int $currentPage Current page number
     * @param int $rowsPerPage Maximum number of rows per page
     * @param int $totalRowNum Total number of data rows
     * @access public
     */
    function setLimit($currentPage, $rowsPerPage, $totalRowNum) {
        $this->_page = $currentPage;
        $this->_pageLimit = $rowsPerPage;
        $this->_totalRecordsNum = $totalRowNum;
    }

    /**
     * Create or/and prepare the container
     *
     * Drivers are required to implement this method.
     *
     * This method is responsible for creating the container if it has not 
     * already been provided by the user with the setContainer() method.
     * It is where preliminary container setup also happens.
     *
     * @abstract
     * @access protected
     */
    function init()
    {
    }

    /**
     * Build the header 
     *
     * Drivers may optionally implement this method.
     *
     * @abstract
     * @access  protected
     * @return  void
     */
    function buildHeader() 
    {
    }

    /**
     * Build the body
     *
     * Drivers are required to implement this method.
     *
     * @abstract
     * @access  protected
     * @return  void
     */
    function buildBody()
    {
    }

    /**
     * Build the footer
     *
     * Drivers may optionally implement this method.
     *
     * @abstract
     * @access  protected
     * @return  void
     */
    function buildFooter() 
    {
    }

    /**
     * Finish building the datagrid.
     *
     * Drivers may optionally implement this method.
     *
     * @abstract
     * @access  protected
     * @return  void
     */
    function finalize()
    {
    }

    /**
     * Retrieve output from the container object 
     * 
     * Drivers are required to implement this method.
     *
     * This method is meant to retrieve final output from the container.
     * 
     * Usually the container is an object (ex: HTMLTable instance),
     * and the final output a string. 
     *
     * The driver knows how to retrieve such final output from a given 
     * container (ex: HTMLTable::toHTML()), and this is where to do it. 
     *
     * Sometimes the container may not be an object, but the final output
     * itself. In this case, this method should simply return the container.
     * 
     * This method mustn't output anything directly to the standard output.
     *  
     * @abstract
     * @return mixed Output
     * @access protected
     */
    function flatten()
    {
    }

    /**
     * Default formatter for all cells
     * 
     * Drivers may optionally implement this method.
     *
     * @abstract
     * @param string  Cell value 
     * @return string Formatted cell value
     * @access protected
     */
    function defaultCellFormatter ($value)
    {
        return $value;
    }

    /**
     * Build the grid
     *
     * Drivers must not overload this method. Pre and post-build operations
     * can be performed in init() and finalize()
     * 
     * @access public
     * @return void
     */
    function build()
    {
        $this->_columns = array();
        foreach ($this->_columnObjects as $index => $column)
        {
            if (!is_null($column->orderBy)) {
                $field = $column->orderBy;
                if (!in_array($field,$this->_sortableFields) and 
                    !in_array($field, $this->_options['disableColumnSorting'])
                   ) {
                    $this->_sortableFields[] = $field;
                }
            } else if (!is_null($column->fieldName)) {
                $field = $column->fieldName;
            } else {
                $field = $column->columnName;
            }

            $label = $column->columnName;

            if (isset($this->_options['defaultColumnValues'][$field])) {
                $column->setAutoFillValue($this->_options['defaultColumnValues'][$field]);
            } else if (!is_null($this->_options['defaultCellValue'])) {
                $column->setAutoFillValue($this->_options['defaultCellValue']);
            }

            if (isset($column->attribs) && 
                strtolower(get_class($this)) == 'structures_datagrid_renderer_htmltable')
            {
                if (!array_key_exists($field, $this->_options['columnAttributes'])) {
                    $this->_options['columnAttributes'][$field] = array();
                }
                $this->_options['columnAttributes'][$field] =
                    array_merge($this->_options['columnAttributes'][$field],
                                $column->attribs);
            }

            $this->_columns[$index] = compact('field','label');
        }

        $this->_columnsNum = count($this->_columns);
        $this->_recordsNum = count($this->_records);

        $this->init();

        if (is_null($this->_pageLimit)) {
            $this->_pageLimit = $this->_recordsNum;
        }

        for ($rec = 0; $rec < $this->_recordsNum; $rec++) {
            $content = array();
            foreach ($this->_columnObjects as $column) {
                $content[] = $this->recordToCell($column, $this->_records[$rec]);
            }
            $this->_records[$rec] = $content;
        }

        if ($this->_options['buildHeader']) {
            $this->buildHeader();
        }

        $this->buildBody();

        if ($this->_options['buildFooter']) {
            $this->buildFooter();
        }

        $this->finalize();

        $this->_isBuilt = true;
    }

    /**
     * Returns the output from the renderer (e.g. HTML table, XLS object, ...)
     *
     * Drivers must not overload this method. Output generation has to be 
     * implemented in flatten().
     * 
     * @access  public
     * @return  mixed    The output from the renderer
     */
    function getOutput()
    {
        $this->_isBuilt or $this->build();

        return $this->flatten();
    }

    /**
     * Render to the standard output
     *
     * This method may be overloaded by renderer drivers in order to prepare
     * writing to the standard output (like calling header(), etc...).
     * 
     * @access  public
     */
    function render()
    {
        echo $this->getOutput();
    }

    /**
     * Sets the rendered status.  This can be used to "flush the cache" in case
     * you need to render the datagrid twice with the second time having changes
     *
     * This is quite an obsolete method...
     * 
     * @access  public
     * @param   bool        $status     The rendered status of the DataGrid
     */
    function setRendered($status = false)
    {
        if (!$status) {
            $this->_isBuilt = false;
        }
        /* What are we supposed to do with $status = true ? */
    }   

    /**
     * Set the HTTP Request prefix
     * 
     * @param string $prefix The prefix string
     * @return void
     * @access public
     */
    function setRequestPrefix($prefix) {
        $this->_requestPrefix = $prefix;
    }

    /**
     * Perform record/column to cell intersection and formatting
     * 
     * @param  object $column The column object
     * @param  array  $record Array of record values
     * @return string Formatted cell value
     * @access private
     */
    function recordToCell(&$column, $record)
    {
        $value = '';
        if (isset($column->formatter) and !empty($column->formatter)) {
            $value = $column->formatter($record);
        } else if (isset($column->fieldName) and isset($record[$column->fieldName])) {
            $value = $this->defaultCellFormatter($record[$column->fieldName]);
        }

        if (empty($value) and !is_null($column->autoFillValue)) {
            $value = $column->autoFillValue; 
        }

        return $value;
    }

    /**
     * Query the grid build status 
     * 
     * @return bool Whether the grid has already been built or not
     * @access public
     */
    function isBuilt()
    {
        return $this->_isBuilt;
    }
}

?>
