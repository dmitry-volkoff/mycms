<?php
/*
 * CRUD form base class - parent of all data manupulation interfaces
 *
 * @package common
 * @author vdb
 * @version CVS: $Id$
 */

require_once('Structures/DataGrid.php');
require_once 'HTML/QuickForm.php';
//require_once 'HTML/QuickForm/Renderer/Tableless.php';
require_once('../lib/dg_printer.php');

class crud_form extends block
{
	/**
	 * Primary index field name
	 */    
	var $id_field = 'id';

	/**
	 * Result Filter
	 */    
	var $filter = null;

	/**
	 * Array of sql conditions form wich Result Filter is made
	 */    
	var $where_add = array();

	/**
	 * View name
	 */    
	var $view = 'all_fk_join';

	/**
	 * Columns to include in the form
	 */    
	var $include_cols_form = array();

	/**
	 * Columns to include in the grid
	 */    
	var $include_cols_grid = array();

	/*
	 * Columns to exclude from the form
	 */    
	var $exclude_cols_form = array();

	/**
	 * Columns to exclude from the grid
	 */    
	var $exclude_cols_grid = array();

	/**
	 * HTML_Quick_Form object
	 */    
	var $form;
	//var $form_renderer;

	/**
	 * HTML_Quick_Form file element, representing photo/image uploads
	 */        
	var $form_file;

	/**
	 * Relative path where to upload photos
	 */
	var $upload_photo_rel_path = 'photo';

	/**
	 * Absolute path where to upload photos
	 */
	var $upload_photo_abs_path;

	/**
	 * Relative path where to upload thumbnails
	 */
	var $upload_thumb_rel_path = 'thumb';

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
	 * Whether to use html editor on textarea fields (true/false)
	 */
	var $textarea_editor = false;

	/**
	 * Whether to show "add record" form (true/false)
	 */
	var $add_record_form = true;

	/**
	 * Default starting date when querying with date interval condition (in seconds)
	 */
	var $query_default_time_start = 0;

	/**
	 * Array of data fields used on the search form
	 */
	var $search_fields = array();

	/**
	 * Flag whether to display priority arrows in the grid
	 */
	var $show_grid_priority = false;

	/**
	 * Constructor (php4)
	 */
	function crud_form()
	{
		$this->__construct();
	}

	/**
	 * Constructor
	 */
	function __construct($dao_name = '')
	{
		global $current_lang, $available_langs, $tr;

		// make 1st day of the month as a start date
		$this->query_default_time_start = time() - ((date("d") - 1) * 60*60*24);
		// make the begining of current week as a start date
		//$this->query_default_time_start = time() - 60*60*24*7;

		$this->template = get_class($this) .'.html'; 
		if (! is_readable('./tpl/'.$this->template)) 
		{ 
			$this->template = 'blk_default.html'; 
		}

		// initialize main dao object
		if (! $dao_name)
		{
			$dao_name = 'dao_' . substr(get_class($this), 4);
		}
		require_once('../dao/'.$dao_name.'.php');

		$this->dao =& new $dao_name;
		//$dao =& new $dao_name($dao_name, 'alter');

		if ($this->dao->error) 
		{
			die('dao error in '. get_class($this) .': '.$this->dao->error->message);
		}

		/**
		 * Default to show all columns; overwrite in child class.
		 */
		$this->include_cols_grid = $this->include_cols_form = array_keys($this->dao->col);

		// exclude some fields from the form
		$langs_to_remove = array_diff($available_langs, (array)$current_lang);

		foreach($langs_to_remove as $lang)
		{
			$this->exclude_cols_grid[] = 'name_'.$lang;
			$this->exclude_cols_form[] = 'name_'.$lang;
			if (isset($this->dao->col['description_' . $lang]))
			{
				$this->exclude_cols_grid[] = 'description_'.$lang;
				$this->exclude_cols_form[] = 'description_'.$lang;				
			}
		}
		
		if (isset($this->dao->col['priority']))
		{
			$this->exclude_cols_grid[] = 'priority';
			$this->exclude_cols_form[] = 'priority';
		}

		if (isset($this->dao->col['description_' . $current_lang]))
		{
			$this->exclude_cols_grid[] = 'description_'.$current_lang;
		}

		if (isset($this->dao->col['date_enter']))
		{
			$this->exclude_cols_grid[] = 'date_enter';
		}

		/**
		 * Define set of fields to show on form and grid (overwtite in child class)
		 */
		//$this->define_data_fields();

		// photo upload path
		$this->upload_photo_abs_path = realpath(dirname($_SERVER["SCRIPT_FILENAME"]).'/../').
			'/'. $this->upload_photo_rel_path;
		$this->upload_thumb_abs_path = realpath(dirname($_SERVER["SCRIPT_FILENAME"]).'/../').
			'/'. $this->upload_thumb_rel_path;

		// Default search fields
		if (isset($this->dao->col['name_' . $current_lang]))
		{
			$this->search_fields = array('name_'.$current_lang);
		}
		
		//$this->define_data_filter();
		//$this->filter_form();

	} // end constructor

	/**
	 * Create select elements using fk table values
	 */
	function create_fk_select()
	{
		global $current_lang;

		if (! isset($this->dao->fk) || ! count($this->dao->fk))
		{
			return false;
		}

		reset($this->dao->fk);
		foreach($this->dao->fk as $field => $table)
		{
			if (! is_readable('../dao/dao_'. $table.'.php')) 
			{ die('Cant find include file for dao '.$table); }
			include_once('../dao/dao_'.$table.'.php');
		}
		$dao_fk = null;
		reset($this->dao->fk);
		reset($this->dao->col);
		foreach($this->dao->fk as $field => $table)
		{
			$dao_table = 'dao_' . $table;
			$dao_fk =& new $dao_table;
			if ($dao_fk->error) 
			{
				$this->error = 'dao_fk error in '. get_class($this) .': '.$dao_fk->error->message; 
			}

			// add default 0 value to select list
			$this->dao->col[$field]['qf_vals'][0] = ' -- ';

			$filter = null;
			if (isset($dao_fk->col['hide'])) { $filter = 'hide IS NULL OR ! hide'; }
			$res = $dao_fk->selectResult('all', $filter);
			if (PEAR::isError($res)) { $this->error = $res->getMessage(); return false; }

			// assign select options
			while($row = $res->fetchrow())
			{
				$this->dao->col[$field]['qf_vals'][$row->id] = $row->{'name_'.$current_lang};
			}
		}

		$dao_fk = null;
		reset($this->dao->fk);
		reset($this->dao->col);	
	}

	/**
	 * Create the HTML_QuickForm object with all form variables in the array 'param'
	 */
	function create_form()
	{
		global $tr;

		/**
		 * Define form
		 */
		// only show these columns
		//$cols = array('id', 'name_ru', 'priority');
		//$cols = array('id', 'name_ru');
		$cols = $this->include_cols_form;


		// generate form action url: remove del/edit from it
		$query0 = '';
		if (! isset($_SERVER['QUERY_STRING'])) { $_SERVER['QUERY_STRING'] = ''; }
		parse_str($_SERVER['QUERY_STRING'], $query0);
		//unset($query0['del']);
		unset($query0['del_img']);
		//unset($query0['edit']);
		include_once('PHP/Compat/Function/http_build_query.php');
		$query = php_compat_http_build_query($query0);
		$query = str_replace('&amp;','&', $query);
		//echo htmlspecialchars($query)."<br>";
		if ($query) { $query = '?'. $query; }

		$act_edit = false;
		$act_edit = 
			isset($_GET['action']) && 
			$_GET['action'] === 'edit' && 
			isset($_GET[$this->id_field]) && 
			is_numeric($_GET[$this->id_field]);

		// if it is update form
		if ($act_edit)
		{
			// fetch row with the given id
			$view = 'all';
			if ($this->dao->col[$this->id_field]['type'] == 'integer')
			{
				$filter = $this->id_field .' = '. (int)$_GET[$this->id_field];
			} else {
				$filter = $this->id_field .' = '. $this->dao->quote(urldecode($_GET[$this->id_field]));
			}

			$res = $this->dao->selectResult($view, $filter);
			if (PEAR::isError($res)) { $this->error = $res->getMessage(); return false; }

			if (!$res->numRows()) {
				//return PEAR::throwError($tr->t('No such ID'));
				$this->error = $tr->t('No such ID') .': '.htmlspecialchars($_GET[$this->id_field]);
				$this->form =& new HTML_QuickForm('f_'.get_class($this), 'post', $query);
				return false;
			}
			$row = $res->fetchrow();
			//print_r($row);

			// fill in form values	    
			$tmp_cols = array();
			reset($cols);
			foreach($cols as $key => $col)
			{
				$tmp_cols[$col] = isset($row->$col) ? $row->$col : '';
				if ($this->dao->col[$col]['type'] == 'date' && ! $row->$col)
				{
					// set default current date
					$tmp_cols[$col] = date('Y-m-d');
				}
			}
			$cols =& $tmp_cols;

			// set selected value
			//$this->dao->col['type']['qf_setvalue'] = $row->type;
			//$this->dao->col['format']['qf_setvalue'] = $row->format;
		} else {
			$tmp_cols = array();
			reset($cols);
			foreach($cols as $key => $col)
			{
				if ($this->dao->col[$col]['type'] == 'date')
				{
					// set default current date
					$tmp_cols[$col] = date('Y-m-d');
				} else if (isset($_REQUEST[$col]) && (int) $_REQUEST[$col]) {
					$tmp_cols[$col] = (int) $_REQUEST[$col];
				} else {
					$tmp_cols[$col] = '';
				}
			}
			$cols =& $tmp_cols;
		}

		if (! $this->textarea_editor)
		{
			reset($this->include_cols_form);
			foreach($this->include_cols_form as $key => $col)
			{
				// Assign textarea a special class to make sure html editor 
				// is not loaded on dictionary content.
				//echo $col.': '.$this->dao->col[$col]['type'].'<br />';
				if ($this->dao->col[$col]['type'] == 'clob') // textarea
				{
					if (! isset($this->dao->col[$col]['qf_attrs']))
					{
						$this->dao->col[$col]['qf_attrs'] = array();
					}
					$this->dao->col[$col]['qf_attrs'] = 
						array_merge($this->dao->col[$col]['qf_attrs'], array('class' => 'noeditor'));
				}
			}
		}

		// create select elements for each foreign keys
		$this->create_fk_select();

		// create form object
		$this->form =& new HTML_QuickForm('f_'.get_class($this), 'post', $query);

		if ($act_edit)
		{
			$this->form->addElement('header', 'MyHeader', $tr->t('Edit record'));
		} else {
			$this->form->addElement('header', 'MyHeader', $tr->t('Add new record'));
		}

		// custom field definitions (overwrite in child class)
		$this->form_add_special_fields_before($row);

		$this->dao->addFormElements($this->form, $cols, 'param');

		// custom field definitions (overwrite in child class)
		$this->form_add_special_fields_after($row);

		$group = array();

		// add a "submit" button named "submit" that says "Submit"
		$group[] =& $this->form->createElement('submit', 'submit', $tr->t('Submit'));

		if ($act_edit)
		{
			// remove 'edit' if it is set
			unset($query0['action']); 
			unset($query0[$this->id_field]); 
			unset($query0['del_img']); 
			$query = php_compat_http_build_query($query0);
			$query = str_replace('&amp;','&', $query);
			
			if ($query) 
			{ 
				$query = '?'. $query; 
			} else {
				$query = $_SERVER["SCRIPT_NAME"];
			}

			$group[] =& $this->form->createElement('link', 'cancel', null, $query, $tr->t('Quit edit mode'));
		}

		$this->form->addGroup($group, null, null, '&nbsp;');

		$this->form->setRequiredNote('<span style="font-size:80%; color:#ff0000;">*</span><span style="font-size:80%;">'.$tr->t('denotes required field').'</span>');
		return true;
	} // function create_form()


	/**
	 * Custom field definitions (overwrite in child class)
	 */
	function form_add_special_fields_before(&$row)
	{
		return true;
	} 

	/**
	 * Custom field definitions (overwrite in child class)
	 */
	function form_add_special_fields_after(&$row)
	{
		return true;
	} 

	/** 
	 * Hadle photo uploads
	 */
	function store_photo($record_id)
	{
		if (! isset($this->form_file) || ! $this->form_file->isUploadedFile()) { return false; }

		global $tr;

		$upload_path = $this->upload_photo_abs_path;
		if (!is_dir($upload_path)) { @mkdir($upload_path) && @chmod($upload_path, 0755); }
		if (!is_dir($upload_path)) { $this->error = $tr->t('Cannot mkdir') .' "'. $upload_path .'".'; return false; }
		//$upload_path .= '/'. substr(get_class($this), 4);
		$upload_path .= '/'. substr(get_class($this->dao), 4);
		if (!is_dir($upload_path)) { @mkdir($upload_path) && @chmod($upload_path, 0755); }
		if (!is_dir($upload_path)) { $this->error = $tr->t('Cannot mkdir') .' "'. $upload_path .'".'; return false; }
		$upload_path .= '/'. (string)$record_id;
		if (!is_dir($upload_path)) { @mkdir($upload_path); @chmod($upload_path, 0755); }
		if (!is_dir($upload_path)) { $this->error = $tr->t('Cannot mkdir') .' "'. $upload_path .'".'; return false; }
		//echo 'upload_path: '.$upload_path.'// record_id: '.$record_id.'<br>';

		// Find an empty slot for uploaded file in the range [1-MAX_PHOTO_PER_OBJECT]
		$empty_slot = 0;
		for ($i= 1; $i <= MAX_PHOTO_PER_OBJECT; $i++)
		{
			if (! file_exists($upload_path . '/'. "${i}.".$this->img_type))
			{ 
				$empty_slot = $i; 
				break;
			}
		}
		if (! $empty_slot) 
		{ 
			$this->error = $tr->t('Maximum number of photos'). ' - '. MAX_PHOTO_PER_OBJECT; 
			return false; 
		}		
		$res = $this->form_file->moveUploadedFile($upload_path, "${empty_slot}.".$this->img_type);
		if ($res) { @chmod($upload_path .'/'. "${empty_slot}.".$this->img_type, 0644); }
		if (! $res) { $this->error = $tr->t('Cannot move uploaded file'); return $res; }
	} // end store_photo()

	/**
	 * Define Datagrid options
	 */
	function dg_options(&$dg)
	{
		$dg->setRenderer(DATAGRID_RENDER_DEFAULT); 
		//$dg->_renderer->setTableHeaderAttributes(array('bgcolor' => '#3399FF'));
		$dg->_renderer->setTableHeaderAttributes(array('bgcolor' => '#9DB1E6'));
		$dg->_renderer->setTableOddRowAttributes(array('bgcolor' => '#CCCCCC')); 
		$dg->_renderer->setTableEvenRowAttributes(array('bgcolor' => '#EEEEEE'));
		$dg->_renderer->setTableAttribute('width', '100%');

		// remove del/edit from QUERY_STRING in paging/sorting links
		$dg->_renderer->_options['excludeVars'] = array('action', $this->id_field, 'del_img');

		// make links mod_rewrite compatible
		//$dg->setDataSourceOptions('active' => false);	
		$dg->_renderer->_options['sortIconASC'] = "&#8595;";
		$dg->_renderer->_options['sortIconDESC'] = "&#8593;";	
	}

	/**
	 * Define columns for the DataGrid
	 */
	function dg_def_columns(&$dg)
	{
		global $tr;

		$fk_cols = (isset($this->dao->fk) && is_array($this->dao->fk)) ? 
			array_keys($this->dao->fk) : array();
		reset($this->dao->col);
		reset($this->include_cols_grid);
		foreach($this->include_cols_grid as $key => $field)
		{
			/**
			 * Show renamed column_name+'_fk' to avoid ambiguous name in order clause.
			 */
			$showfield = $field;
			if (in_array($field, $fk_cols))
			{
				$showfield = $field .'_fk';
			}
			if (@$this->dao->col[$field]['type'] == 'boolean')
			{
				$column =& new Structures_DataGrid_Column(
					(isset($this->dao->col[$field]['qf_label']) ? 
					$this->dao->col[$field]['qf_label'] : $tr->t($field)), 
					null, 
					$field, 
					array('align' => 'center', 'width' => '7%'), 
					null, 
					'dg_printer::printBool($field='.$field.')');
			} else {
				$column =& new Structures_DataGrid_Column(
					(isset($this->dao->col[$field]['qf_label']) ? 
					$this->dao->col[$field]['qf_label'] : $tr->t($field)), 
					$showfield, 
					$showfield
				);
			}
			$dg->addColumn($column);
			//echo $showfield .'<br>';
		}
		$this->add_special_columns($dg);
	} // function dg_def_columns(&$dg)

	/**
	 * Define special service columns.
	 */
	function add_special_columns(&$dg)
	{
		global $tr;
		
		if ($this->show_grid_priority)
		{
		$column = new Structures_DataGrid_Column($tr->t('Up'), null, null, array('align' => 'center'), null, 'dg_printer::printUp($label=&#8593)');
		$dg->addColumn($column);
		$column = new Structures_DataGrid_Column($tr->t('Down'), null, null, array('align' => 'center'), null, 'dg_printer::printDown($label=&#8595;)');
		$dg->addColumn($column);
		}

		$column = new Structures_DataGrid_Column($tr->t('Edit'), null, null, array('align' => 'center', 'width' => '7%'), null, 'dg_printer::printEdit($label='. $tr->t('Edit') .',$id_field='. $this->id_field .')');
		$dg->addColumn($column);
		$column = new Structures_DataGrid_Column($tr->t('Delete'), null, null, array('align' => 'center', 'width' => '7%'), null, 'dg_printer::printDelete($label='.$tr->t('Delete'). ',$id_field='. $this->id_field .')');
		$dg->addColumn($column);
	}

	/**
	 * Bind DataGrid to datasource
	 */
	function dg_bind(&$dg)
	{
		//$view = isset($this->dao->sql['all_fk_join']) ? 'all_fk_join' : 'all';
		$view = isset($this->dao->sql[$this->view]) ? $this->view : 'all';
		$dg->bind($this->dao, array('view' => $view), 'DBTable');
	}

	/**
	 * Create html section with all $product_id photos (overwrite in child class)
	 */
	function create_photo_section($product_id)
	{
		global $tpl, $tr;
		$content = new stdClass;
		$this->content->photos = '';
		$photo_size = THUMBNAIL_MIDDLE_SIZE;

		if (!is_dir($this->upload_photo_abs_path . '/'. substr(get_class($this), 4) .'/'.(string)$product_id))
		{ return false; }
		//{ $this->content->photos = $tr->t('No photo'); return false; }

		$d = @dir($this->upload_photo_abs_path . '/'. substr(get_class($this), 4) .'/'. (string)$product_id);
		if (! $d) { $this->content->photos = $tr->t('Cannot read directory'); return false; }
		while (false !== ($entry = $d->read())) 
		{
			if ($entry == '.' || $entry == '..') { continue; }
			$content->photos[] =  array(
				'src' => '../'. $this->upload_thumb_rel_path .'/'. 
				substr(get_class($this), 4) . '/' . $photo_size .'/'.
				(string)$product_id .'/'. $entry,
				'del_href' => '?'.$_SERVER['QUERY_STRING'] .'&del_img='.$entry,
				'width' => $photo_size,
			);
		}
		$d->close();
		
		$this->create_thumbnails($product_id, $photo_size);

		$tpl->compile('section_photos.html');
		$this->content->photos = $tpl->bufferedOutputObject($content);
	}

	/**
	 * Create thumbnails (all available)
	 */
	function create_thumbnails(&$id, $size = THUMBNAIL_SMALL_SIZE)
	{
		require('../lib/create_thumbnails.php');
	} // function create_thumbnail(&$id, $size = THUMBNAIL_SMALL_SIZE)

	/**
	 * Add something to output if needed (overwrite in child class) FIXME: depricated function
	 */
	function content_add()
	{
		return '';
	}

	/**
	 * Add something at the begining of the output if needed (overwrite in child class)
	 */
	function content_add_before()
	{
		return $this->filter_form();
	}

	/**
	 * Add something to the end of the output if needed (overwrite in child class)
	 */
	function content_add_after()
	{
		return $this->content_add();
	}


	/**
	 * Validate form post data
	 */ 
	function validate_form()
	{
		global $tr;

		//print_r($_POST['param']);
		if ($this->form->validate()) 
		{
			// this will be the base name of the photo file
			$record_id = 0; 

			if (isset($_POST['param'][$this->id_field]) && $_POST['param'][$this->id_field])
			{
				// update form
				if ($this->dao->col[$this->id_field]['type'] == 'integer')
				{
					$where = $this->id_field ." = ". (int)$_POST['param'][$this->id_field];
				} else {
					$where = $this->id_field ." = ". $this->dao->quote($_POST['param'][$this->id_field]);
				}
				//echo 'where: '.htmlspecialchars($where).'<br />';
				$res = @$this->dao->update($_POST['param'], $where);
				if (PEAR::isError($res)) 
				{ 
					$this->error = $res->getMessage(); 
				} else {
					$record_id = (int)$_POST['param'][$this->id_field];
					$this->message = $tr->t('Record updated successfully');
				}
			} else {
				// insert data from the form
				$res = @$this->dao->insert($_POST['param']);
				if (PEAR::isError($res)) 
				{ 
					$this->error = $tr->t($res->getMessage()); 
				} else {
					$record_id = $this->dao->last_insert_id;
					$this->message = $tr->t('Record added successfully');
				}
				//print_r($res);
			}

			// handle photo uploads
			if ($record_id && isset($this->form_file) && $this->form_file->isUploadedFile())
			{
				$this->store_photo($record_id);
			}

			// Special form validation procedure (overwrite in child class)
			$this->validate_form_special($record_id);
		} // if ($this->form->validate()) 
	} // function validate_form()

	/**
	 * Special form validation procedure (overwrite in child class)
	 */ 
	function validate_form_special(&$record_id)
	{
		return true;
	} // function validate_form_special()

	/**
	 * Define the set of fields to show on form and grid (overwtite in child class)
	 */
	function define_data_fields()
	{
		return true;
	}

	/**
	 * Define filter expression for result data set. (overwtite in child class)
	 */
	function define_data_filter()
	{
		return true;
	}

	/**
	 * Define filter expression for result data set based on $this->search_fields.
	 */
	function define_search_data_filter()
	{
		//$where_add = array();
		$date_type_var = '';

		foreach($this->search_fields as $search_field)
		{
			if ($this->dao->col[$search_field]['type'] == 'date')
			{	
				$date_type_var = $search_field;
				$empty_date = true;
				foreach(array('_from', '_upto') as $key => $suffix)
				{
					$val =& $_REQUEST[$search_field][$search_field . $suffix];

					if (isset($val) && $val)
					{	
						$empty_date = false;
						if (is_array($val)) 
						{
							if (isset($val['y'])) 
							{
								$val['Y'] = $val['y'];
							}
							if (isset($val['F'])) 
							{
								$val['m'] = $val['F'];
							}
							if (isset($val['M'])) 
							{
								$val['m'] = $val['M'];
							}
						}

						if (is_array($val) &&
							isset($val['Y']) &&
							isset($val['m']) &&
							isset($val['d'])) 
						{    
							// the date is in HTML_QuickForm format,
							// convert into a string
							$y = (strlen($val['Y']) < 4)
								? str_pad($val['Y'], 4, '0', STR_PAD_LEFT)
								: $val['Y'];

							$m = (strlen($val['m']) < 2)
								? '0'.$val['m'] : $val['m'];

							$d = (strlen($val['d']) < 2)
								? '0'.$val['d'] : $val['d'];

							$val = "$y-$m-$d";

							$this->where_add[] = 
								"{$this->dao->table}.{$search_field} ".
								($suffix == '_from' ? '>' : '<') .'= '.$this->dao->quote($val);
						} // if (is_array($val)
					} // if (isset($val) && $val)
				} // foreach(array('_from', '_upto') as $key => $suffix)	
			} else { // if ($this->dao->col[$key]['type'] == 'date') 
				if (isset($_REQUEST[$search_field]) && 
					$_REQUEST[$search_field])
				{
					$this->where_add[] = 
						"{$this->dao->table}.{$search_field} LIKE ".
						$this->dao->quote($_REQUEST[$search_field].'%');
					//$this->dao->quote('%'.$_REQUEST[$search_field].'%');

				} // if (isset($_REQUEST[$search_field])
			} // if ($this->dao->col[$key]['type'] == 'date') 
		} // foreach($this->search_fields as $search_field)

		if ($date_type_var && $empty_date)
		{
			// default: restrict result set to current month
			$this->where_add[] = 
				"{$this->dao->table}.{$date_type_var} ".
				' >= '.$this->dao->quote(date("Y-m-d", $this->query_default_time_start));
		}
	} // function define_search_data_filter()

	/**
	 * Create additional filter form elements.
	 */
	function filter_form_add(&$form)
	{
		return true;
	}

	/**
	 * Create filter form according to $this->search_fields
	 */
	function filter_form()
	{
		global $tpl, $current_lang, $tr;

		if (! count($this->search_fields)) { return ''; } // nothing to filter

		/**
		 * Define form
		 */
		require_once 'HTML/QuickForm.php';
		$form =& new HTML_QuickForm('filter_form', 'get', 'http://'.BASE_HREF .'adm/', null, array('class'=>'filter_form'), true);
		$form->addElement('hidden', 'q');
		$form->setDefaults(array('q' => substr(get_class($this), 4))); // our module name

		//$form->addElement('header', 'header_name', $tr->t('Filter'); 

		foreach($this->search_fields as $search_field)
		{
			if ($this->dao->col[$search_field]['type'] == 'date')
			{	
				// date default values
				$date_defaults_start = array(
					'd' => date('d', $this->query_default_time_start),
					'm' => date('m', $this->query_default_time_start),
					'Y' => date('Y', $this->query_default_time_start));

				$date_defaults_end = array(
					'd' => date('d'),        
					'm' => date('m'),
					'Y' => date('Y'));

				$group = array();


				foreach(array('from', 'upto') as $key => $suffix)
				{
					$group[] =& $form->createElement('date', $search_field .'_'. $suffix, 
						($suffix == 'from' ? 'с': 'по' ).': ', 
						array('language' => $current_lang, 'minYear' => FORM_DATE_MIN_YEAR, 'format' => FORM_DATE_FORMAT));
				}
				//$group[] =& $form->createElement('submit', 'submit', $tr->t('Select'));

				//$form->addGroup($group, $this->var_date, 'Дата: ', '&nbsp;<strong>&mdash;</strong>&nbsp;');
				//$form->addGroup($group, $search_field, $tr->t('Date') .' ', '&nbsp;');
				$form->addGroup($group, $search_field, $this->dao->col[$search_field]['qf_label'] .' ', '&nbsp;');

				$form->setDefaults(array($search_field.'['.$search_field .'_from]' => $date_defaults_start));
				$form->setDefaults(array($search_field.'['.$search_field .'_upto]' => $date_defaults_end));
			} else { 
				// text search
				$form->addElement('text', $search_field, 
					$this->dao->col[$search_field]['qf_label'].': ', null);
			} // if ($this->dao->col[$search_field]['type'] == 'date')	
		} // foreach($this->search_fields as $search_field)

		$form->addElement('submit', 'submit', $tr->t('Select'));

		// create any additional elements
		$this->filter_form_add($form);

		require_once('HTML/QuickForm/Renderer/Object.php');
		$renderer =& new HTML_QuickForm_Renderer_Object(true);
		$form->accept($renderer);
		$view = new StdClass;
		$view->form = $renderer->toObject();

		//echo '<pre>';
		//print_r($view->form);
		//echo '</pre>';

		$suffix = '_filter_form.html';
		$form_tpl = get_class($this) . $suffix; 
		if (! is_readable('./tpl/'.$form_tpl)) 
		{ 
			$form_tpl = get_parent_class($this) . $suffix; 
		}
		if (! is_readable('./tpl/'.$form_tpl)) 
		{ 
			$form_tpl = 'filter_form.html'; 
		}	
		if (! is_readable('./tpl/'.$form_tpl)) 
		{ 
			$form_tpl = 'dynamic_form.html'; 
		}
		$tpl->compile($form_tpl);
		return $tpl->bufferedOutputObject($view);
		//return $form->toHtml();
	} // function filter_form()

	/**
	 * Anything that must be done to handle special sumbit request (not search). (overwtite in child class)
	 */
	function handle_submit_request()
	{
		return true;
	}

	/**
	 * Output to browser
	 */
	function output()
	{
		global $tpl, $db, $tr, $current_lang;
		$out = '';

		/**
		 * Define set of fields to show on form and grid (overwtite in child class)
		 */
		$this->define_data_fields();
		$this->include_cols_form = array_diff($this->include_cols_form, $this->exclude_cols_form);
		$this->include_cols_grid = array_diff($this->include_cols_grid, $this->exclude_cols_grid);

		$this->define_data_filter();
		$this->define_search_data_filter();
		$this->filter = $this->dao->sql[$this->view]['where'] = 
			(count($this->where_add) ? implode(' AND ',$this->where_add) : ''); 

		$this->handle_submit_request();

		$this->filter_form();

		// Add something at the begining of the output if needed
		$out .= $this->content_add_before();

		// delete row
		$act_delete = false;
		$act_delete = 
			isset($_GET['action']) && 
			$_GET['action'] === 'delete' && 
			isset($_GET['id']) && 
			is_numeric($_GET['id']);

		if ($act_delete)
		{
			if (isset($this->dao->col['parent_id']))
			{
				$res = $this->dao->delete_recursive((int)$_GET['id']);
			} else {
				$this->dao->delete($this->id_field .' = '. (int)$_GET['id']);
			}
			// redraw parents select after delete
			$form =& $this->create_form($dao);
		}

		// shift Up priority
		$act_shift_up = false;
		$act_shift_up = 
			isset($_GET['action']) && 
			$_GET['action'] === 'up' && 
			isset($_GET['id']) && 
			is_numeric($_GET['id']);

		if ($act_shift_up)
		{
			$this->dao->shift_priority($_GET['id']);
		}

		// shift Down priority
		$act_shift_down = false;
		$act_shift_down = 
			isset($_GET['action']) && 
			$_GET['action'] === 'down' && 
			isset($_GET['id']) && 
			is_numeric($_GET['id']);

		if ($act_shift_down)
		{
			$this->dao->shift_priority($_GET['id'], 'down');
		}


		// create the HTML_QuickForm object with all form variables in the array 'param'
		$this->create_form();

		// validate form post data
		$this->validate_form();

		/**
		 * Define DataGrid
		 */
		$dg =& new Structures_DataGrid(DATAGRID_ROWS_PER_PAGE);

		// Define DataGrid Color Attributes and other options
		$this->dg_options($dg);

		// Define DataGrid columns
		$this->dg_def_columns($dg);

		// Bind DataGrid to datasource
		$this->dg_bind($dg);

		//echo $dg->renderer->getPaging();
		//$dg->render();
		$out .= $dg->getOutput();

		//$dg->dump();

		// function getPaging($mode = 'Sliding', $separator = '|', $prev = '<<', $next = '>>', $delta = 5, $attrs = null) 
		$out .= $dg->_renderer->getPaging('Sliding', '|', '&laquo;', '&raquo;');


		$out .= '<br /><br />';

		$act_edit = false;
		$act_edit = 
			isset($_GET['action']) && 
			$_GET['action'] === 'edit' && 
			isset($_GET[$this->id_field]) && 
			is_numeric($_GET[$this->id_field]);

		// if it is update form
		if ($act_edit)
		{
			// is there delete photo request?
			if (isset($_GET['del_img']) && $_GET['del_img'])
			{
				// security checks
				$del_img = str_replace('.',':', substr($_GET['del_img'], 0, strlen($_GET['del_img']) - 4));

				$upload_path = $this->upload_photo_abs_path . '/'. 
					substr(get_class($this), 4) .'/'. (string)$_GET[$this->id_field];
				if (is_dir($upload_path) && 
					file_exists($upload_path .'/'. $del_img .'.'.$this->img_type)) 
				{
					$res = @unlink($upload_path .'/'. $del_img .'.'.$this->img_type);
					if (! $res) { $this->error = $tr->t('Cannot delete photo'); }
				}
			}
			// we have to show product photos in edit mode
			$this->create_photo_section((int)$_GET[$this->id_field]);
		}

		// Add something to output if needed
		// save in buffer in case there are errors/messages to display
		$out_add = $this->content_add_after();

		if ($this->message)
		{
			$out .= '<div style="border: solid blue; padding: .5em; width: 90%;"><strong>'.$tr->t('Message').': </strong>'.
				htmlspecialchars($this->message).'</div><br />';
		}
		if ($this->error)
		{
			$out .= '<div style="border: solid red; padding: .5em; width: 90%;"><strong>'.$tr->t('Error').': </strong>'.
				htmlspecialchars($this->error).'</div><br />';
		}

		// display the form
		//$this->form_renderer =& new HTML_QuickForm_Renderer_Tableless();
		//$this->form->accept($this->form_renderer);
		//$out .= $this->form_renderer->toHtml();

		$show_form = $this->add_record_form || $act_edit;
		if ($show_form)
		{
			$out .= $this->form->toHtml();
		}

		$out .= $out_add;

		$this->content->content = $out;
		// output
		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($this->content);
	}
}
?>