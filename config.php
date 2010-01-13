<?php
/*
 * Global configuration and initialization.
 *
 * @author  vdb
 * @version CVS: $Id$
 */
// compression
ini_set("zlib.output_compression", false);
ini_set("zlib.output_compression_level", 9);

// sometimes $_SERVER['DOCUMENT_ROOT'] is not available, so...
if (! isset($_SERVER['DOCUMENT_ROOT'])) 
{
	$script_name = end(explode('/', $_SERVER['SCRIPT_FILENAME']));
	$_SERVER['SCRIPT_NAME'] = $script_name;
	$_SERVER['DOCUMENT_ROOT'] =  
		realpath(
			reset(
				explode($_SERVER['SCRIPT_NAME'], 
					$_SERVER['SCRIPT_FILENAME'])
			)
		);
}

function __autoload($class_name) 
{
	if (substr($class_name, 0, 4) === 'blk_')
	{
		include_once('blk/'.$class_name . '.php');
	} else if (substr($class_name, 0, 3) === 'dao') {
		include_once('dao/'.$class_name . '.php');
	}
}


require_once("PEAR.php");
PEAR::setErrorHandling(PEAR_ERROR_PRINT);
//PEAR::setErrorHandling(PEAR_ERROR_RETURN);

/**
 * Default url path (QUERY_STRING).
 */
define('DEFAULT_URL_PATH', 'main');
define('DEFAULT_URL_ADMIN_PATH', 'site_pages');

/**
 * Page options.
 */
define('PAGE_TYPE_PAGE', 1);
define('PAGE_TYPE_FILE', 2);
define('PAGE_FORMAT_HTML', 1);
define('PAGE_FORMAT_PHP', 2);

/**
 * Form options.
 */
define('DATAGRID_ROWS_PER_PAGE', 20);
define('TEXTAREA_ROWS', 5);
define('TEXTAREA_COLS', 70);
define('TEXTAREA_BIG_ROWS', 10);
define('TEXTAREA_BIG_COLS', 70);
define('FORM_INPUT_NUMERIC_SIZE', 10);
define('FORM_DATE_MIN_YEAR', date('Y') - 2);
define('FORM_DATE_MAX_YEAR', date('Y') + 1);
define('FORM_DATE_FORMAT', 'd.m.Y');
define('DB_DATE_FORMAT', '%d.%m.%Y');
define('MAX_PHOTO_PER_OBJECT', 9);
define('THUMBNAIL_SMALL_SIZE', 50);
define('THUMBNAIL_MIDDLE_SIZE', 150);
define('THUMBNAIL_BIG_SIZE', 500);
define('UPLOAD_PHOTO_REL_PATH', 'photo');
define('UPLOAD_THUMB_REL_PATH', 'thumb');
define('UPLOAD_PHOTO_COMBINED_PATH', 'combo');

/**
 * Commission types
 */
define('COMMISS_PERCENT_PER_ORDER', 1);
define('COMMISS_FIXED_PER_ORDER', 2);
define('COMMISS_FIXED_PER_CLICK', 3);

/**
 * Block/page names.
 */ 
define('BLK_PRODUCT_DETAILS', 'productdetails');
define('BLK_PRODUCT_LIST', 'productslist');

/**
 * Words separator in the title.
 */
define('TITLE_SEPARATOR', ' - ');
 
/**
 * Table names.
 */ 
define('TABLE_PRODUCTS', 'products');
define('TABLE_LOGOTYPES', 'logotypes');


/**
 * DEFAULT LANG.
 */
define('DEFAULT_LANG', 'ru');
//$current_lang = 'ru';// now in -local
$available_langs = array('ru', 'en');
if (isset($_GET['lang']) && in_array($_GET['lang'], $available_langs))
{
    $current_lang = $_GET['lang'];
}

/** 
 * Security stuff.
 */
$protects = array('_REQUEST', '_GET', '_POST', '_COOKIE', '_FILES', '_SERVER', '_ENV', 'GLOBALS', '_SESSION');

foreach ($protects as $protect) {
	if ( in_array($protect , array_keys($_REQUEST)) ||
	     in_array($protect , array_keys($_GET)) ||
	     in_array($protect , array_keys($_POST)) ||
	     in_array($protect , array_keys($_COOKIE)) ||
	     in_array($protect , array_keys($_FILES))) {
	    die("Invalid Request.");
	}
}

if (stristr($_SERVER['PHP_SELF'], "config")) {
    Header("Location: ./index.php");
    die();
}

/**
 * PHP Compatibility.
 */
$raw = phpversion();
list($v_Upper,$v_Major,$v_Minor) = explode(".",$raw);

if (($v_Upper == 4 && $v_Major < 1) || $v_Upper < 4) {
	$_FILES =& $HTTP_POST_FILES;
	$_ENV =& $HTTP_ENV_VARS;
	$_GET =& $HTTP_GET_VARS;
	$_POST =& $HTTP_POST_VARS;
	$_COOKIE =& $HTTP_COOKIE_VARS;
	$_SERVER =& $HTTP_SERVER_VARS;
	$_SESSION =& $HTTP_SESSION_VARS;
	$_FILES =& $HTTP_POST_FILES;
}
/*
if (empty($_SERVER['PHP_SELF']))
{
    $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
}
$PHP_SELF = $_SERVER['PHP_SELF'];
*/
//$_SERVER['PHP_SELF'] = '';

/**
 * PHP settings
 */
ini_set('arg_separator.output',     '&amp;');
ini_set('magic_quotes_runtime',     0);
ini_set('magic_quotes_sybase',      0);
//ini_set('session.name',             'CSID');
if (defined('ADMIN_SESSION'))
{
	ini_set('session.name', 'ASID');
	ini_set('session.cache_limiter',    'nocache');
} else {
	ini_set('session.name', 'CSID');
	ini_set('session.cache_limiter',    'none');
}
ini_set('session.cache_expire',     3600 * 24 * 7);
//ini_set('session.cache_limiter',    'nocache');
ini_set('session.cookie_lifetime',  3600 * 24 * 356);
ini_set('session.gc_maxlifetime',   3600 * 24 * 7);
ini_set('session.save_handler',     'files'); // overwrite in Pear::Sessions object
ini_set('session.save_path',     $_SERVER['DOCUMENT_ROOT'] .'/tmp');
ini_set('session.use_only_cookies', 0);
// If you dont need transparent sid comment out the following 2 lines
//ini_set('session.use_trans_sid',    1);
//ini_set('url_rewriter.tags', 'a=href,area=href,frame=src,input=src');

/**
 * Correct double-escaping problems caused by "magic quotes" in some PHP
 * installations.
 */
function fix_gpc_magic() {
  static $fixed = false;
  if (!$fixed && ini_get('magic_quotes_gpc')) {
    array_walk($_GET, '_fix_gpc_magic');
    array_walk($_POST, '_fix_gpc_magic');
    array_walk($_COOKIE, '_fix_gpc_magic');
    array_walk($_REQUEST, '_fix_gpc_magic');
    $fixed = true;
  }
}

function _fix_gpc_magic(&$item) {
  if (is_array($item)) {
    array_walk($item, '_fix_gpc_magic');
  }
  else {
    $item = stripslashes($item);
  }
}

// Undo magic quotes
fix_gpc_magic();

/** 
 * Globals
 */
setlocale(LC_TIME, 'ru_RU.'. CHARSET); 
setlocale(LC_CTYPE, 'ru_RU.'.CHARSET); 

require_once("DB.php");
require_once('DB/Table.php');
require_once('HTML/Page2.php');

$p = new HTML_Page2(
array(
    // Sets the charset encoding (default: utf-8)
    //'charset'  => 'windows-1251',
    'charset'  => CHARSET,
    // Sets the line end character (default: unix (\n))
    'lineend'  => 'unix',
    // Sets the tab string for autoindent (default: tab (\t))
    'tab'  => '  ',
    // This is where you define the doctype
    //'doctype'  => "XHTML 1.0 Transitional",
    'doctype'  => "HTML 4.01 Transitional",
    //'doctype'  => "none",
    // Global page language setting
    'language' => 'ru',
    // If cache is set to true, the browser may cache the output.
    'cache'    => 'true'
));


/**
 * Global db object.
 */
$db =& DB::connect($dsn);
if (PEAR::isError($db)) 
{
    die('Global db object error: '.$db->getMessage());
}
$db->query('SET NAMES '.CHARSET);


/**
 * Global translation object.
 */
require_once("dao/dao_i18n.php");
$tr = new dao_i18n();
if ($tr->error) 
{
    die('dao_i18n error in config: '. 
   	$tr->error->message);
}

//$tr =& new i18n($db, 'i18n', 'drop');

/**
 * Translated messages for some QuickForm rules.
 */
if (! isset($GLOBALS['_DB_TABLE']['qf_rules'])) {
    $GLOBALS['_DB_TABLE']['qf_rules'] = array(
      'required'  => $tr->t('The item %s is required.'),
      'numeric'   => $tr->t('The item %s must be numbers only.'),
      'maxlength' => $tr->t('The item %s can have no more than %d characters.'),
      'email'     => $tr->t('The item %s must be valid email.'), 
    );
}


/******************* Site vars **********************/

require_once("dao/dao_settings.php");
$dao_settings = new dao_settings();
if ($dao_settings->error) 
{
    die('dao_settings error in config: '. 
   	$dao_settings->error->message);
}
//$dao_settings->fetchmode = DB_FETCHMODE_ASSOC;

$res = $dao_settings->selectResult('all_fk_join');
//if (PEAR::isError($res)) { die('dao_settings error in config: '.$res->getMessage()); }
if (PEAR::isError($res) || ! $res->numRows())
{ 
	// init first time only 
	/**
	 * Create auth table this way
	 */
	include_once("dao/dao_users.php");
	$dao_users = new dao_users(); 
	if ($dao_users->error)
	{
		die('dao_users error in config: '.$dao_users->error->message);
	}

	// check if we have at least 1 user...
	$res = $dao_users->selectResult('all');
	if (PEAR::isError($res)) { die('dao_users error in config: '.$res->getMessage()); }
	if (! $res->numRows())
	{
		$data = array('email' => 'admin@' . INITIAL_DOMAIN, 'login' => 'admin', 'password' => 'test', 'password2' => 'test', 'name_'. $current_lang => 'admin');
		$res = $dao_users->insert($data);
		if (PEAR::isError($res)) { die('dao_users insert error in config: '.$res->getMessage()); }
	}

	// create currencies table
	include_once("dao/dao_currencies.php");
	$dao_currencies = new dao_currencies();
	if (PEAR::isError($res)) { die('dao_currencies error in config: '.$res->getMessage()); }
	$res = $dao_currencies->selectResult('all');
	if (PEAR::isError($res)) { die('dao_currencies error in config: '.$res->getMessage()); }
	if (! $res->numRows())
	{
		$data = array(
			'id' => 1,
			'name_'.$current_lang => 'p.', 
			'rate'	=> 30); 
		$dao_currencies->insert($data);
	}
	
	// create session table
	include_once("dao/dao_sessions.php");
	$dao_sessions = new dao_sessions(); 
	if ($dao_sessions->error)
	{
		die('dao_sessions error in config: '.$dao_sessions->error->message);
	}
	unset($dao_sessions);

	// create page_types table
	include_once("dao/dao_page_types.php");
	$dao_page_types = new dao_page_types();
	if (PEAR::isError($res)) { die('dao_page_types error in config: '.$res->getMessage()); }
	$res = $dao_page_types->selectResult('all');
	if (PEAR::isError($res)) { die('dao_page_types error in config: '.$res->getMessage()); }
	if (! $res->numRows())
	{
		$data = array(
			'id' => 1,
			'name_'.$current_lang => 'Page');
		$dao_page_types->insert($data);
	}
		
	// insert initial settings 
	$data = array(
		'fqdn'	=> INITIAL_DOMAIN, 
		'name'	=> INITIAL_DOMAIN, 
		'email'	=> 'info@' . INITIAL_DOMAIN,
		'currency' => 1);
	$dao_settings->insert($data);
	$res = $dao_settings->selectResult('all_fk_join');
	if (PEAR::isError($res)) { die('dao_settings error in config: '.$res->getMessage()); }
}

$row = $res->fetchrow();
$default_val = $tmp = 'undefined';

foreach($dao_settings->col as $key => $val)
{
	if (isset($row->{$key}))
	{
		$tmp = trim($row->{$key});
	} elseif (isset($row->{$key.'_fk'})) {
		$tmp = trim($row->{$key.'_fk'});
	} else {
		$tmp = $default_val;
	}
	define(strtoupper('SITE_'.$key), $tmp);
}

// Currency name/rate
$key = 'currency';
if (isset($row->{$key.'_fk'}))
{
	$tmp = trim($row->{$key.'_fk'});
} else {
	$tmp = 'USD';
}
define(strtoupper('SITE_'.$key.'_NAME'), $tmp);

$key = 'rate';
if (isset($row->{$key}))
{
	$tmp = trim($row->{$key});
} else {
	$tmp = 1;
}
define(strtoupper('SITE_CURRENCY_'.$key), $tmp);

/******* end site vars ********/

/**
 * Create session object.
 */
require_once 'HTTP/Session.php';
HTTP_Session::useTransSID(false);
HTTP_Session::useCookies(true);

HTTP_Session::setContainer('DB', array('dsn' => $dsn, 'table' => $table_prefix .'sessions'));

HTTP_Session::start(ini_get('session.name'));
//HTTP_Session::setExpire(time() + 60);   // set expire to 60 seconds
//HTTP_Session::setIdle(time() + 5); 


/**
 * Configuration for PEAR::AUTH.
 */
$auth_params = array(
  "dsn" => $dsn,
  "table" => $table_prefix . "users",
  "usernamecol" => "login",
  "passwordcol" => "password",
  "cryptType"	=> "none",
  "db_fields"	=> "*",
);

include_once("Auth.php");

// Auth
include_once("lib/login_form.php");
$auth = new Auth("DB", $auth_params, "login_form", false);
$auth->setSessionname(ini_get('session.name'));
$auth->start();

/**
 * Template.
 */
require_once 'HTML/Template/Flexy.php';

$flexy_options = array(
    'templateDir'   => './tpl',
    'compileDir'    => './tpc',
    'forceCompile'  => 0,
    'debug'         => 0,
    'compiler'      => 'Flexy',
    'charset'       => CHARSET,
    'Translation2'  => &$tr,
    'locale'        => 'ru',
    'nonHTML'	    => false,
);

// Main template
$tpl = new HTML_Template_Flexy($flexy_options);

/* 
 * Cache.
 */
$cache_options = array(
	'cacheDir' => './tmp/',
	'lifeTime' => 600,
	'hashedDirectoryLevel' => 1,
	'caching' => true,
);

//include_once('Cache/Lite.php');
include_once('lib/cache-lite.php');
$cache = new Cache_Lite($cache_options);
?>