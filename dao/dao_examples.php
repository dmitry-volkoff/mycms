<?php
//require_once('DB.php');
//require_once('DB/Table.php');

class dao_examples extends DB_Table
{
	var $last_insert_id = 0;
	var $id_type = 0; // normal

	var $col = array(         
		// unique row ID
		'id' => array(
			'type'    => 'integer',
			'require' => true,
			'qf_type' => 'hidden',
			'qf_label' => 'ID',
		),
		// Wholesale price
		'price' => array(
			'type'    => 'decimal',
			'size' => 10,
			'scope' => 2,
			'qf_label' => 'Price',
		),
		// Date entered - when this item was first entered in the DB
		'date_enter' => array(
			'type'    => 'date',
			'qf_label' => 'Date entered',
			'qf_opts' => array(
				'format'  => FORM_DATE_FORMAT,
				'language'=> DEFAULT_LANG,
				'minYear' => FORM_DATE_MIN_YEAR,
				'maxYear' => FORM_DATE_MAX_YEAR,
			),
		),
		// hot offer - flag to promote item as best offer
		'hot_offer' => array(
			'type'    => 'boolean',
			'qf_label' => 'Hot Offers',
		),
		// hide - flag to not display this item
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

	/**
	 * Foregn keys columns.
	 *
	 * This is associative array field => foreign_table
	 */
	var $fk = array(
		);

	var $idx = array(
		'id' => 'unique',
		'name_ru' => 'normal',
		'price' => 'normal',
		'priority' => 'normal',
	);

	var $sql = array(
		// multiple rows for a list
		'all' => array(
			'select' => '*'
		),
		'all_fk_join' => array(
			'select' => '*',
		),
		// get uniq brands
		'distinct_brands' => array(
			'select' => '*'
		),
		// get uniq brands + models
		'distinct_brands_models' => array(
			'select' => '*'
		),
	);

	/**
	 * Constructor (php4)
	 */     
	function dao_examples($table = '', $create = TABLE_CREATE_MODE)
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

		// Dynamic columns
		foreach($available_langs as $key => $lang)
		{
			// name
			$this->col['name_' . $lang] = array(
				'type'    => 'varchar',
				'size'    => 255,
				'require' => ($lang === $current_lang),
				'qf_label' => 'Name ('. $lang .')',
				'qf_type'  => 'text',
				'qf_attrs' => array(
					'size' => TEXTAREA_COLS,
				),
			);
			// Description - full item description
			$this->col['description_' . $lang] = array(
				'type'    => 'clob',
				'qf_label' => 'Description ('. $lang .')',
				'qf_type'  => 'textarea',
				'qf_attrs'  => array(
					'rows' => TEXTAREA_ROWS,
					'cols' => TEXTAREA_COLS,
				),
			);
		}

		foreach($this->col as $key => $val)
		{
			if (isset($val['qf_label']) && $val['qf_label'])
			{
				$this->col[$key]['qf_label'] = trim($tr->t($val['qf_label']));
			} else {
				$this->col[$key]['qf_label'] = $tr->t($key);
			}
		}

		DB_Table::DB_Table($db, $table, $create);
		$this->fetchmode = DB_FETCHMODE_OBJECT;

	} // Constructor


	/** 
	 * Override the parent create() 
	 *
	 * Remember to include the creation 
	 * flag parameter!
	 */
/*********************
	function create($flag)
	{
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
		'name'      => 'Home',
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
**********************/    

	/**
	 * Handle inserts
	 *
	 * @param array data to insert
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

	/**
	 * Handle updates
	 *
	 * @param $data data array to update
	 * @param $where sql where condition
	 * @return integer true or PEAR error object
	 */    
	function update($data, $where)
	{
		// auto-validate and update, return the success flag
		// or PEAR_Error
		return parent::update($data, $where);
	}
}
?>