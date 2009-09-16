<?php
/**
 * Product item output
 *
 * @author vdb
 * @version CVS: $Id$
 */
 
require_once('./lib/itemdetails.php');
class blk_productdetails extends itemdetails
{
	/**
	 * Constructor (php4)
	 */
	function blk_productdetails()
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
		$this->view = 'all_fk_join';
		$this->id_arg_no = 2;
	} // end constructor

	/**
	 * Assign rows in template 
	 */
	function tpl_assign_rows(&$res)
	{
		global $current_lang, $tr, $title;
		
		$row = $res->fetchrow(DB_FETCHMODE_ASSOC, 0); // rewind result set
		if (! $row) { return; }

		$this->content->item['description'] = $row['description_'.$current_lang];
		$this->content->item['stock'] = $row['stock'] ? $tr->t('warehouse') : $tr->t('order');
		$this->content->item['price'] = (int) ($row['price'] * SITE_CURRENCY_RATE) .' '. SITE_CURRENCY_NAME;
		$this->content->item['alt'] = '';

		if (isset($this->content->item['name']) && trim($this->content->item['name']))
		{
			$title = SITE_NAME .TITLE_SEPARATOR. $this->content->item['name'];
			$this->content->item['alt'] = $this->content->item['name'];
			if (isset($this->content->item['brand_fk']))
			{
				$title .= ' '. $this->content->item['brand_fk'];
				$this->content->item['alt'] .= ' '. $this->content->item['brand_fk'];
				if (isset($this->content->item['model_fk']))
				{
					$title .= ' '. $this->content->item['model_fk'];
					$this->content->item['alt'] .= ' '. $this->content->item['model_fk'];
				}
			}
			
			//$page->title = $this->content->rows[0]['type_fk'];
		}

	} // function tpl_assign_rows(&$res)
} // class blk_news extends block
?>