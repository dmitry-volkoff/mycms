<?php
/*
 * examples management class
 *
 * @package admin
 * @author vdb
 * @version CVS: $Id$
 */

require_once('./lib/crud_form.php');
class blk_examples extends crud_form 
{
	/**
	 * Constructor (php4)
	 */
	function blk_examples()
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
		$this->include_cols_form = 
		array(
		'id',
		'date_enter',
		'name_'. $current_lang,
		'description_'. $current_lang,
		'price',
		'hot_offer',
		'hide',
		);
		//$this->include_cols_grid = $this->include_cols_form;
		$this->include_cols_grid =
		array(
		'id',
		'name_'.$current_lang,
		'price',
		'hot_offer',
		'hide',
		);
		
		//$this->textarea_editor = true;
	} // end constructor

	/**
	 * Bind DataGrid to datasource
	 */
	function dg_bind(&$dg)
	{
		$where = null;
		if (isset($_GET['type']) && (int) $_GET['type'])
		{
			$where = $this->dao->table .'.type ='. (int) $_GET['type'];
		}
		$dg->bind($this->dao, array('view' => 'all_fk_join', 'where' => $where), 'DBTable');
	}

	/**
	 * Custom field definitions
	 */
	function form_add_special_fields_before(&$row)
	{
/*
		global $current_lang, $tr;
		$main = array();
		$secondary = array();
		
		$main[0] = ' -- ';

		if (isset($row->brand) && isset($row->model))
		{
			$this->form->setDefaults(array('param[brand_model]' => array(
				$row->brand,
				$row->model,
			)));
		}

		foreach(array('dao_brands', 'dao_models') as $name)
		{
			require_once('./dao/'.$name.'.php');
	
			${$name} =& new $name;
			if (${$name}->error) 
			{
				die('dao error in '. get_class($this) .'('.$name.'): '.${$name}->error->message);
			}
		}
	
		//$res = $dao_models->selectResult('brands_distinct');
		$res = $dao_brands->selectResult('all');
		if (PEAR::isError($res)) { $this->error = $res->getMessage(); return false; }
	
		// assign select options
		while($row = $res->fetchrow())
		{
			$main[$row->id] = $row->{'name_'.$current_lang};
		}

		$res = $dao_models->selectResult('all');
		if (PEAR::isError($res)) { $this->error = $res->getMessage(); return false; }

		// assign select options
		while($row = $res->fetchrow())
		{
			$secondary[$row->brand][$row->id] = $row->{'name_'.$current_lang};
		}
	
		//$sel =& $this->form->addElement('hierselect', 'param[brand_model]', $tr->t('Model').':');
		//$sel->setMainOptions($main);
		//$sel->setSecOptions($secondary);    
*/
		return true;
	} // function form_add_special_fields_before(&$row)

	/**
	 * Custom field definitions
	 */
	function form_add_special_fields_after(&$row)
	{
		global $current_lang, $tr, $com_dir, $com_adm_dir;

		/**
		 *  Create image upload element
		 */
		$this->form_file =& $this->form->addElement('file', 'photo', $tr->t('Image').':');
		//$this->form->addRule('photo', $tr->t('Must be a jpeg'), 'mimetype', array('image/jpeg','image/pjpeg'));
		$this->form->addRule('photo', $tr->t('Must be a '. $this->img_type), 'mimetype', array('image/jpeg','image/pjpeg'));
	}

	/**
	 * Define special service columns.
	 */
	/***********************
	function add_special_columns(&$dg)
	{
	   	global $tr;
		//$column = new Structures_DataGrid_Column($tr->t('Properties'), null, null, array('align' => 'center', 'width' => '7%'), null, 'dg_printer::printLink($label='.$tr->t('Properties').')');
		$column = new Structures_DataGrid_Column(
			$tr->t('Properties'), null, null, 
			array('align' => 'center', 'width' => '7%'), null, 
			'dg_printer::printLink($label='.$tr->t('Properties').',$q=product_properties'.')');
		$dg->addColumn($column);
		$column = new Structures_DataGrid_Column($tr->t('Edit'), null, null, array('align' => 'center', 'width' => '7%'), null, 'dg_printer::printLink($label='.$tr->t('Edit').')');
		$dg->addColumn($column);
		$column = new Structures_DataGrid_Column($tr->t('Delete'), null, null, array('align' => 'center', 'width' => '7%'), null, 'dg_printer::printDelLink($label='.$tr->t('Delete').')');
		$dg->addColumn($column);
	}
	************************/
}
?>