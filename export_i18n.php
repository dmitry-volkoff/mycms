<?php
ini_set('include_path', '.'.PATH_SEPARATOR.'./lib/pear');
require_once("./config.php");

/**
 * Global db object
 */
//include_once("DB.php"); 
//$db =& DB::connect($dsn);
//if (PEAR::isError($db)) 
//{
//    die($db->getMessage());
//}
//$db->query('SET NAMES CP1251');

    include_once("./dao/dao_i18n.php");
    $dao = new dao_i18n;
	if ($dao->error) 
	{
	    die('dao error: '.$dao->error->message);
	}

    $res = $dao->selectResult('all');
    while ($row = $res->fetchrow())
    {
	echo $row->id.':::'.$row->string_id.':::'.$row->name_en.':::'.$row->name_ru;
    }
?>