<?php
/*
 * Manage site menu
 *
 * @package admin
 * @author vdb
 * @version CVS: $Id$
 */

require_once('Structures/DataGrid.php');
require_once('../lib/dg_printer.php');
require_once 'HTML/QuickForm.php';

class blk_menu extends block
{
	/**
	 * Create the HTML_QuickForm object with all form variables in the array 'param'
	 */
	function create_form(&$menu)
	{
		global $tr;

		/**
		 * Define form
		 */
		// only show these fields
		//$cols = array('id', 'type', 'name_ru', 'name_en', 'parent_id', 'link', 'priority');
		$cols = array('id', 'type', 'name_ru', 'parent_id', 'link', 'link_par', 'hide');

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
			$filter = $menu->table .'.'. $menu->db->quoteIdentifier('id'). ' = '. (int)$_GET['id'];

			$res = $menu->selectResult($view, $filter);
			//$res = $menu->select($view, $filter);

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

			// create select menu with parent_id's
			$this->create_parent_select($menu);

			// set selected value
			$menu->col['parent_id']['qf_setvalue'] = $row->parent_id;
		}

		// generate form action url: remove del/edit from it
		$query0 = '';
		parse_str($_SERVER['QUERY_STRING'], $query0);
		//unset($query0['action']);
		//unset($query0['edit']);
		include_once('PHP/Compat/Function/http_build_query.php');
		$query = php_compat_http_build_query($query0);
		$query = str_replace('&amp;','&', $query);
		//echo htmlspecialchars($query)."<br>";
		if ($query) { $query = '?'. $query; }

		// create form object
		//require_once 'HTML/QuickForm.php';
		$form =& new HTML_QuickForm('f_menu', 'post', $query);

		if ($act_edit)
		{
			$form->addElement('header', 'MyHeader', $tr->t('Edit record'));
		} else {
			$form->addElement('header', 'MyHeader', $tr->t('Add new record'));

			// create select menu with parent_id's
			$this->create_parent_select($menu);
		}

		//$form =& $menu->getForm($cols, 'param', array(), null, null, $header); // note the "=&" -- very important
		$menu->addFormElements($form, $cols, 'param');

		$group = array();

		// add a "submit" button named "submit" that says "Submit"
		$group[] =& $form->createElement('submit', 'submit', $tr->t('Submit'));
		//$form->addElement('submit', 'submit', $tr->t('Submit'));

		if ($act_edit)
		{
			// remove 'edit' if it is set
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
	 * Create select menu with parent_id's
	 */
	function create_parent_select(&$menu)
	{
		// This is root item
		$menu->col['parent_id']['qf_vals'][0] = '/';

		// get a sorted array with menu items
		$m = $menu->get_sorted_tree_array();

		// now assign select options
		foreach($m as $id => $val)
		{
			$menu->col['parent_id']['qf_vals'][$val['id']] = 
				str_repeat('---', strlen($m[$id]['sort'])/12 - 1) . $val['name'];
		}
	}

	/**
	 * Output menu to browser
	 */
	function output()
	{
		global $tpl, $db, $tr, $current_lang;
		$out = '';
		if (! $this->template) { $this->template = get_class($this) .'.html'; }

		if (! is_readable('./tpl/'.$this->template)) 
		{ 
			$this->template = 'blk_default.html'; 
		}

		$dao_name = 'dao_' . substr(get_class($this), 4);
		require_once("./dao/dao_menu.php");

		if (substr(get_class($this), 4) !== 'menu')
		{
			eval('class ' . $dao_name .' extends dao_menu {}; ');
		}

		$dao =& new $dao_name;
		//$dao =& new menu('menu', 'drop');
		//$dao->fetchmode = DB_FETCHMODE_OBJECT;	

		$page = new stdClass;
		if ($dao->error) 
		{
			// error handling code goes here; for example ...
			//print_r($dao->error);
			return 'dao error in '. get_class($this) .': '.$dao->error->message; 
		}

		// delete row
		$act_delete = false;
		$act_delete = 
			isset($_GET['action']) && 
			$_GET['action'] === 'delete' && 
			isset($_GET['id']) && 
			is_numeric($_GET['id']);

		if ($act_delete)
		{
			$res = $dao->delete_recursive((int)$_GET['id']);

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
			$dao->shift_priority($_GET['id']);
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
			$dao->shift_priority($_GET['id'], 'down');
		}

		// create the HTML_QuickForm object with all form variables in the array 'param'
		$form =& $this->create_form($dao);

		if ($form->validate()) 
		{
			//$form->freeze();
			// update form
			//print_r($_POST['param']);
			if (isset($_POST['param']['id']) && is_numeric($_POST['param']['id']))
			{
				$where = $dao->table .'.'. $dao->db->quoteIdentifier('id').' = '. (int)$_POST['param']['id'];
				$res = @$dao->update($_POST['param'], $where);
				if (PEAR::isError($res)) { $this->error = $res->getMessage(); }
			} else {
				// insert data from the form
				$res = @$dao->insert($_POST['param']);
				if (PEAR::isError($res)) { $this->error = $res->getMessage(); }
				//if (PEAR::isError($result)) { echo $res->getMessage(); }

				// redraw parents select after insert
				$form =& $this->create_form($dao);

				//print_r($result);
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
		$dg->_renderer->_options['excludeVars'] = array('action', 'id');

		// make links mod_rewrite compatible
		//$dg->setDataSourceOptions('active' => false);

		$dg->_renderer->_options['sortIconASC'] = "&#8595;";
		$dg->_renderer->_options['sortIconDESC'] = "&#8593;";

		// Define columns for the DataGrid
		$column = new Structures_DataGrid_Column($tr->t('ID'), 'id', 'id', null);
		$dg->addColumn($column);
		//$column = new Structures_DataGrid_Column($tr->t('Name (ru)'), 'name_ru', 'name_ru');
		$column = new Structures_DataGrid_Column($tr->t('Name ('.$current_lang.')'), 'name_'.$current_lang, 'name_'.$current_lang);
		$dg->addColumn($column);

		//$column = new Structures_DataGrid_Column($tr->t('Name (en)'), 'name_en', 'name_en');
		//$dg->addColumn($column);
		$column = new Structures_DataGrid_Column($tr->t('Parent'), 'pname_'.$current_lang, 'parent_id');
		$dg->addColumn($column);
		$column = new Structures_DataGrid_Column($tr->t('Link'), 'link', 'link');
		$dg->addColumn($column);

		$column =& new Structures_DataGrid_Column($tr->t('Hide'), 'hide', 'hide',
					array('align' => 'center', 'width' => '7%'), 
					null, 
					'dg_printer::printBool($field=hide)');
		$dg->addColumn($column);

		$column = new Structures_DataGrid_Column($tr->t('Up'), null, null, array('align' => 'center'), null, 'dg_printer::printUp($label=&#8593)');
		$dg->addColumn($column);
		$column = new Structures_DataGrid_Column($tr->t('Down'), null, null, array('align' => 'center'), null, 'dg_printer::printDown($label=&#8595;)');
		$dg->addColumn($column);

		$column = new Structures_DataGrid_Column($tr->t('Edit'), null, null, array('align' => 'center'), null, 'dg_printer::printEdit($label='.$tr->t('Edit').')');
		$dg->addColumn($column);
		$column = new Structures_DataGrid_Column($tr->t('Delete'), null, null, array('align' => 'center'), null, 'dg_printer::printDelete($label='.$tr->t('Delete').')');
		$dg->addColumn($column);

		$res = $dg->bind($dao, array('view' => 'parent_join'), 'DBTable');
		if (PEAR::isError($res)) { $this->error = $res->getMessage(); }
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