<?php
/**
 * News
 *
 * @author vdb
 * @version CVS: $Id$
 */
 
class blk_articles extends blk_news
{
	/**
	 * Constructor (php4)
	 */
	function blk_articles()
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

		//$this->view = 'all_fk_join';
		$this->totalItems = 2;
		//$this->records_per_row = 2;
		//$this->rows_per_page = 2;
		//$this->order  = $this->dao->db->quoteIdentifier('date_enter'). ' DESC';
		$this->filter = $this->dao->db->quoteIdentifier('type').' = 3';
		$this->num_chars_trim_head_content = 150;
		$this->url_single_item = 'article';
	} // end constructor
} // class blk_articles extends block
?>