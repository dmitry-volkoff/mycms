<?php
/**
 * News
 *
 * @author vdb
 * @version CVS: $Id$
 */
 
require_once('./lib/itemslist.php');
class blk_news extends itemslist
{
	var $num_chars_trim_head_content = 400;
	var $url_single_item = 'newsitem';
	
	/**
	 * Constructor (php4)
	 */
	function blk_news()
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

		$this->view = 'all_fk_join';
		$this->totalItems = 2;
		//$this->records_per_row = 2;
		$this->rows_per_page = 2;
		$this->order  = $this->dao->db->quoteIdentifier('date_enter'). ' DESC';
		$this->filter = $this->dao->db->quoteIdentifier('type').' = 2';

	} // end constructor
    

	/**
	 * Assign rows in template 
	 */
	function tpl_assign_rows(&$res)
	{
		global $current_lang;
		
		$row = $res->fetchrow(DB_FETCHMODE_ASSOC, 0); // rewind result set
		if (! $row) { return; }
		
		do 
		{
			$content_head = substr(strip_tags($row['content_'.$current_lang]), 0, $this->num_chars_trim_head_content);
			$last_space = strrpos($content_head, ' ');
			if (($last_space !== false) && $last_space)
			{
				$content_head = substr($content_head, 0, $last_space) .'...';
			}
			// FIXME: write function to get default value in case of absence $current_lang value
			$content_title = isset($row['title_'. $current_lang]) ?
				$row['title_'. $current_lang] : $row['title_en']; 
			$this->content->rows[$row['id']]['title'] = $content_title;
			$this->content->rows[$row['id']]['content'] = $content_head;
			$this->content->rows[$row['id']]['link'] = 
				common::get_url_path($this->url_single_item .'/'. $row['id']);	
				
		} while($row = $res->fetchrow());
		
	} // function tpl_assign_rows(&$res)
} // class blk_news extends block
?>