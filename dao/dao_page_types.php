<?php
//require_once('DB.php');
//require_once('DB/Table.php');

class dao_page_types extends DB_Table
{
	var $last_insert_id = 0;
	var $id_type = 0; // normal

	var $col = array(         
		// unique ID
		'id' => array(
			'type'    => 'integer',
			'require' => true,
			'qf_type' => 'hidden',
			'qf_label' => 'ID',
		),
		// flag: Hide this item 
		'hide' => array(
			'type'    => 'boolean',
			'qf_label' => 'Hide',
		),	 
		// priority (relative position)
		'priority' => array(
			'type'    => 'integer',
			'qf_label' => 'Priority',
		),
	);

	var $idx = array(
		'id' => 'unique',
	);

	var $sql = array(
		// multiple rows for a list
		'all' => array(
			'select' => '*'
		),
	);

	/**
	 * Constructor (php4)
	 */     
	function dao_page_types($table = '', $create = TABLE_CREATE_MODE)
	{
		$this->__construct($table, $create);
	}

	/**
	 * Constructor
	 */
	function __construct($table = '', $create = TABLE_CREATE_MODE)
	{
		global $db, $tr, $table_prefix, $current_lang, $available_langs;

		if (!$table)
		{
			// default to this class name
			$table = $table_prefix . substr(get_class($this), 4);
		}

		/**
		 * Dynamic columns.
		 */
		foreach($available_langs as $key => $lang)
		{
			// name
			$this->col['name_' . $lang] = array(
				'type'    => 'varchar',
				'size'    => 255,
				'require' => ($lang === $current_lang),
				'qf_label' => 'Name ('. $lang .')',
			);
		}

		foreach($this->col as $key => $val)
		{
			if (isset($val['qf_label']) && $val['qf_label'])
			{
				$this->col[$key]['qf_label'] = $tr->t($val['qf_label']);
			} else {
				$this->col[$key]['qf_label'] = $tr->t($key);
			}
		}

		DB_Table::DB_Table($db, $table, $create);
		$this->fetchmode = DB_FETCHMODE_OBJECT;
	} // Constructor

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
	}

	/**
	 * Create properly sorted array of items
	 */
	function &get_menu_array()
	{
		global $current_lang, $tr;

		$m = array();	

		//$order = 'parent_id, priority';
		$order = 'priority';
		$res = $this->selectResult('all', null, $order);
		if (PEAR::isError($res)) { echo $res->getMessage(); }

		// make an aray with special sort column
		// sort column format: parent_id + priority + id (e.g. '000 001 002')
		while ($row = $res->fetchrow())
		{
			if ($row->hide) { continue; }
			if (! isset($m[$row->id]['sort'])) { $m[$row->id]['sort'] = ''; }
			$m[$row->id]['sort'] .= (isset($row->parent_id) ? $m[$row->parent_id]['sort'] : '000000');
			$m[$row->id]['sort'] .= '000000' .
				sprintf("%06s", $row->priority) . 
				sprintf("%06s", $row->id) ; 
			$m[$row->id]['name'] = 
				$row->{'name_'.$current_lang} ? $row->{'name_'.$current_lang} : 
				($current_lang == 'en' ? $tr->tl($row->name_ru) : $row->name_en);
			//if ($m[$row->id]['name'])

			$m[$row->id]['id'] = $row->id; 
			//$m[$row->id]['link'] = $row->link; 
			//$m[$row->id]['parent_id'] = $row->parent_id; 
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