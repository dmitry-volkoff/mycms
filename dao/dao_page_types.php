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
	//function insert($data)
	//{
	//}
}
?>