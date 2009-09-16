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
	 * Columns to include in the form
	 */    
	var $include_cols_form = array();

	/**
	 * Columns to include in the grid
	 */    
	var $include_cols_grid = array();

	/**
	 * Additional 'where' parameter
	 */
	var $where_add = array();

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
	var $upload_photo_rel_path;

	/**
	 * Absolute path where to upload photos
	 */
	var $upload_photo_abs_path;

	/**
	 * Image type we are using
	 */
	var $img_type;

	/**
	 * Whether to use html editor on textarea fields (true/false)
	 */
	var $textarea_editor = false;

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
	function __construct()
	{
		global $current_lang, $tr;

		$this->template = get_class($this) .'.html'; 
		if (! is_readable('./tpl/'.$this->template)) 
		{ 
			$this->template = 'blk_default.html'; 
		}

		// initialize main dao object
		$dao_name = 'dao_' . substr(get_class($this), 4);
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

		// photo upload path
		$this->upload_photo_rel_path = 'photo';
		$this->upload_photo_abs_path = realpath(dirname($_SERVER["SCRIPT_FILENAME"]).'/../').
			'/'. $this->upload_photo_rel_path;
		$this->img_type = 'jpg';

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

			$res = $dao_fk->selectResult('all');
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
		parse_str($_SERVER['QUERY_STRING'], $query0);
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
			isset($_GET['id']) && 
			is_numeric($_GET['id']);

		// if it is update form
		if ($act_edit)
		{
			// fetch row with the given id
			$view = 'all';
			if ($this->dao->col['id']['type'] == 'integer')
			{
				$filter = 'id = '. (int)$_GET['id'];
			} else {
				$filter = 'id = '. $this->dao->quote(urldecode($_GET['id']));
			}

			$res = $this->dao->selectResult($view, $filter);
			if (PEAR::isError($res)) { $this->error = $res->getMessage(); return false; }

			if (!$res->numRows()) {
				//return PEAR::throwError($tr->t('No such ID'));
				$this->error = $tr->t('No such ID') .': '.htmlspecialchars($_GET['id']);
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
			unset($query0['id']); 
			$query = php_compat_http_build_query($query0);
			$query = str_replace('&amp;','&', $query);
			if ($query) { $query = '?'. $query; }

			$group[] =& $this->form->createElement('link', 'cancel', null, $query, $tr->t('Quit edit mode'));
		}
		$this->form->addGroup($group, null, null, '&nbsp;');

		$this->form->setRequiredNote('<span style="font-size:80%; color:#ff0000;">*</span><span style="font-size:80%;">'.$tr->t('denotes required field').'</span>');
		return true;
	}

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
		if (!is_dir($upload_path)) { @mkdir($upload_path); @chmod($upload_path, 0755); }
		if (!is_dir($upload_path)) { $this->error = $tr->t('Cannot mkdir for uploaded file'); return false; }
		//$upload_path .= '/'. substr(get_class($this), 4);
		$upload_path .= '/'. substr(get_class($this->dao), 4);
		if (!is_dir($upload_path)) { @mkdir($upload_path); @chmod($upload_path, 0755); }
		if (!is_dir($upload_path)) { $this->error = $tr->t('Cannot mkdir for uploaded file'); return false; }
		$upload_path .= '/'. (string)$record_id;
		if (!is_dir($upload_path)) { @mkdir($upload_path); @chmod($upload_path, 0755); }
		if (!is_dir($upload_path)) { $this->error = $tr->t('Cannot mkdir for uploaded file'); return false; }
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
		if (!$res) { $this->error = $tr->t('Cannot move uploaded file'); return $res; }
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
		$dg->_renderer->_options['excludeVars'] = array('del', 'edit');

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
			if ($this->dao->col[$field]['type'] == 'boolean')
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
	}

	/**
	 * Define special service columns.
	 */
	function add_special_columns(&$dg)
	{
		global $tr;
		$column = new Structures_DataGrid_Column($tr->t('Edit'), null, null, array('align' => 'center', 'width' => '7%'), null, 'dg_printer::printEdit($label='.$tr->t('Edit').')');
		$dg->addColumn($column);
		$column = new Structures_DataGrid_Column($tr->t('Delete'), null, null, array('align' => 'center', 'width' => '7%'), null, 'dg_printer::printDelete($label='.$tr->t('Delete').')');
		$dg->addColumn($column);
	}

	/**
	 * Bind DataGrid to datasource
	 */
	function dg_bind(&$dg)
	{
		$view = isset($this->dao->sql['all_fk_join']) ? 'all_fk_join' : 'all';
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

		if (!is_dir($this->upload_photo_abs_path . '/'. substr(get_class($this), 4) .'/'.(string)$product_id))
		{ return false; }
		//{ $this->content->photos = $tr->t('No photo'); return false; }

		$d = @dir($this->upload_photo_abs_path . '/'. substr(get_class($this), 4) .'/'. (string)$product_id);
		if (! $d) { $this->content->photos = $tr->t('Cannot read directory'); return false; }
		while (false !== ($entry = $d->read())) 
		{
			if ($entry == '.' || $entry == '..') { continue; }
			$content->photos[] =  array(
				'src' => '../'. $this->upload_photo_rel_path .'/'. 
				substr(get_class($this), 4) . '/'.
				(string)$product_id .'/'. $entry,
				'del_href' => '?'.$_SERVER['QUERY_STRING'] .'&del_img='.$entry,
			);
		}
		$d->close();

		$tpl->compile('section_photos.html');
		$this->content->photos = $tpl->bufferedOutputObject($content);
	}

	/**
	 * Add something to output if needed (overwrite in child class)
	 */
	function content_add()
	{
		return '';
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

			if (isset($_POST['param']['id']) && $_POST['param']['id'])
			{
				// update form
				if ($this->dao->col['id']['type'] == 'integer')
				{
					$where = "id = ". (int)$_POST['param']['id'];
				} else {
					$where = "id = ". $this->dao->quote($_POST['param']['id']);
				}
				//echo 'where: '.htmlspecialchars($where).'<br />';
				$res = @$this->dao->update($_POST['param'], $where);
				if (PEAR::isError($res)) 
				{ 
					$this->error = $res->getMessage(); 
				} else {
					$record_id = (int)$_POST['param']['id'];
					$this->message = $tr->t('Record updated successfully');
				}
			} else {
				// insert data from the form
				$res = @$this->dao->insert($_POST['param']);
				if (PEAR::isError($res)) 
				{ 
					$this->error = $res->getMessage(); 
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
	 * Output to browser
	 */
	function output()
	{
		global $tpl, $db, $tr, $current_lang;
		$out = '';

		// delete row
		$act_delete = false;
		$act_delete = 
			isset($_GET['action']) && 
			$_GET['action'] === 'delete' && 
			isset($_GET['id']) && 
			is_numeric($_GET['id']);

		if ($act_delete)
		{	
			$res = $this->dao->delete('id = '. (int)$_GET['del']);
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
			isset($_GET['id']) && 
			is_numeric($_GET['id']);

		// if it is update form
		if ($act_edit)
		{
			// is there delete photo request?
			if (isset($_GET['del_img']) && $_GET['del_img'])
			{
				// security checks
				$del_img = str_replace('.',':', substr($_GET['del_img'], 0, strlen($_GET['del_img']) - 4));

				$upload_path = $this->upload_photo_abs_path . '/'. 
					substr(get_class($this), 4) .'/'. (string)$_GET['id'];
				if (is_dir($upload_path) && 
					file_exists($upload_path .'/'. $del_img .'.'.$this->img_type)) 
				{
					$res = @unlink($upload_path .'/'. $del_img .'.'.$this->img_type);
					if (! $res) { $this->error = $tr->t('Cannot delete photo'); }
				}
			}
			// we have to show product photos in edit mode
			$this->create_photo_section((int)$_GET['id']);
		}

		// Add something to output if needed
		// save in buffer in case there are errors/messages to display
		$out_add = $this->content_add();

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
		$out .= $this->form->toHtml();

		$out .= $out_add;

		$this->content->content = $out;
		// output
		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($this->content);
	}
}
?>
