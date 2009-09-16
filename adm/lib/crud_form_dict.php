<?php
/*
 * CRUD form dictionary class - parent of all dictionary manupulation interfaces
 *
 * @package common
 * @author vdb
 * @version CVS: $Id$
 */

require_once('./lib/crud_form.php');

class crud_form_dict extends crud_form
{
    /**
     * Constructor (php4)
     */
    function crud_form_dict()
    {
	$this->__construct();
    }

    /**
     * Constructor
     */
    function __construct()
    {
	global $current_lang, $tr;
	parent::__construct();
	$this->include_cols_form = 
	array(
	    'id',
	    'name_'.$current_lang,
	);
	$this->include_cols_grid = $this->include_cols_form;	
    } // end constructor
}
?>