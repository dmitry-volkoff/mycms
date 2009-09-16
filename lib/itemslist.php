<?php
/**
 * DB Block
 *
 * @author vdb
 * @version CVS: $Id$
 */
class itemslist extends block
{
	/**
	 * Columns to include in the form
	 */    
	var $include_cols_form = array();

	/**
	 * View name
	 */    
	var $view = 'all';

	/**
	 * Result Filter
	 */    
	var $filter = null;

	/**
	 * Sort Order
	 */    
	var $order = null;

	/**
	 * Records per page
	 */    
	var $rows_per_page = DATAGRID_ROWS_PER_PAGE;

	/**
	 * Records per row when rendering as table
	 */    
	var $records_per_row = 1;

	/**
	 * Total number of rows in the dataset
	 */    
	var $totalItems;

	/**
	 * Pager arg No_
	 */    
	var $pager_arg_no = 1;

	/**
	 * Pager file name mask
	 */    
	var $pager_file_name = '/%d';
	
	/**
	 * Absolute path where to upload photos
	 */
	var $upload_photo_abs_path;

	/**
	 * Absolute path where to upload thumbnails
	 */
	var $upload_thumb_abs_path;

	/**
	 * Image type we are using
	 */
	var $img_type = 'jpg';

	/**
	 * Thumbnail image type
	 */
	var $thumb_type = 'jpg';

	/**
	 * Thumbnail image size
	 */
	var $thumb_size = THUMBNAIL_MIDDLE_SIZE;


	/**
	 * Constructor (php4)
	 */
	function itemslist($db = '')
	{
		$this->__construct($db);
	}

	/**
	 * Constructor
	 */
	function __construct($db = '')
	{
		global $current_lang, $tr;
	
		$this->template = get_class($this) .'.html'; 
		if (! is_readable('./tpl/'.$this->template)) 
		{ 
			$this->template = 'blk_itemslist.html'; 
		}
		
		// find current template
		$q1 = ''; $arg_num =& common::arg_num();
		if ($arg_num != 0)
		{
			for($i = $arg_num; $i > 0; $i--) 
			{
				// try find on disk files like 'name_arg1_arg2_arg3' etc.
				$q1 = str_replace('/','_', common::narg($i));
				//echo 'q1='.$q1."\n<br />";
				if (is_readable('./tpl/blk_'.$q1.'.html'))
				{
					$this->template = 'blk_'.$q1.'.html'; 
					break;
				}
			}
		}

		/**
		 * Construct pager_file_name (pager[options][fileName])
		 */
		//$this->pager_file_name = common::get_module_name() .'/%d';
		$this->pager_file_name = common::get_module_name();
		for($i = 1; $i < $this->pager_arg_no; $i++)
		{
			$this->pager_file_name .= '/'.(int) common::arg($i);
		}
		$this->pager_file_name .= '/%d';
		
		// initialize main dao object
		if (empty($db))
		{
			$db = 'dao_'. substr(get_class($this), 4);
			//$dao_name = 'dao_pages';
		}
		require_once('./dao/'.$db.'.php');

		$this->dao =& new $db;
		//$dao =& new $dao_name($dao_name, 'alter');
	
		if ($this->dao->error) 
		{
			die('dao error in '. get_class($this) .': '.$this->dao->error->message);
		}
	
		$this->dao->fetchmode = DB_FETCHMODE_ASSOC;
	
		// change sql where to exclude items marked as 'hide'
		//$this->dao->sql['all_fk_join']['where'] = '(hide IS NULL OR ! hide)';

		/**
		 * Default to show all columns; overwrite in child class.
		 */
		$this->include_cols_form = array_keys($this->dao->col);

		// photo upload path
		$this->upload_photo_abs_path = $_SERVER['DOCUMENT_ROOT'] .'/'. UPLOAD_PHOTO_REL_PATH;
		// photo upload path
		$this->upload_thumb_abs_path = $_SERVER['DOCUMENT_ROOT'] .'/'. UPLOAD_THUMB_REL_PATH;
	} // end constructor


	/**
	 * Return html pager for the dataset
	 */
	function get_pager()
	{
		include_once('Pager/Pager.php');
		$pager_options = array();
		$pager_options['mode'] = 'Sliding';
		$pager_options['urlVar'] = 'page';
		$pager_options['delta'] = 2;
		$pager_options['perPage'] = (int) $this->rows_per_page;
		$pager_options['separator'] = '|';
		$pager_options['prev'] = '&laquo;';
		$pager_options['next'] = '&raquo;';
		$pager_options['append'] = false;
		$pager_options['fileName'] = $this->pager_file_name;
		
		$pager_options['totalItems'] = $this->totalItems;
		
		$currentPage = common::arg($this->pager_arg_no);
		//$this->content->debug .= ' currentPage: '.$currentPage .' argno: '.$this->pager_arg_no .' a:'.(int)common::arg(3);
		if (! isset($currentPage) || ! $currentPage) { $currentPage = 1; }
		if ($currentPage > ceil($this->totalItems/$this->rows_per_page)) { $currentPage = 1; }
		
		$pager_options['currentPage'] = $currentPage;
		
		$pager =& Pager::factory($pager_options);
		return $pager->links;
	}


	/**
	 * Assign rows in template (default)
	 */
	function __tpl_assign_rows(&$res)
	{
		global $current_lang;
		
		$i = 0;
		while($row = $res->fetchrow())
		{
			$this->content->rows[$row['id']] = $row;

			if (isset($row['name_'.$current_lang]))
			{
				$this->content->rows[$row['id']]['name'] = $row['name_'.$current_lang];
			}
			//echo 'name: '.$row['name_'.$current_lang]."\n<br />";
		
			/**
			 * Check thumbnails and create if needed
			 */
			$tmp = $this->create_thumbnail($row['id'], $this->thumb_size);
			
			$thumbs_dir = UPLOAD_THUMB_REL_PATH.'/'. substr(get_class($this->dao), 4) .'/'.$this->thumb_size.'/'.$row['id'];

			$this->content->rows[$row['id']]['photo_file'] = $thumbs_dir .'/1.'. $this->thumb_type; 
			$this->content->rows[$row['id']]['split_start'] = ! fmod($i, $this->records_per_row); 
			$this->content->rows[$row['id']]['split_end'] = ! fmod($i + 1, $this->records_per_row);
			
			$size = @getimagesize($this->content->rows[$row['id']]['photo_file']);
			$this->content->rows[$row['id']]['photo_width'] = 
				($size[0] > $this->thumb_size) ? $this->thumb_size : $size[0];
			$i++;
		}
	}	

	/**
	 * Assign row in template (overwrite in child classes)
	 */
	function tpl_assign_rows(&$res)
	{
		return true;
	}	


	/**
	 * Create thumbnail
	 */
	function create_thumbnail(&$id, $size = THUMBNAIL_SMALL_SIZE, $entry = 1)
	{
		include('./lib/create_thumbnail.php');
	} // function create_thumbnail(&$id, $size = THUMBNAIL_SMALL_SIZE)

	/**
	 * Create thumbnails (all available)
	 */
	function create_thumbnails(&$id, $size = THUMBNAIL_SMALL_SIZE)
	{
		include('./lib/create_thumbnails.php');
	} // function create_thumbnail(&$id, $size = THUMBNAIL_SMALL_SIZE)

	/**
	 * Assign additional content in template 
	 */
	function tpl_add_content()
	{
		return true;
	}
    

	/**
	 * Output to browser
	 */
	function output()
	{
		global $tpl, $db, $tr, $current_lang, $p;
		$out = '';

		$totalItems = $this->dao->selectCount($this->view, $this->filter);
		if (PEAR::isError($totalItems)) { die('dbblock dao error: '.$totalItems->getMessage()); }
		$this->totalItems = $totalItems;
		//echo '<pre>totalItems'.$totalItems.'</pre>';

		$currentPage = (int) common::arg($this->pager_arg_no);
		if (! (isset($currentPage) && (int) $currentPage)) { $currentPage = 1; }
		//echo '<pre>$this->totalItems/$this->rows_per_page'.$this->totalItems.'/'.$this->rows_per_page.'</pre>';
		if ($currentPage > ceil($this->totalItems/$this->rows_per_page)) { $currentPage = 1; }

		$start = ($currentPage - 1) * $this->rows_per_page;
	
		// selectResult($view, $filter, $order, $start, $count);
		$res = $this->dao->selectResult($this->view, $this->filter, $this->order, $start, $this->rows_per_page);
		if (PEAR::isError($res)) { die('dbblock dao error: '.$res->getMessage()); }

		$this->__tpl_assign_rows($res);
		//$res = $this->dao->selectResult($this->view, $this->filter, $this->order, $start, $this->rows_per_page);
		$this->tpl_assign_rows($res);
		$this->tpl_add_content();
		
		$this->content->pager = $this->get_pager();
		
		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($this->content);
	} // function output()
} // class blk_news extends block
?>