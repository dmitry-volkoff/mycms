<?php
/**
 * DAO pages
 *
 * @author vdb
 * @version CVS: $Id$
 */
//require_once('DB.php');
//require_once('DB/Table.php');

class dao_pages extends DB_Table
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
		// type (foreign key: page_types)
		'type' => array(
			'type'    => 'integer',
			'require' => true,
			'qf_label' => 'Type',
			'qf_type' => 'select',
			//'qf_type' => 'hidden',
		),
		// format (foreign key: page_formats)
		'format' => array(
			'type'    => 'integer',
			'require' => false,
			'qf_label' => 'Format',
			'qf_type' => 'hidden',
		),	 
		// menu liaison (foreign key: menu)
		'menu_liaison' => array(
			'type'    => 'integer',
			'require' => false,
			'qf_type' => 'select',
			'qf_label' => 'Menu liaison',
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
		// link path
		'link' => array(
			'type' => 'varchar',
			'size' => 255,
			'require' => false,
			'qf_label' => 'Link',
			'qf_type'  => 'text',
			'qf_attrs'  => array(
				'size' => TEXTAREA_COLS,
			),
		),	 
	);

	/**
	 * Foregn keys columns.
	 *
	 * This is associative array field => foreign_table
	 */
	var $fk = array(
		'type'	=> 'page_types',
	);

	/**
	 * Index definitions.
	 */
	var $idx = array(
		'id' => 'unique',
		'link' => 'unique',
		'date_enter' => 'normal',
	);

	/**
	 * SQL query definitions.
	 */
	var $sql = array(
		// multiple rows for a list
		'all' => array(
			'select' => '*'
		),
	);

	/**
	 * Constructor (php4)
	 */     
	function dao_pages($table = '', $create = TABLE_CREATE_MODE)
	{
		$this->__construct($table, $create);
	}

	/**
	 * Constructor
	 */
	function __construct($table = '', $create = TABLE_CREATE_MODE)
	{
		global $db, $tr, $table_prefix, $available_langs, $current_lang;

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
			// title
			$this->col['title_' . $lang] = array(
				'type'    => 'clob',
				'require' => false,
				'qf_label' => 'Title ('. $lang .')',
				'qf_type'  => 'text',
				'qf_attrs'  => array(
					'size' => TEXTAREA_COLS,
				),
			);

			// content
			$this->col['content_' . $lang] = array(
				'type'    => 'clob',
				'require' => false,
				'qf_label' => 'Content ('. $lang .')',
				'qf_type'  => 'textarea',
				'qf_attrs'  => array(
					'rows' => TEXTAREA_ROWS,
					'cols' => TEXTAREA_COLS,
				),
			);
			// Page description meta tag
			$this->col['description_' . $lang] = array(
				'type'    => 'clob',
				'require' => false,
				'qf_label' => 'Description ('. $lang .')',
				'qf_type'  => 'textarea',
				'qf_attrs'  => array(
					'rows' => 4,
					'cols' => TEXTAREA_COLS,
					'class' => 'noeditor',
				),
			);
			// Page keywords meta tag
			$this->col['keywords_' . $lang] = array(
				'type'    => 'clob',
				'require' => false,
				'qf_label' => 'Keywords ('. $lang .')',
				'qf_type'  => 'textarea',
				'qf_attrs'  => array(
					'rows' => 4,
					'cols' => TEXTAREA_COLS,
					'class' => 'noeditor',
				),
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

		/**
		 * Create complex join statement with all fk's
		 */
		$select_join = $this->table.'.*';
		$join = '';
		reset($this->fk);
		foreach($this->fk as $field => $table)
		{
			// rename column_name+'_fk' to avoid ambiguous column name in order clause
			$select_join .= ','.$table .'.name_'.$current_lang.' AS '.$field .'_fk';
			$join .= " LEFT JOIN $table ON ({$this->table}.$field = {$table}.id)";
		}
		reset($this->fk);
		$this->sql['all_fk_join'] = array(
			'select' => $select_join,
			'join'   => $join,
			'order'  => 
			$this->table .'.type,'. 
			$this->table .'.id', 
			//$this->table .'.title_'. $current_lang,
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

		// set default type/format/link (main site menu)
		if (! isset($data['type']) || ! $data['type']) { $data['type'] = PAGE_TYPE_PAGE; }
		if (! isset($data['format']) || ! $data['format']) { $data['format'] = PAGE_FORMAT_HTML; }
		if (! isset($data['link']) || ! $data['link']) { $data['link'] = $data['id']; }

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