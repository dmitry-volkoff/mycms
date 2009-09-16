<?php
/*
 * settings management class
 *
 * @package admin
 * @author vdb
 * @version CVS: $Id$
 */

require_once('./lib/crud_form.php');
class blk_settings extends crud_form 
{
	/**
	 * Constructor (php4)
	 */
	function blk_settings()
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
		$this->add_record_form = false;
		//$this->include_cols_grid = $this->include_cols_form;	
	} // end constructor
}
?>