<?php
/**
 * CMS Administration.
 *
 * @package admin
 * @author vdb
 * @version CVS: $Id$
 */

/**
 * Main index.
 */
//error_reporting(E_ALL);
$_SERVER['PHP_SELF'] = '';

// comment ini_set if you don't want to use the bundled PEAR libriaries
ini_set('include_path', '.'.PATH_SEPARATOR.'../lib/pear'); 

require_once("../config-local.php");
require_once("../config.php");
require_once("../lib/block.php");

/**
 * Here we go. $p is PEAR HTML_Page2 object defined in config
 */

// Set the page title
$p->setTitle("Administrative Tools");

// Generator meta tag
$p->unsetMetaData('Generator');

// Add optional meta data
$p->setMetaData("author", "vdb at mail.ru");

// Add stylesheet
$p->addStyleSheet('styles.css');

// Add script
$p->addScript('ie7/ie7-standard-p.js');
$p->addScript('menu.js');
$p->addScript('editor/tiny_mce.js');
//$p->addScript('editor/ckeditor.js');
//$p->addScript('editor/tiny_mce_gzip.php');
$p->addScript('editor.js');

//$p->disableXmlProlog();

// Main template
//$tpl = new HTML_Template_Flexy($flexy_options); already defined in config
$page = new stdClass;

// Auth
include_once("lib/login_form.php");
$auth = new Auth("DB", $auth_params, "login_form", false);
$auth->setSessionname('CSID');
$auth->start();

if (! ($auth->checkAuth() && ((int)$auth->getAuthData('id') === 1)) ) 
{ 
	$p->addBodyContent(login_form());
	$p->display();
	exit;
} 

// Main controller
$q = isset($_GET['q']) ? $_GET['q'] : '';
$q = str_replace('.',':', $q);
if (! is_readable('./blk/blk_'.$q.'.php')) 
{ 
	// lets try autodetect dictionary/datagrid block
	if (is_readable('../dao/dao_'.$q.'.php'))
	{
		$base_class = ($q === 'i18n' ? 'crud_form' : 'crud_form_dict');
		$search_fields = ($q === 'i18n' ? 'string_id", "name_' . $current_lang : 'name_' . $current_lang);
		eval('require_once("./lib/' . $base_class . '.php"); ' .
		'class blk_' . $q .' extends ' . $base_class . ' '.
		'{ function __construct(){ parent::__construct(); '.
		'$this->search_fields = array("'. $search_fields .'"); } }');
	} else if (substr($q, 0, 5) === 'menu_') {
		$base_class = 'blk_menu';
		eval('require_once("./blk/' . $base_class . '.php"); ' .
		'class blk_' . $q .' extends ' . $base_class . ' {}');
	} else {
		$q = DEFAULT_URL_ADMIN_PATH;
	}
}
if (is_readable('./blk/blk_'.$q.'.php')) 
{ 
	require_once('./blk/blk_'.$q.'.php');
}

$page->page_title = $tr->t(str_replace('_',' ',ucfirst($q)));
$page->date = date("d.m.Y");

// Generate admin menu
require_once('./blk/blk_menuadmin.php');
$blk = new blk_menuadmin;
$page->menu = $blk->out();

$blk_name = 'blk_'.$q;
$blk = new $blk_name;
$page->content = $blk->out();

$page->site_name = SITE_NAME;

// Put content into the body
$tpl->compile('index.html');
$p->addBodyContent($tpl->bufferedOutputObject($page));

// print to browser
$p->display();
?>