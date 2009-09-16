<?php
//require_once('DB.php');
//require_once('DB/Table.php');

class dao_sessions extends DB_Table
{
	var $last_insert_id = 0;
	var $id_type = 0; // normal

	var $col = array(         
		// unique ID
		'id' => array(
			'type' => 'varchar',
			'size' => 32,
			'require' => true,
			'qf_label' => 'ID',
			'qf_type' => 'hidden',
			'qf_attrs'  => array(
				'size' => 32,
			),
		),
		// expiry time
		'expiry' => array(
			'type'    => 'integer',
			'require' => false,
			'qf_label' => 'Expiry',
		),
		// session data
		'sdata' => array(
			'type'    => 'clob',
			'require' => true,
			'qf_label' => 'Data',
			'qf_type'  => 'text',
			'qf_attrs'  => array(
				'size' => TEXTAREA_COLS,
			),
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
	function dao_sessions($table = '', $create = TABLE_CREATE_MODE)
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
}
?>