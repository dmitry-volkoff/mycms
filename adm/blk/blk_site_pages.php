<?php
/*
 * Manage site pages
 *
 * @package admin
 * @author vdb
 * @version CVS: $Id$
 */

require_once('../dao/dao_pages.php');
require_once('Structures/DataGrid.php');
require_once('../lib/dg_printer.php');
require_once 'HTML/QuickForm.php';

class blk_site_pages extends block
{
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

		// assign selected options		
		if (isset($_REQUEST[$field]) && (int) $_REQUEST[$field]) 
		{
			$this->dao->col[$field]['qf_setvalue'] = (int) $_REQUEST[$field];
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
	global $tr, $current_lang;

	/**
	 * Define form
	 */
	// only show these columns
	//$cols = array('id', 'type', 'format','title_ru', 'title_en', 'content_ru', 'content_en', 'link');
	//$cols = array('id', 'type', 'format','title_ru', 'content_ru', 'link');
	$cols = array(
		'id', 
		'type',
		'date_enter',
		'title_' 	. $current_lang, 
		'content_' 	. $current_lang, 
		'description_' 	. $current_lang,
		'keywords_' 	. $current_lang,
		'menu_liaison', 
		'link');

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
	    $view = 'all_fk_join';
	    $filter = $this->dao->table .'.id = '. (int)$_GET['id'];
	    
	    $res = $this->dao->selectResult($view, $filter);
	    	
	    if (!$res->numRows()) {
	        return PEAR::throwError($tr->t('No such ID'));
	    }
	    $row = $res->fetchrow();
	    //print_r($row);
	
	    // fill in form values	    
	    $tmp_cols = array();
	    foreach($cols as $key => $col)
	    {
		$tmp_cols[$col] = $row->$col;
	    }
	    $cols =& $tmp_cols;

	    // set selected value
	    $this->dao->col['type']['qf_setvalue'] = $row->type;
	    $this->dao->col['format']['qf_setvalue'] = $row->format;
	    $this->dao->col['menu_liaison']['qf_setvalue'] = $row->menu_liaison;

	    // Assign textarea a special class to make sure html editor 
	    // is not loaded when content is php code.
	    if (strpos($row->{'content_'.$current_lang},'<?') === 0) // php page
	    {
		$this->dao->col['content_'.$current_lang]['qf_attrs'] = 
		    array_merge($this->dao->col['content_'.$current_lang]['qf_attrs'], array('class' => 'noeditor'));
	    }
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
	
	/*
	// create page type select
	include_once('../dao/dao_page_types.php');
	$page_types =& new dao_page_types;
	$this->dao->col['type']['qf_vals'][0] = ' -- ';
	$m = $page_types->get_menu_array();
	//echo '<pre>';
	//print_r($m);
	//echo '</pre>';
	// now assign select options
	foreach($m as $id => $val)
	{
	    $this->dao->col['type']['qf_vals'][$val['id']] = 
	         str_repeat('---', strlen($m[$id]['sort'])/12 - 1) . $val['name'];
	}
	*/

	// create select elements for each foreign keys
	$this->create_fk_select();


	// create menu_liaison select
	include_once('../dao/dao_menu.php');
	$menu =& new dao_menu;
	$this->dao->col['menu_liaison']['qf_vals'][0] = ' -- ';
	$m = $menu->get_menu_array();
	//echo '<pre>';
	//print_r($m);
	//echo '</pre>';
	// now assign select options
	foreach($m as $id => $val)
	{
	    $this->dao->col['menu_liaison']['qf_vals'][$val['id']] = 
	         str_repeat('---', strlen($m[$id]['sort'])/12 - 1) . $val['name'];
	}

	// generate form action url: remove del/edit from it
	$query0 = '';
	if (! isset($_SERVER['QUERY_STRING'])) { $_SERVER['QUERY_STRING'] = ''; }
	parse_str($_SERVER['QUERY_STRING'], $query0);
	unset($query0['del']);
	//unset($query0['edit']);
	include_once('PHP/Compat/Function/http_build_query.php');
	$query = php_compat_http_build_query($query0);
	$query = str_replace('&amp;','&', $query);
	//echo htmlspecialchars($query)."<br>";
	if ($query) { $query = '?'. $query; }
	
	// create form object
	//require_once 'HTML/QuickForm.php';
	
	$form =& new HTML_QuickForm('f_'.get_class($this), 'post', $query);

	if ($act_edit)
	{
	    $form->addElement('header', 'MyHeader', $tr->t('Edit record'));
	} else {
	    $form->addElement('header', 'MyHeader', $tr->t('Add new record'));
	}
	
	//$form =& $dao->getForm($cols, 'param', array(), null, null, $header); // note the "=&" -- very important
	$this->dao->addFormElements($form, $cols, 'param');

	$group = array();
	
	// add a "submit" button named "submit" that says "Submit"
	$group[] =& $form->createElement('submit', 'submit', $tr->t('Submit'));
	//$form->addElement('submit', 'submit', $tr->t('Submit'));
	
	if ($act_edit)
	{
	    // remove 'edit' if it is set
	    //unset($query0['edit']); 
	    unset($query0['action']); 
	    unset($query0['id']); 
	    $query = php_compat_http_build_query($query0);
	    $query = str_replace('&amp;','&', $query);
	    if ($query) { $query = '?'. $query; }
	    
	    $group[] =& $form->createElement('link', 'cancel', null, $query, $tr->t('Quit edit mode'));
	}
	$form->addGroup($group, null, null, '&nbsp;');
	
	$form->setRequiredNote('<span style="font-size:80%; color:#ff0000;">*</span><span style="font-size:80%;">'.$tr->t('denotes required field').'</span>');
	return $form;
    }

    /**
     * Output to browser
     */
    function output()
    {
	global $tpl, $db, $tr, $current_lang;
	$out = '';
	if (! $this->template) { $this->template = get_class($this) .'.html'; }

	$this->dao =& new dao_pages();
	//$dao =& new pages('pages', 'drop');
	//$dao->fetchmode = DB_FETCHMODE_OBJECT;	

	$page = new stdClass;
	if ($this->dao->error) 
	{
	    // error handling code goes here; for example ...
	    //print_r($dao->error);
	    return 'dao error in '. get_class($this) .': '.$this->dao->error->message; 
	}

	// delete row
	if (isset($_GET['del']) && is_numeric($_GET['del']))
	{
	    $res = $this->dao->delete($this->dao->table.'.id = '. (int)$_GET['del']);
	}
	
	// create the HTML_QuickForm object with all form variables in the array 'param'
	$form =& $this->create_form();
	
	if ($form->validate()) 
	{
	    //print_r($_POST['param']);
	    if (isset($_POST['param']['id']) && is_numeric($_POST['param']['id']))
	    {
		// update form
		$where = $this->dao->table .".id = ". (int)$_POST['param']['id'];
		$res = @$this->dao->update($_POST['param'], $where);
		if (PEAR::isError($res)) { $this->error = $res->getMessage(); }
		//if (PEAR::isError($res)) { echo $res->getMessage(); }
	    } else {
	        // insert data from the form
		$res = @$this->dao->insert($_POST['param']);
		if (PEAR::isError($res)) { $this->error = $res->getMessage(); }
		//if (PEAR::isError($res)) { echo $res->getMessage(); }
		//print_r($res);
	    }
	}
	
	/**
	 * Define DataGrid
	 */
	$dg =& new Structures_DataGrid(DATAGRID_ROWS_PER_PAGE);
	
	// Define DataGrid Color Attributes
	$dg->setRenderer(DATAGRID_RENDER_DEFAULT); 
	$dg->_renderer->setTableHeaderAttributes(array('bgcolor' => '#9DB1E6'));
	$dg->_renderer->setTableOddRowAttributes(array('bgcolor' => '#CCCCCC')); 
	$dg->_renderer->setTableEvenRowAttributes(array('bgcolor' => '#EEEEEE'));
	$dg->_renderer->setTableAttribute('width', '100%');

	// remove del/edit from QUERY_STRING in paging/sorting links
	//$dg->_renderer->_options['excludeVars'] = array('del', 'edit');
	$dg->_renderer->_options['excludeVars'] = array('action', 'id');
	
	// make links mod_rewrite compatible
	//$dg->setDataSourceOptions('active' => false);

	$dg->_renderer->_options['sortIconASC'] = "&#8595;";
	$dg->_renderer->_options['sortIconDESC'] = "&#8593;";

	// Define columns for the DataGrid
	$column = new Structures_DataGrid_Column($tr->t('ID'), 'id', 'id', null);
	$dg->addColumn($column);
	$column = new Structures_DataGrid_Column($tr->t('Title ('.$current_lang.')'), 'title_'.$current_lang, 'title_'.$current_lang);
	$dg->addColumn($column);
	//$column = new Structures_DataGrid_Column($tr->t('Title (en)'), 'title_en', 'title_en');
	//$dg->addColumn($column);
	$column = new Structures_DataGrid_Column($tr->t('Type'), 'type_fk', 'type_fk');
	$dg->addColumn($column);
	//$column = new Structures_DataGrid_Column($tr->t('Format'), 'format', 'type');
	//$dg->addColumn($column);
	$column = new Structures_DataGrid_Column($tr->t('Link'), 'link', 'link');
	$dg->addColumn($column);
	$column = new Structures_DataGrid_Column($tr->t('Edit'), null, null, array('align' => 'center'), null, 'dg_printer::printEdit($label='.$tr->t('Edit').')');
	$dg->addColumn($column);
	$column = new Structures_DataGrid_Column($tr->t('Delete'), null, null, array('align' => 'center'), null, 'dg_printer::printDelete($label='.$tr->t('Delete').')');
	$dg->addColumn($column);


	$where = null;
	if (isset($_GET['type']) && (int) $_GET['type'])
	{
		$where = $this->dao->table .'.type ='. (int) $_GET['type'];
	}
	$dg->bind($this->dao, array('view' => 'all_fk_join', 'where' => $where), 'DBTable');

	//$dg->bind($dao, array('view' => 'all_fk_join'), 'DBTable');
	//$dg->bind($db);

	//echo $dg->renderer->getPaging();
	//$dg->render();
	$out .= $dg->getOutput();

	//$dg->dump();

	// function getPaging($mode = 'Sliding', $separator = '|', $prev = '<<', $next = '>>', $delta = 5, $attrs = null) 
	$out .= $dg->_renderer->getPaging('Sliding', '|', '&laquo;', '&raquo;');


	$out .= '<br /><br />';
	
	if ($this->error)
	{
	    $out .= '<div style="border: solid red; padding: .5em; width: 90%;"><strong>'.$tr->t('Error').': </strong>'.
		htmlspecialchars($tr->t($this->error)).'</div><br />';
	}

	// display the form
	$out .= $form->toHtml();

	$page->content = $out;
	// output
	$tpl->compile($this->template);
	return $tpl->bufferedOutputObject($page);
    }
}
?>