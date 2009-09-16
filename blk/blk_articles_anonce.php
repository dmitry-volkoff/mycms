<?php
/**
 * News
 *
 * @author vdb
 * @version CVS: $Id$
 */
 
//require_once('./lib/itemslist.php');
class blk_articles_anonce extends blk_articles
{
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
		//$this->filter = $this->dao->db->quoteIdentifier('type').' = 3';

	} // end constructor
} // class blk_articles extends block
?>