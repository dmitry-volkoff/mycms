<?php
/**
 * List examples
 *
 * @author vdb
 * @version CVS: $Id$
 */

require_once('./lib/itemslist.php');
class blk_examples extends itemslist
{
	/**
	 * Constructor (php4)
	 */
	function blk_examples()
	{
		$this->__construct();
	}

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $current_lang, $tr;

		parent::__construct('dao_examples');

		if (! isset($this->pager_arg_no) || $this->pager_arg_no < 2)
		{
			$this->pager_arg_no = 1; // type/page
		}
		//$this->rows_per_page = 10;
		$this->view = 'all_fk_join';
		//$this->filter = $this->dao->table .'.type = '.(int) common::arg(1);
		//$this->pager_file_name = common::get_module_name() .'/'.(int) common::arg(1) .'/%d';
		$this->records_per_row = 3;
		$this->rows_per_page = 2 * $this->records_per_row;

	} // end constructor


	/**
	 * Assign rows in template 
	 */

	function tpl_assign_rows(&$res)
	{
		global $current_lang, $page, $title;

		$row = $res->fetchrow(DB_FETCHMODE_ASSOC, 0); // rewind result set
		if (! $row) { return; }

		do
		{
			$this->content->rows[$row['id']]['link'] = 
				common::get_url_path('exdetails/'. (int) common::arg(1) .'/'. $row['id']);
			//common::get_url_path('productdetails/'. $row['id']);

			$this->content->rows[$row['id']]['price'] = (int) ($row['price'] * SITE_CURRENCY_RATE) .' '. SITE_CURRENCY_NAME;
			//$type_fk = $this->content->rows[$row['id']]['type_fk'];

			/**
			 * Check thumbnails existence and create them if needed
			 */
			$this->create_thumbnail($row['id'], THUMBNAIL_MIDDLE_SIZE);

		} while($row = $res->fetchrow());

		//echo '<pre>';
		//print_r($this->content->rows);
		//echo '</pre>';
	} // function tpl_assign_rows(&$res)
} // class blk_productslist extends itemslist
?>
