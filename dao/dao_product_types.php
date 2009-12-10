<?php
/**
 * DAO product_types
 *
 * @author vdb
 */

//require_once('DB.php');
//require_once('DB/Table.php');

class dao_product_types extends dao
{
	/**
	 * Constructor (php4).
	 */     
	function dao_product_types($table = '', $create = TABLE_CREATE_MODE)
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
		
		$this->fk['parent_id']  = 'product_types'; // self join
	} // function __construct($table = '', $create = TABLE_CREATE_MODE)


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
	//} // function insert($data)
}
?>