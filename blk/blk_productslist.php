<?php
/**
 * List products
 *
 * @author vdb
 * @version CVS: $Id$
 */
 
require_once('./lib/itemslist.php');
class blk_productslist extends itemslist
{
	/**
	 * Constructor (php4)
	 */
	function blk_productslist()
	{
		$this->__construct();
	}

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $current_lang, $tr;
		
		parent::__construct('dao_products');

		if (! isset($this->pager_arg_no) || $this->pager_arg_no < 2)
		{
			$this->pager_arg_no = 2; // type/page
		}
		//$this->rows_per_page = 10;
		$this->view = 'all_fk_join';
		$this->filter = $this->dao->table .'.type = '.(int) common::arg(1); 
		//$this->pager_file_name = common::get_module_name() .'/'.(int) common::arg(1) .'/%d';
		$this->records_per_row = 1;
		$this->rows_per_page = 15 * $this->records_per_row;

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
			//$this->content->rows[$row['id']] = $row;

			if (isset($row['name_'.$current_lang]))
			{
				$this->content->rows[$row['id']]['name'] = $row['name_'.$current_lang];
			}
			$this->content->rows[$row['id']]['link'] = 
				common::get_url_path('productdetails/'. (int) common::arg(1) .'/'. $row['id']);
				//common::get_url_path('productdetails/'. $row['id']);
				
			$this->content->rows[$row['id']]['price'] = (int) ($row['price'] * SITE_CURRENCY_RATE) .' '. SITE_CURRENCY_NAME;
			$type_fk = $this->content->rows[$row['id']]['type_fk'];
			
		} while($row = $res->fetchrow());
		
		if (isset($type_fk) && $type_fk)
		{
			//$page->title .= TITLE_SEPARATOR . $this->content->rows[0]['type_fk'];
			//$title .= TITLE_SEPARATOR . $this->content->rows[0]['type_fk'];
			$title = SITE_NAME .TITLE_SEPARATOR. $type_fk;
			$page->title = $type_fk;

			if ((int)common::arg(2))
			{
				$row = $res->fetchrow(DB_FETCHMODE_ASSOC, 0); // rewind result set
				$title .= ' '.$row['brand_fk'];
				$page->title .= ' '.$row['brand_fk'];
			}
		}
		
		//echo '<pre>';
		//print_r($this->content->rows);
		//echo '</pre>';
	} // function tpl_assign_rows(&$res)
} // class blk_productslist extends itemslist
?>