<?php
/**
 * item details
 *
 * @author vdb
 * @version CVS: $Id$
 */
class itemdetails extends block
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
	 * Total number of rows in the dataset
	 */    
	var $totalItems;

	/**
	 * item id arg No_ in url
	 */    
	var $id_arg_no = 1;

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
	var $thumb_size = THUMBNAIL_BIG_SIZE;


	/**
	 * Constructor (php4)
	 */
	function itemdetails($db = '')
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
			$this->template = 'blk_itemdetails.html'; 
		}	
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
	 * Assign rows in template (default)
	 */
	function __tpl_assign_rows(&$res)
	{
		global $current_lang;
		
		$row = $res->fetchrow();
		$this->content->item = $row;

		if (isset($row['name_'.$current_lang]))
		{
			$this->content->item['name'] = $row['name_'.$current_lang];
		}

		/**
		 * Check thumbnails and create if needed
		 */
		$this->content->photos = array();
		$photo_dir = UPLOAD_PHOTO_REL_PATH.'/'. substr(get_class($this->dao), 4) .'/'. $row['id'];
		$thumbs_dir = UPLOAD_THUMB_REL_PATH.'/'. substr(get_class($this->dao), 4) .'/'.$this->thumb_size.'/'.$row['id'];
		
		$this->create_thumbnails($row['id'], $this->thumb_size);
		
		//echo "thumb: ".$thumbs_dir."<br>";
		$d = @dir($thumbs_dir);
		if ($d) 
		{ 
			$fnames = array();
			while (false !== ($entry = $d->read())) 
			{
				if ($entry == '.' || $entry == '..') { continue; }
				$fnames[] = $entry;
			}
			$d->close();
			sort($fnames, SORT_NUMERIC);

			foreach($fnames as $key => $fname) 
			{
				$this->content->photos[(int)$fname]['photo_file'] = $thumbs_dir .'/'. (int)$fname .'.'. $this->thumb_type;
				$size = getimagesize($this->content->photos[(int)$fname]['photo_file']);
				$this->content->photos[(int)$fname]['photo_width']  = 
					($size[0] > $this->thumb_size) ? $this->thumb_size : $size[0];
			}
		}
	}	

	/**
	 * Assign rows in template (overwrite in child classes)
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

		$id = common::arg($this->id_arg_no);
		if (! (isset($id) && (int) $id)) { return $tr->t('Not found'); }
		$this->filter = $this->dao->db->quoteIdentifier($this->dao->table).'.id' .' = '. (int) $id; 
	
		// selectResult($view, $filter, $order, $start, $count);
		$res = $this->dao->selectResult($this->view, $this->filter);
		if (PEAR::isError($res)) { die(get_class($this) .' dao error: '.$res->getMessage()); }
		
		if (! $res->numRows()) { return $tr->t('Not found'); }
	    
		$this->__tpl_assign_rows($res);
		$this->tpl_assign_rows($res);
		$this->tpl_add_content();
		
		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($this->content);
	} // function output()
} // class blk_news extends block
?>