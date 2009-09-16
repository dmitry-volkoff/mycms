<?php
/*
 * users CRUD form 
 *
 * @package admin
 * @author vdb
 * @version CVS: $Id$
 */

require_once('./lib/crud_form.php');

class blk_users extends crud_form
{
    /**
     * Constructor (php4)
     */
    function blk_users()
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
	
	$this->search_fields = array('login');
	$this->add_record_form = false;	
    } // end constructor

    /**
     * Define the set of fields to show on form and grid (overwtite in child class)
     */
    function define_data_fields()
    {
/*
	// exclude some fields from the form
	$langs_to_remove = array_diff($available_langs, (array)$current_lang);

	foreach($langs_to_remove as $lang)
	{
		$this->exclude_cols_grid[] = 'name_'.$lang;
		$this->exclude_cols_form[] = 'name_'.$lang;
	}
*/
	$this->exclude_cols_form[] = 'password2';
	$this->exclude_cols_grid[] = 'password2';

	$this->exclude_cols_grid[] = 'note';
	$this->exclude_cols_grid[] = 'pay_requisite';
	$this->exclude_cols_grid[] = 'pay_type';
	$this->exclude_cols_grid[] = 'city';
	$this->exclude_cols_grid[] = 'icq';
	$this->exclude_cols_grid[] = 'site';
	$this->exclude_cols_grid[] = 'phone';
	
	//$this->include_cols_form = array_diff($this->include_cols_form, $this->exclude_cols_form);
	//$this->include_cols_grid = array_diff($this->include_cols_grid, $this->exclude_cols_grid);

	//$this->include_cols_grid = $this->include_cols_form;

    }
}
?>
