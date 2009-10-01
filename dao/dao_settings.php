<?php
class dao_settings extends DB_Table
{
	var $last_insert_id = 0;
	var $id_type = 0; // normal
	var $col = array(         
		// unique ID
		'id' => array(
			'type'    => 'integer',
			'require' => true,
			'qf_label' => 'ID',
			'qf_type' => 'hidden',
		),
		// Site domain
		'fqdn' => array(
			'type'    => 'varchar',
			'size'    => 255,
			'require' => true,
			'qf_label' => 'Domain',
			'qf_type'  => 'text',
			'qf_attrs'  => array(
				'size' => TEXTAREA_COLS,
			),
		),
		// Site name
		'name' => array(
			'type'    => 'varchar',
			'size'    => 255,
			'require' => true,
			'qf_label' => 'Site Name',
			'qf_type'  => 'text',
			'qf_attrs'  => array(
				'size' => TEXTAREA_COLS,
			),
		),
		// Company name
		'cname' => array(
			'type'    => 'varchar',
			'size'    => 255,
			'require' => false,
			'qf_label' => 'Company Name',
			'qf_type'  => 'text',
			'qf_attrs'  => array(
				'size' => TEXTAREA_COLS,
			),
		),
		// Site mail
		'email' => array(
			'type'    => 'varchar',
			'size'    => 255,
			'require' => true,
			'qf_label' => 'Email',
			'qf_type'  => 'text',
			'qf_attrs'  => array(
				'size' => TEXTAREA_COLS,
			),
		),
		// Site phone
		'phone' => array(
			'type'    => 'varchar',
			'size'    => 255,
			'require' => false,
			'qf_label' => 'Phone',
			'qf_type'  => 'text',
			'qf_attrs'  => array(
				'size' => TEXTAREA_COLS,
			),
		),
		// Site phone 2
		'phone2' => array(
			'type'    => 'varchar',
			'size'    => 255,
			'require' => false,
			'qf_label' => 'Phone 2',
			'qf_type'  => 'text',
			'qf_attrs'  => array(
				'size' => TEXTAREA_COLS,
			),
		),
		// Site address
		'address' => array(
			'type'    => 'varchar',
			'size'    => 255,
			'require' => false,
			'qf_label' => 'Address',
			'qf_type'  => 'text',
			'qf_attrs'  => array(
				'size' => TEXTAREA_COLS,
			),
		),
		// currency (fk: currencies)
		'currency' => array(
			'type'    => 'integer',
			'require' => true,
			'qf_type' => 'select',
			'qf_label' => 'Currency',
		),
	);

	/**
	 * Foregn keys columns.
	 *
	 * This is associative array field => foreign_table
	 */
	var $fk = array(
		'currency'	=> 'currencies',
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
	function dao_settings($table = '', $create = TABLE_CREATE_MODE)
	{
		$this->__construct($table, $create);
	}

	/**
	 * Constructor
	 */     
	function __construct($table = '', $create = TABLE_CREATE_MODE)
	{
		global $db, $tr, $current_lang, $available_langs, $table_prefix;

		if (!$table)
		{
			// default to this class name
			$table = $table_prefix . substr(get_class($this), 4);
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

		/**
		 * Create complex join statement with all fk's
		 */
		$select_join = $this->table.'.*, currencies.rate';
		$join = '';
		reset($this->fk);
		foreach($this->fk as $field => $table)
		{
			// rename column_name+'_fk' to avoid ambiguous column name in ``ORDER'' clause
			$select_join .= ','. $table_prefix . $table .'.name_'.$current_lang.' AS '.$field .'_fk';
			$join .= " LEFT JOIN {$table_prefix}{$table} ON ({$this->table}.$field = {$table_prefix}{$table}.id)";
		}
		reset($this->fk);
		$this->sql['all_fk_join'] = array(
			'select' => $select_join,
			'join'   => $join,
		);
	} // end Constructor

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