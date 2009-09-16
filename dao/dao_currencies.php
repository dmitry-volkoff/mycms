<?php
//require_once('DB.php');
//require_once('DB/Table.php');

class dao_currencies extends DB_Table
{
	var $id_type = 0; // normal
	var $last_insert_id = 0;
	var $col = array(         
		// unique ID
		'id' => array(
			'type'    => 'integer',
			'require' => true,
			'qf_label' => 'ID',
			'qf_type' => 'hidden',
		),
		// Exchange rate
		'rate' => array(
			'type'    => 'decimal',
			'size' => 10,
			'scope' => 2,
			'qf_label' => 'Rate',
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

	/*
	 * Constructor (php4)
	 */     
	function dao_currencies($table = '', $create = TABLE_CREATE_MODE)
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
	 * @param $data data array to insert
	 * @return integer new ID or PEAR error object
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
}
?>