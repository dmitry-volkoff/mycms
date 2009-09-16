<?php
/**
 * DAO menu
 *
 * @author vdb
 */

//require_once('DB.php');
//require_once('DB/Table.php');

class dao_menu extends dao
{
	/**
	 * Constructor (php4).
	 */     
	function dao_menu($table = '', $create = TABLE_CREATE_MODE)
	{
		$this->__construct($table, $create);
	}

	/**
	 * Constructor.
	 */
	function __construct($table = '', $create = TABLE_CREATE_MODE)
	{
		global $tr, $db, $table_prefix, $available_langs, $current_lang;

		/** 
		 * Column definitions. 
		 */
		$this->col = array(         
			// unique row ID
			'id' => array(
				'type'    => 'integer',
				'require' => true,
				'qf_type' => 'hidden',
				'qf_label' => 'ID',
			),
			// menu type
			'type' => array(
				'type'    => 'integer',
				'require' => true,
				'qf_type' => 'hidden',
				'qf_label' => 'Type',
			),	 
			// parent ID
			'parent_id' => array(
				'type'    => 'integer',
				'require' => true,
				'qf_type' => 'select',
				'qf_label' => 'Parent',
			),	 
			// Link/Url
			'link' => array(
				'type'    => 'varchar',
				'size'    => 255,
				'qf_label' => 'Link',
			),
		);

		parent::__construct($table);

		/**
		 * Create complex join statement with all fk's
		 */
		$iq = 'quoteIdentifier';

		$select = 
			$this->table .'.'. $this->db->$iq('id') .', '.
			$this->table .'.'. $this->db->$iq('name_ru') .', '.
			$this->table .'.'. $this->db->$iq('name_en') .', '.
			$this->table .'.'. $this->db->$iq('parent_id') .', '.
			$this->table .'.'. $this->db->$iq('link') .', '.
			'p.'. $this->db->$iq('name_ru') .' AS pname_ru, '.
			'p.'. $this->db->$iq('name_en') .' AS pname_en ';

		$join = 'LEFT JOIN ' .$this->table. ' AS p ON '. $this->table .'.'. 
			$this->db->$iq('parent_id') .' = p.'. $this->db->$iq('id');


		$order = 
			$this->table .'.'. $this->db->$iq('parent_id') .', '.
			$this->table .'.'. $this->db->$iq('priority') .', '.
			$this->table .'.'. $this->db->$iq('name_'.$current_lang);

/****
	$order = 
		$this->table .'.'. $this->db->$iq('link');
****/

		$this->sql['parent_join'] = array(
			'select' => $select,
			'join'   => $join,
			'order'  => $order,
		);
	} // function __construct($table = '', $create = TABLE_CREATE_MODE)


	/** 
	 * Override the parent create() 
	 *
	 * Remember to include the creation 
	 * flag parameter!
	 */
/*************
	function create($flag)
	{
	global $tr;
	// call the parent create() first
	$result = parent::create($flag);

	// was the table created?
	if (PEAR::isError($result) || ! $result) 
	{
		// table not created
		return $result;
	} else {
		// table created successfully; insert a first row...
		$cols_vals = 
		array(
		'parent_id' => 0,
		'name_ru'   => $tr->t('Home'),
		'name_en'   => 'Home',
		'link'      => '',
		'type'      => 1,
		'priority'  => 0,
		);
		$result = $this->insert($cols_vals);
		// ... and return the insert results.
		return $result;
	}
	}
**************/

	/**
	 * Handle inserts
	 *
	 * @param data
	 *     assoc data array to insert
	 * @return 
	 *     integer new ID or PEAR error object
	 */
	function insert($data)
	{
		// force a new ID on the data
		$data['id'] = $this->nextID();
		$this->last_insert_id = $data['id'];

		// set default menu type (main site menu)
		if (! isset($data['type']) || ! $data['type']) { $data['type'] = 1; }

		// set default uniq priority value
		if (isset($this->col['priority']) && ! (isset($data['priority']) && $data['priority'])) 
		{
			$data['priority'] = time();
		}

		// auto-validate and insert
		$result = parent::insert($data);
		// check the result of the insert attempt
		if (PEAR::isError($result)) {
			// return the error
			return $result;
		} else {
			// return the new ID
			return $data['id'];
		}
	} // function insert($data)


	/**
	 * Handle recursive deletes of children
	 *
	 * @param id 
	 *    menu item id to delete
	 * @return integer deleted ID or PEAR error object
	 */
	function delete_recursive($id)
	{
		//echo 'id: ';
		//echo $id;
		//echo '<br>';

		$id = (int) $id;
		if (! $id) { return false; }


		$view = 'all';
		$where = 'parent_id = ' . $this->quote($id);

		// check if there are any children
		$res = $this->selectResult('all', $where);

		// if no result, then stop recursion and just delete this item
		if (!$res->numRows()) {
			$where = 'id = ' . $this->quote($id);
			return parent::delete($where);
		}
		while($row = $res->fetchrow())
		{
			$this->delete_recursive($row->id);
		}

		// finally delete requested id
		$where = 'id = ' . $this->quote($id);
		return parent::delete($where);
	} // function delete_recursive($id)


	/**
	 * Create properly sorted menu array
	 */
	function &get_menu_array()
	{
		global $current_lang, $tr;

		$m = array();	

		$order = 'parent_id, priority';
		$res = $this->selectResult('all', null, $order);
		if (PEAR::isError($res)) { echo $res->getMessage(); }

		// collect array of hidden parent
		$parent_hide = array();

		// make an aray with special sort column
		// sort column format: parent_id + priority + id (e.g. '000 001 002')
		while ($row = $res->fetchrow())
		{
			if ($row->hide || in_array($row->parent_id, $parent_hide)) 
			{ 
				$parent_hide[] = $row->id; continue; 
			}
			
			if (! isset($m[$row->id]['sort'])) { $m[$row->id]['sort'] = ''; }
			
			$m[$row->id]['sort'] .= ($row->parent_id ? $m[$row->parent_id]['sort'] : '000000') .
				sprintf("%06s", $row->priority) . 
				sprintf("%06s", $row->id) ; 
			$m[$row->id]['name'] = $row->{'name_'.$current_lang} ? $row->{'name_'.$current_lang} : 
				($current_lang == 'en' ? $tr->tl($row->name_ru) : $row->name_en);
			//if ($m[$row->id]['name'])

			$m[$row->id]['id'] = $row->id; 
			$m[$row->id]['link'] = $row->link; 
			$m[$row->id]['parent_id'] = $row->parent_id; 
		}

		reset($m);
		$m0 = array();
		foreach($m as $id => $val)
		{
			$m0[] = $m["$id"]['sort'];
		}

		array_multisort($m0, SORT_ASC, SORT_STRING, $m);
		return $m;
	} // function &get_menu_array()
}
?>