<?php
/**
 * DAO main class
 *
 * @author vdb
 */

//require_once('DB.php');
//require_once('DB/Table.php');

class dao extends DB_Table
{
	var $last_insert_id = 0;
	var $id_type = 0; // normal

	var $col = array();
	var $idx = array();
	var $sql = array();
	var $fk  = array();

	/**
	 * Constructor (php4)
	 */
	function dao($table = '', $create = TABLE_CREATE_MODE) 
	{
		$this->__construct($table, $create);
	} // Constructor (php4)

	
	/**
	 * Constructor
	 */
	function __construct($table = '', $create = TABLE_CREATE_MODE) 
	{
		global $db, $tr, $table_prefix, $current_lang, $available_langs;
		if (!$table) 
		{
			// default to this class name
			$table = $table_prefix . substr(get_class($this) , 4);
		}

		// Common columns.
		if (isset($this->col['id'])) 
		{
			$this->col['id'] = array(
				'type' => 'integer',
				'require' => true,
				'qf_type' => 'hidden',
				'qf_label' => 'ID',
			);
		}

		// flag: Hide menu item
		$this->col['hide'] = array(
			'type' => 'boolean',
			'qf_label' => 'Hide',
		);

		// priority (relative position)
		$this->col['priority'] = array(
			'type' => 'integer',
			'qf_label' => 'Priority',
		);

		// Dynamic columns.
		foreach($available_langs as $key => $lang) 
		{

			// real user name
			$this->col['name_' . $lang] = array(
				'type' => 'varchar',
				'size' => 255,
				'require' => ($lang === $current_lang) ,
				'qf_label' => 'Name (' . $lang . ')',
				'qf_type' => 'text',
				'qf_attrs' => array(
					'size' => TEXTAREA_COLS,
				),
			);
		}
	
		$this->idx = array(
			'id' => 'unique',
		);

		$this->sql = array(
			// multiple rows for a list
			'all' => array(
				'select' => '*',
			),
		);
	
		DB_Table::DB_Table($db, $table, $create);
		$this->fetchmode = DB_FETCHMODE_OBJECT;

		foreach($this->col as $key => $val) 
		{
			if (isset($val['qf_label']) && $val['qf_label']) 
			{
				$this->col[$key]['qf_label'] = $tr->t($val['qf_label']);
			} else {
				$this->col[$key]['qf_label'] = $tr->t($key);
			}
		}

		// Create complex join statement with all fk's
		$select_join = $this->table . '.*';
		$join = '';
		reset($this->fk);
		foreach($this->fk as $field => $table) 
		{
			// rename column_name+'_fk' to avoid ambiguous column name in ``ORDER'' clause
			$select_join.= ',' . $table_prefix . $table . '.name_' . $current_lang . ' AS ' . $field . '_fk';
			$join.= " LEFT JOIN {$table_prefix}{$table} ON ({$this->table}.$field = {$table_prefix}{$table}.id)";
		}
		reset($this->fk);
		$this->sql['all_fk_join'] = array(
			'select' => $select_join,
			'join' => $join,
		);
	} // Constructor

	
	/**
	 * Handle inserts
	 *
	 * @param data
	 *     assoc data array to insert
	 * @return
	 *     integer new ID or PEAR error object
	 */
	function insert(&$data) 
	{
		// force a new ID on the data
		$data['id'] = $this->nextID();
		$this->last_insert_id = $data['id'];

		// default uniq priority value
		if (isset($this->col['priority']) && ! isset($data['priority'])) 
		{
			$data['priority'] = time();
		}
		
		// auto-validate and insert
		$result = parent::insert($data);

		// check the result of the insert attempt
		if (PEAR::isError($result)) 
		{
			return $result;
		} else {
			// return the new ID
			return $data['id'];
		}
	}


	/**
	 * Shift Up/Down priority of a given record
	 *
	 * @param integer ID
	 *     record ID
	 * @param string direction
	 *     'up' or 'down' tokens
	 * @return
	 *     bool result
	 */
	function shift_priority($id, $direction = 'up')
	{
		$order = $filter = $priority = $parent_id = $prio_oper = null;
		
		// get priority value of a given record
		$filter = 'id = ' . $this->quote($id);
		$res = $this->selectResult('all', $filter, $order);
		if (PEAR::isError($res)) { return false; } //echo $res->getMessage();
		if (! $res->numRows()) { return false; }

		$row = $res->fetchrow();

		if (isset($row->priority)) 
		{ 
			$priority = (int) $row->priority; 
		} else {
			 return false;
		} 

		if (isset($row->parent_id)) 
		{ 
			$parent_id = (int) $row->parent_id; 
		}

		// Find records with similar priority, candidats for exchange priority values.
		// define sort order
		if (isset($this->col['parent_id'])) 
		{
			$order = 'parent_id, priority';
		} else {
			$order = 'priority';
		}
		
		if ($direction === 'up') { $order .= ' DESC'; } 

		// narrow search results
		$arr_filter = array();
		
		if (isset($row->parent_id)) 
		{
			$arr_filter[] = 'parent_id = '. $this->quote($row->parent_id);
		}
		
		if ($direction === 'up')
		{
			$prio_oper = '<';
		} else {
			$prio_oper = '>';
		}
		
		$arr_filter[] = 'priority '. $prio_oper . $this->quote($priority);
		
		
		$filter = implode(' AND ', $arr_filter);
		
		$res = $this->selectResult('all', $filter, $order, 0, 1);
		if (PEAR::isError($res)) { return false; } //echo $res->getMessage();
		if (! $res->numRows()) { return false; }
		
		//echo $id .' '. $direction . '<br>';
		
		$row = $res->fetchrow();
		//echo $row->id .' '. $row->priority . '<br>';
		
		// update priority in 2 steps
		$data['priority'] = (int) $row->priority;
		$res = $this->update($data, 'id = '. $this->quote($id));
		//if (PEAR::isError($res)) { return false; } //echo $res->getMessage();
		
		$data['priority'] = (int) $priority;
		$res = $this->update($data, 'id = '. $this->quote($row->id));
		//if (PEAR::isError($res)) { return false; } //echo $res->getMessage();
	}
} // class dao extends DB_Table
?>