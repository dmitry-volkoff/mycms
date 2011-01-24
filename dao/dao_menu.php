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
			// Link parameters (class, id, onClick etc.)
			'link_par' => array(
				'type'    => 'varchar',
				'size'    => 255,
				'qf_label' => 'Link Param',
				'qf_attrs' => array(
	 				'size' => TEXTAREA_COLS,
				),
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
			$this->table .'.'. $this->db->$iq('hide') .', '.
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
		// set default menu type (main site menu)
		if (! isset($data['type']) || ! $data['type']) { $data['type'] = 1; }

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
}
?>