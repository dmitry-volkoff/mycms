<?php
/**
 * DAO i18n
 *
 * @author vdb
 * @version CVS: $Id$
 */
//require_once('DB.php');
//require_once('DB/Table.php');

class dao_i18n extends DB_Table
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
	 * Aassociative array with translation strings.
	 */
	var $trans = array(); 

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
		// unique string ID (english)
		'string_id' => array(
			'type'    => 'varchar',
			'size'    => 255,
			'require' => true,
			'qf_label' => 'String ID',
		),
		// english translation
		'name_en' => array(
			'type'    => 'clob',
			'require' => false,
			'qf_label' => 'English',
		),
		// russian transaltion
		'name_ru' => array(
			'type'    => 'clob',
			'require' => false,
			'qf_label' => 'Russian',
		),
	);

	/**
	 * Index definitions.
	 */
	var $idx = array(
		'id' => 'unique',
		'string_id' => 'unique',
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
	 *
	 * @param $table string database table name
	 * @param $create_flag string database table create mode (DB_TABLE constant)
	 */     
	function dao_i18n($table = '', $create_flag = TABLE_CREATE_MODE)
	{
		$this->__construct($table, $create_flag);
	}

	/**
	 * Constructor
	 *
	 * @param $table string database table name
	 * @param $create_flag string database table create mode (DB_TABLE constant)
	 */
	function __construct($table = '', $create_flag = TABLE_CREATE_MODE)
	{
		global $db, $table_prefix;

		if (!$table)
		{
			// default to this class name
			$table = $table_prefix . substr(get_class($this), 4);
		}

		DB_Table::DB_Table($db, $table, $create_flag);
		$this->fetchmode = DB_FETCHMODE_OBJECT;
	} // Constructor    

	/**
	 * Fetch all records and populate array $trans.
	 */
	function make_trans()
	{
		global $current_lang;

		$this->trans = array();
		$res = $this->selectResult('all');
		if (PEAR::isError($res)) {
			// return the error
			die($res->getmessage());
		}

		while($row = $res->fetchrow())
		{
			$this->trans[strtolower($row->string_id)] = $row->{'name_'.$current_lang};
		}
	}

	/**
	 * Translate $string into $current_lang
	 *
	 * @param $string string to translate
	 * @return string translated string
	 */
	function t($string)
	{
		if (! $string) { return ''; }
		$string0 = strtolower($string);

		// check if array $trans is filled in
		if (! count($this->trans))
		{
			$this->make_trans();
		}

		// check if string exists...
		if (array_key_exists($string0, $this->trans))
		{
			// check if string is translated...
			if (isset($this->trans[$string0]) && $this->trans[$string0])
			{
				return $this->trans[$string0];
			} else {
				// return untranslated
				return $string;
			}
		} else {
			// and if it is not exists, insert new string into table for further translation
			$data = array('string_id' => $string);
			$res = $this->insert($data);
			// add new string to array
			$this->trans[$string0] = '';
			return $string;
		}
	}

	/**
	 * alias of t()
	 *
	 * @param $string string to translate
	 * @return string translated string
	 */
	function get(&$string)
	{
		return $this->t($string);
	}

	/**
	 * Transliterate $cyr_string into latin charset.
	 *
	 * @param $cyr_str cyrillic string to translate
	 * @return string transliterated string
	 */
	function tl($cyr_str) 
	{
		$tr_letters = array(
			"¥"=>"G","¨"=>"Yo","ª"=>"E","¯"=>"Yi","²"=>"I",
			"³"=>"i","´"=>"g","¸"=>"yo","¹"=>"#","º"=>"e",
			"¿"=>"yi","À"=>"A","Á"=>"B","Â"=>"V","Ã"=>"G",
			"Ä"=>"D","Å"=>"E","Æ"=>"Zh","Ç"=>"Z","È"=>"I",
			"É"=>"Y","Ê"=>"K","Ë"=>"L","Ì"=>"M","Í"=>"N",
			"Î"=>"O","Ï"=>"P","Ð"=>"R","Ñ"=>"S","Ò"=>"T",
			"Ó"=>"U","Ô"=>"F","Õ"=>"H","Ö"=>"Ts","×"=>"Ch",
			"Ø"=>"Sh","Ù"=>"Sch","Ú"=>"'","Û"=>"Yi","Ü"=>"",
			"Ý"=>"E","Þ"=>"Yu","ß"=>"Ya","à"=>"a","á"=>"b",
			"â"=>"v","ã"=>"g","ä"=>"d","å"=>"e","æ"=>"zh",
			"ç"=>"z","è"=>"i","é"=>"y","ê"=>"k","ë"=>"l",
			"ì"=>"m","í"=>"n","î"=>"o","ï"=>"p","ð"=>"r",
			"ñ"=>"s","ò"=>"t","ó"=>"u","ô"=>"f","õ"=>"h",
			"ö"=>"ts","÷"=>"ch","ø"=>"sh","ù"=>"sch","ú"=>"'",
			"û"=>"yi","ü"=>"","ý"=>"e","þ"=>"yu","ÿ"=>"ya"
		);

		return strtr($cyr_str, $tr_letters);
	}

	/**
	 * Handle inserts
	 *
	 * @param $data array assoc data array to insert
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
	 * Override the parent create() 
	 *
	 * Remember to include the creation flag parameter!
	 * @param $create_flag string database table create mode (DB_TABLE constant)
	 */
	function create($create_flag)
	{
		global $tr;
		// call the parent create() first
		$result = parent::create($create_flag);

		// was the table created?
		if (PEAR::isError($result) || ! $result) 
		{
			// table not created
			return $result;
		} else {
			// table created successfully; insert some rows...
			$data_file = './i18n.txt';
			if (! is_readable($data_file))
			{
				die('Fatal: Cant read '.htmlspecialchars($data_file));
			}

			$data = file($data_file);
			$res = null;

			foreach($data as $key => $values)
			{
				$val = explode(':::', $values);
				$cols = array(
					'id'      => $val[0],
					'string_id'=> $val[1],
					'name_en' => $val[2],
					'name_ru' => $val[3],
				);

				$res = $this->insert($cols);

				if (PEAR::isError($res)) 
				{ 
					return PEAR::throwError('Fatal: '.htmlspecialchars($res->getMessage())); 
				}
			}
			// ... and return the insert results.
			return $res;
		}
	}

}
?>