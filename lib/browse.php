<?php
/**
 * Browse base class - parent of all data browse interfaces.
 *
 * @package shop
 * @author vdb
 * @version CVS: $Id$
 */

require_once('Structures/DataGrid.php');
require_once('./lib/dg_printer.php');

class browse extends block
{
	/**
	 * Columns to include in the grid
	 */    
	var $include_cols_grid = array();

	/**
	 * Additional 'where' parameter
	 */
	var $where_add = array();

	/**
	 * Relative path where to upload photos
	 */
	var $upload_photo_rel_path;

	/**
	 * Absolute path where to upload photos
	 */
	var $upload_photo_abs_path;


	/**
	 * Constructor (php4)
	 */
	function browse()
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
			$this->template = 'default.html'; 
		}

		// initialize main dao object
		$dao_name = 'dao_'. substr(get_class($this), 4);
		require_once('./dao/'.$dao_name.'.php');

		$this->dao =& new $dao_name;
		//$dao =& new $dao_name($dao_name, 'alter');

		if ($this->dao->error) 
		{
			die('dao error in '. get_class($this) .': '.$this->dao->error->message);
		}

		// photo upload path
		$this->upload_photo_rel_path = 'photo';
		$this->upload_photo_abs_path = realpath(dirname($_SERVER["SCRIPT_FILENAME"]).'/../').
			'/'. $this->upload_photo_rel_path;

	} // end constructor

	/**
	 * Define Datagrid options
	 */
	function dg_options(&$dg)
	{
		$dg->setRenderer(DATAGRID_RENDER_DEFAULT); 
		$dg->_renderer->setTableHeaderAttributes(array('bgcolor' => '#FFCC66'));
		//$dg->_renderer->setTableOddRowAttributes(array('bgcolor' => '#CCCCCC'));
		$dg->_renderer->setTableOddRowAttributes(array('bgcolor' => '#EEEEEE'));
		$dg->_renderer->setTableEvenRowAttributes(array('bgcolor' => '#EEEEEE'));
		$dg->_renderer->setTableAttribute('width', '98%');
		$dg->_renderer->setTableAttribute('cellpadding', '2px');

		// remove del/edit from QUERY_STRING in paging/sorting links
		$dg->_renderer->_options['excludeVars'] = array('del', 'edit', 'badd', 'brem');

		// make links mod_rewrite compatible
		//$dg->setDataSourceOptions('active' => false);

		$dg->renderer->_options['sortIconASC'] = "&#8595;";
		$dg->renderer->_options['sortIconDESC'] = "&#8593;";
	}

	/**
	 * Define columns for the DataGrid
	 */
	function dg_def_columns(&$dg)
	{
		global $tr;

		$fk_cols = is_array($this->dao->fk) ? array_keys($this->dao->fk) : array();
		reset($this->dao->col);
		reset($this->include_cols_grid);
		//foreach($this->dao->col as $field => $options)
		foreach($this->include_cols_grid as $key => $field)
		{
			// show renamed column_name+'_fk' to avoid ambiguous name in order clause
			$showfield = $field;
			if (in_array($field, $fk_cols))
			{
				$showfield = $field .'_fk';
			}
			if ($this->dao->col[$field]['type'] == 'boolean')
			{
				$column =& new Structures_DataGrid_Column(
					(isset($this->dao->col[$field]['qf_label']) ? 
					$this->dao->col[$field]['qf_label'] : $tr->get($field)), 
					null, 
					$field, 
					array('align' => 'center', 'width' => '7%'), 
					null, 
					'dg_printer::printBool($field='.$field.')');
			} else {
				$column =& new Structures_DataGrid_Column(
					(isset($this->dao->col[$field]['qf_label']) ? 
					$this->dao->col[$field]['qf_label'] : $tr->get($field)), 
					$showfield, 
					$showfield
				);
			}
			$dg->addColumn($column);
			//echo $showfield .'<br>';
		}
	} // end dg_def_columns(&$dg)

	/**
	 * Bind DataGrid to datasource
	 */
	function dg_bind(&$dg)
	{
		$dg->bind($this->dao, array('view' => 'all_fk_join'), 'DBTable');
	}

	/**
	 * Add something at the begining of the output if needed (overwrite in child class)
	 */
	function content_add_before()
	{
		return '';
	}

	/**
	 * Add something to the end of the output if needed (overwrite in child class)
	 */
	function content_add_after()
	{
		return '';
	}

	/**
	 * Output to browser
	 */
	function output()
	{
		global $tpl, $db, $tr, $current_lang;
		$out = '';

		// Add something at the begining of the output if needed
		$out .= $this->content_add_before();

		// delete row
		if (isset($_GET['del']) && is_numeric($_GET['del']))
		{
			$res = $this->dao->delete('id = '. (int)$_GET['del']);
		}

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

		$out .= $dg->_renderer->getPaging();

		$out .= '<br /><br />';

		if ($this->message)
		{
			$out .= '<div style="border: solid blue; padding: .5em; width: 90%;"><strong>'.$tr->get('Message').': </strong>'.
				htmlspecialchars($this->message).'</div><br />';
		}
		if ($this->error)
		{
			$out .= '<div style="border: solid red; padding: .5em; width: 90%;"><strong>'.$tr->get('Error').': </strong>'.
				htmlspecialchars($this->error).'</div><br />';
		}

		// Add something to output if needed
		$out .= $this->content_add_after();

		$this->content->content = $out;
		// output
		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($this->content);
	}
}
?>