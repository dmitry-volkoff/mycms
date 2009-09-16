<?php
/**
 * DAO users
 *
 * @author vdb
 * @version CVS: $Id$
 */
//require_once('DB.php');
//require_once('DB/Table.php');

class dao_users extends DB_Table
{
	/**
	 * Last insert ID.
	 */
	var $last_insert_id = 0;

	/** 
	 * Primary key increment method.
	 *
	 * 0 - for normal autoincrement key.
	 * 1 - for special "power of 2" increment method.
	 */
	var $id_type = 0; // normal

	/** 
	 * Column definitions. 
	 */
	var $col = array(
         // unique ID
         'id' => array(
         'type'    => 'integer',
         'require' => true,
	 'qf_type' => 'hidden',
	 'qf_label' => 'ID',
         ),
         // login
         'login' => array(
         'type'    => 'varchar',
	 'size'    => 40,
         'require' => true,
	 'qf_label' => 'Login',
	 'qf_type'  => 'text',
	 'qf_attrs'  => array(
	     'size' => 40,
	     ),
         ),
         // password
         'password' => array(
         'type'    => 'varchar',
	 'size'    => 200,
         'require' => true,
	 'qf_label' => 'Password',
	 'qf_type'  => 'password',
	 'qf_attrs'  => array(
	     'size' => 40,
	     ),
         ),
         // password2 - fake entry to easy form creation
         'password2' => array(
         'type'    => 'varchar',
	 'size'    => 200,
         'require' => true,
	 'qf_label' => 'Password (again)',
	 'qf_type'  => 'password',
	 'qf_attrs'  => array(
	     'size' => 40,
	     ),
         ),
         // City
         'city' => array(
         'type'    => 'varchar',
	 'size'    => 150,
         'require' => false,
	 'qf_label' => 'City',
	 'qf_type'  => 'text',
	 'qf_attrs'  => array(
	     'size' => TEXTAREA_COLS,
	     ),
         ),
         // email
         'email' => array(
         'type'    => 'varchar',
	 'size'    => 150,
         'require' => true,
	 'qf_label' => 'Email',
	 'qf_type'  => 'text',
	 'qf_attrs'  => array(
	     'size' => TEXTAREA_COLS,
	     ),
         ),
         // ICQ
         'icq' => array(
         'type'    => 'varchar',
	 'size'    => 150,
         'require' => false,
	 'qf_label' => 'ICQ',
	 'qf_type'  => 'text',
	 'qf_attrs'  => array(
	     'size' => TEXTAREA_COLS,
	     ),
         ),
         // Site
         'site' => array(
         'type'    => 'varchar',
	 'size'    => 255,
         'require' => false,
	 'qf_label' => 'Site',
	 'qf_type'  => 'text',
	 'qf_attrs'  => array(
	     'size' => TEXTAREA_COLS,
	     ),
         ),
         // User phone
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
         // Notes
         'note' => array(
         'type'    => 'clob',
         'require' => false,
	 'qf_label' => 'Notes',
	 'qf_type'  => 'textarea',
	 'qf_attrs'  => array(
		'rows' => TEXTAREA_ROWS,
		'cols' => TEXTAREA_COLS,
	 	),
         ),
         // Registration date
         'reg_date' => array(
         'type'    => 'date',
         'require' => false,
	 'qf_label' => 'Date',
	 'qf_opts' => array(
	     'format'  => FORM_DATE_FORMAT,
	     'language'=> DEFAULT_LANG,
	     'minYear' => FORM_DATE_MIN_YEAR,
	     'maxYear' => FORM_DATE_MAX_YEAR,
	     ),
         ),
	);

	/**
	 * Index definitions.
	 */
	var $idx = array(
        'id' => 'unique',
	'login' => 'unique',
	'reg_date' => 'normal',
	);

	/**
	 * SQL query definitions.
	 */
	var $sql = array(
        // multiple rows for a list
        'all' => array(
	'select' => '*',
	'get'   => 'assoc',
	),
	);

	/**
	 * Foreign key definitions.
	 */
	var $fk = array();
    
	/**
	 * Constructor (php4)
	 */     
	function dao_users($table = '', $create = TABLE_CREATE_MODE)
	{
		$this->__construct($table, $create);
	}

	/**
	 * Constructor
	 */
	function __construct($table = '', $create = TABLE_CREATE_MODE)
	{
		global $db, $tr, $table_prefix, $available_langs, $current_lang;

		if (! $table)
		{
			// default to this class name
			$table = $table_prefix . substr(get_class($this), 4);
		}

		/**
		 * Dynamic columns.
		 */
		foreach($available_langs as $key => $lang)
		{
			// real user name
			$this->col['name_' . $lang] = array(
			'type'    => 'varchar',
			'size'    => 255,
			//'require' => ($lang === $current_lang),
			'require' => false,
			'qf_label' => 'User Name ('. $lang .')',
			'qf_type'  => 'text',
			'qf_attrs'  => array(
				'size' => TEXTAREA_COLS,
	     			),
			);
		}

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

		$this->col['email']['qf_rules'] = 
		array('email' => 
		str_replace('%s', $this->col['email']['qf_label'].':', $GLOBALS['_DB_TABLE']['qf_rules']['email']) 
		);

		/**
		 * Create complex join statement with all fk's
		 */
		$select_join = $this->table.'.*';
		$join = '';
		reset($this->fk);
		foreach($this->fk as $field => $table)
		{
			// rename column_name+'_fk' to avoid ambiguous column name in ``ORDER'' clause
			$select_join .= ','. $table_prefix . $table .'.name_'.$current_lang.' AS '.$field .'_fk';
			if ($table === 'commiss_types')
			{
				$select_join .= ','. $table_prefix . $table .'.val'.' AS val';
			}
			$join .= " LEFT JOIN {$table_prefix}{$table} ON ({$this->table}.$field = {$table_prefix}{$table}.id)";
		}
		reset($this->fk);
		$this->sql['all_fk_join'] = array(
			'select' => $select_join,
			'join'   => $join,
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
	function insert($data)
	{
		// force a new ID on the data
		$data['id'] = $this->nextID();
		$this->last_insert_id = $data['id'];
		
		if (! isset($data['reg_date']) || ! $data['reg_date'])
		{
			$data['reg_date'] = date('Y-m-d');
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
	 * Handle updates
	 *
	 * @param $data data array to update
	 * @param $where sql where condition
	 * @return integer true or PEAR error object
	 */    
	function update($data, $where)
	{
		// Compare existing commiss_type value with the value to be updated.
		// If they are not equal, change default commiss_value accordingly.
		
		// get ald value
		$view = 'all';
		$res = $this->selectResult($view, $where);
		if (PEAR::isError($res)) { die('dao error: '. get_class($this) .' - '.$res->getMessage()); }
		if (! $res->numRows())
		{
			return PEAR::raiseError('Record does not exist.');
		}
		
		// auto-validate and update, return the success flag
		// or PEAR_Error
		return parent::update($data, $where);
	} // function update($data, $where)

	/**
	 * Handle deletes
	 *
	 * @param where
	 *     delete condition ('field = value')
	 * @return 
	 *     mixed true or PEAR error object
	 */
	function delete($where)
	{
		//if ($this->hasSubNodes($id)) 
		//{
		//	return PEAR::throwError("cannot delete '$id' -- has subnodes");
		//}
		
		// parse where condition
		$params = explode('=', $where);
		$par0 = trim($params[0]);
		$id = (int) trim($params[1]);
		
		// See if dumb heads staying on crack are trying to delete admin...
		if (($par0 === 'id') && ($id === 1))
		{
			return PEAR::raiseError('Sorry, admin is God.');
		}

		// now real delete in the table 'orders'
		return parent::delete($where);
	} // function delete($id)
}
?>