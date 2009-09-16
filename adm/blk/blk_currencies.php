<?php
/*
 * currency management class
 *
 * @package admin
 * @author vdb
 * @version CVS: $Id$
 */

require_once('./lib/crud_form.php');

class blk_currencies extends crud_form
{
	/**
	 * Constructor (php4)
	 */
	function blk_currencies()
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
				'rate',
			);
		$this->dao->col['rate']['qf_rules'] = 
			array('nonzero' => 
			str_replace('%s', $this->dao->col['rate']['qf_label'].':', $GLOBALS['_DB_TABLE']['qf_rules']['required'])
		);

		$this->include_cols_grid = $this->include_cols_form;
	} // end constructor
}
?>
