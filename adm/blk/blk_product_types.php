<?php
/*
 * product_types CRUD form 
 *
 * @package admin
 * @author vdb
 * @version CVS: $Id$
 */

require_once('./lib/crud_form.php');

class blk_product_types extends crud_form
{
	/**
	 * Constructor (php4)
	 */
	function blk_product_types()
	{
		$this->__construct();
	}

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $current_lang, $available_langs, $tr;
		parent::__construct();
		$this->view = 'parent_join';

		//$this->search_fields = array('name_'.$current_lang);
		//$this->add_record_form = false;	
	} // end constructor

	/**
	 * Define the set of fields to show on form and grid (overwtite in child class)
	 */
	function define_data_fields()
	{
		global $current_lang, $available_langs, $tr;

		// exclude some fields from the form
		$langs_to_remove = array_diff($available_langs, (array)$current_lang);
		$fields_to_remove= array('name_', 'description_');

		foreach($langs_to_remove as $lang)
		{
			foreach($fields_to_remove as $fld)
			{
				$this->exclude_cols_grid[] = $fld . $lang;
				$this->exclude_cols_form[] = $fld . $lang;
			}
		}
		//$this->exclude_cols_form[] = 'password2';
		$this->exclude_cols_grid[] = 'link';
		$this->exclude_cols_grid[] = 'priority';
	}

	/**
	 * Custom field definitions (overwrite in child class)
	 */
	function form_add_special_fields_before(&$row)
	{
		// This is root item
		$this->dao->col['parent_id']['qf_vals'][0] = '/';

		// get a sorted tree array to include in html-select element
		$m = $this->dao->get_sorted_tree_array();

		// now assign select options
		foreach($m as $id => $val)
		{
			$this->dao->col['parent_id']['qf_vals'][$val['id']] = 
				str_repeat('---', strlen($m[$id]['sort'])/12 - 1) . $val['name'];
		}
		return true;
	} // function form_add_special_fields_before(&$row)
}
?>
