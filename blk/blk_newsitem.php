<?php
/**
 * News item output
 *
 * @author vdb
 * @version CVS: $Id$
 */
 
require_once('./lib/itemdetails.php');
class blk_newsitem extends itemdetails
{
	/**
	 * Constructor (php4)
	 */
	function blk_newsitem()
	{
		$this->__construct();
	}

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $current_lang, $tr;
		
		parent::__construct('dao_pages');
	} // end constructor

	/**
	 * Assign rows in template 
	 */
	function tpl_assign_rows(&$res)
	{
		global $current_lang, $tr;
		
		$row = $res->fetchrow(DB_FETCHMODE_ASSOC, 0); // rewind result set
		if (! $row) { return; }

		$this->content->item['title'] = $row['title_'.$current_lang];
		$this->content->item['content'] = $row['content_'.$current_lang];		
	} // function tpl_assign_rows(&$res)
} // class blk_news extends block
?>