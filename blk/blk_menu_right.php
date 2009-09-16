<?php
/**
 * Info Block.
 *
 * @author vdb
 * @version CVS: $Id$
 */

class blk_menu_right extends block
{
	var $page_name = ''; 
	var $dao_name = 'pages'; // db_table 

	function __construct()
	{
		$this->cache = false;
		$this->template = 'blk_page.html';
		if (is_readable('./tpl/'.get_class($this) .'.html')) 
		{ 
			$this->template = get_class($this) .'.html'; 
		}
	}

	function init($page_name)
	{
		$this->page_name = $page_name; 
	}

	function output()
	{
		global $tpl, $tr, $q;

		$page =& new page;

		if ($q === DEFAULT_URL_PATH)
		{
			$page->main_add = 1;
		}

		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($page);
	} // function output()
} // class blk_page extends block
?>
