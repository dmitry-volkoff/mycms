<?php
/** \mainpage
 *
 * \section intro_sec Introduction
 *
 * Footbolka.ru shop.
 * @author vdb
 * @version CVS: $Id$
 */

/*
 * Main index.
 */
//error_reporting(E_ALL);

// comment ini_set if you don't want to use the bundled PEAR libriaries
ini_set('include_path', '.'.PATH_SEPARATOR.'./lib/pear'); 

require_once("./config.php");
require_once("./lib/common.php");

$valid_hostname = 
	isset($_SERVER["HTTP_HOST"]) && 
	defined("SITE_FQDN") && 
	($_SERVER["HTTP_HOST"] === ('www.'.SITE_FQDN));

/*
if (! $valid_hostname && defined("SITE_FQDN") && SITE_FQDN)
{
	header("HTTP/1.1 301 Moved Permanently");
	$request = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '/';
	header("Location: http://".'www.'.SITE_FQDN. $request);
	exit();
}
*/

// Main controller
//$q =& common::get_module_name();
$q =& common::get_path_info();
$q1 = $q; // real module

require_once("./lib/block.php");
require_once("./dao/dao_pages.php");


// Here we go. $p is PEAR HTML_Page2 object defined in config

// Generator page meta tag
$p->unsetMetaData('Generator');

// Add optional meta data
$p->setMetaData("author", "vdb at mail.ru"); 
$p->setMetaData("revisit", "7 days");
$p->setMetaData("robots", "index, follow");

//$p->setMetaData("verify-v1", "UHCrkgptiFoZdF+kRPLV4TBgQc6zNHcp0k40gLCDi/g=");

// Add stylesheet
$p->addStyleSheet('style.css');

// Add script
//$p->addScript('script.js');
$p->addScript('email.js');
//$p->addScript('aserver.php?client=all');
//$p->addScript('HTML_AJAX.js');

// Add base href
$p->_links[] = '<base href="http://' .BASE_HREF. '"';
//$p->disableXmlProlog();

// Add favicon.ico
$p->addFavicon('http://' .BASE_HREF. 'favicon.ico');

// Add Raw data to header. Usually, IE hacks with comments.
$headfile = './ie6.head.html';
if (is_readable($headfile))
{
	$p->setRawData(trim(implode(' ', file($headfile))));
}

// Auth
//include_once("lib/login_form.php");
//$auth = new Auth("DB", $auth_params, "login_form", false);
//$auth->setSessionname(ini_get('session.name'));
//$auth->start();

//$auth->checkAuth();

// Main template
//$tpl =& new HTML_Template_Flexy($flexy_options);
$main_tpl = 'index_'.str_replace('/','_', $q).'.html';
$page =& new stdClass;

class controller {
    var $masterTemplate = "master.html";
    var $bodyTemplate =   "body.html";
    
    function blk_menu($string) {
        echo ucfirst($string);
    }
    
    function includeBody() {
        $template = new HTML_Template_Flexy();
        $template->compile($this->bodyTemplate);
        $template->outputObject($this);
    }
}


// Put content into the body
//$tpl->compile('header.html');
//$page->header = $tpl->bufferedOutputObject($page);

// main content
//$pages =& new pages('pages', 'drop');
$pages =& new dao_pages();
$res = $pages->selectResult('all', 'link = '.$pages->quote($q));

$active_id = 0; // menu active id
$active_link = ''; // menu active link
$title = SITE_NAME; // html title
$page->site_phone = SITE_PHONE;
$page->site_address = SITE_ADDRESS;

if (empty($title)) { $title = SITE_FQDN; }

if (! $res->numRows())
{
	// Check if page is called by id (no url alias)
	if (is_numeric($q) && $q)
	{
		$res = $pages->selectResult('all', 'id = '.$pages->quote((int)$q));
	} else {
		// get parent module name as last ressort. Ex: /main/xxx/bla. Get xxx if bla is not found etc.
		//$q1 =& common::get_module_name();
		$q1 = ''; $arg_num =& common::arg_num();
		if ($arg_num > 1)
		{
			for($i = $arg_num - 1; $i >= 0; $i--) 
			{
				$q1 = common::narg($i);
				$active_link = $q1; 
				//echo 'active_link: '.$q1.'<br />';
				//echo 'arg_num:'.$i.'<br />';
				//echo 'narg:'.common::narg($i).'<br />';
				$res = $pages->selectResult('all', 'link = '.
					$pages->quote($q1));
			
				if ($res->numRows())
				{
					$active_link = $q1; 
					//echo 'active_link2: '.$q1.'<br />';
					break;
				}
			}
		}
		//$res = $pages->selectResult('all', 'link = '.$pages->quote(common::get_module_name()));
	}
}

if ($res->numRows())
{
    $row = $res->fetchrow();
    $page->title = $row->{'title_'.$current_lang} ? $row->{'title_'.$current_lang} : 
	($current_lang == 'en' ? $tr->tl($row->title_ru) : $row->title_en);
    if ($page->title) { $title = $title .TITLE_SEPARATOR.$page->title; }
    $active_id = $row->menu_liaison;   
    
    switch ($row->type)
    {
	case PAGE_TYPE_PAGE:
	    $page->blk_content = $row->{'content_'.$current_lang} ? $row->{'content_'.$current_lang} :
		($current_lang == 'en' ? $tr->tl($row->content_ru) : $row->content_en);
	    if (strpos($page->blk_content,'<?') === 0) // php page
	    {
		$page->blk_content = $row->{'content_'.$current_lang} ? common::php_eval($row->{'content_'.$current_lang}) :
		    ($current_lang == 'en' ? $tr->tl(common::php_eval($row->content_ru)) : $row->content_en);
	    } else {
		// combine static/dynamic parts
		//echo 'q='.$q.'<br />';
		//echo 'active_link:'.$active_link.'<br />';
		
		$active_block = ''; // script real name
		if (is_readable('./blk/blk_'.str_replace('/','_',$q).'.php'))
		{
			$active_block = str_replace('/','_',$q);
		} else {
			// lets parse active link
			$q1 = ''; $arg_num =& common::arg_num();
			if ($arg_num != 0)
			{
				for($i = $arg_num - 1; $i >= 0; $i--) 
				{
					// try find on disk files like 'name_arg1_arg2_arg3' etc.
					$q1 = str_replace('/','_', common::narg($i));
					//echo 'q1='.$q1."\n<br />";
					if (is_readable('./blk/blk_'.$q1.'.php'))
					{
						$active_block = $q1;
						break;
					}
				}
			}
		}
		//echo 'active_block:'.$active_block."\n<br />";
		if ($active_block)
		{
			include_once('./blk/blk_'.$active_block.'.php');
			$blk_name = 'blk_'. $active_block;
			$blk =& new $blk_name;
			$page->blk_content = $blk->out($page->content);		
		}
	    }
	    break;
	default:
	    $page->blk_content = $tr->t('Unsupported page type');
    }
} else {
    header("HTTP/1.1 404 Not Found");
    $page->title = $tr->t('Under construction');
    //$page->title = $tr->t('Page not found');
    $page->blk_content = '';
}

// Set the page title
$p->setTitle($title);

// set page specific meta data
if (!empty($row->{'description_' . $current_lang}))
{
	$page->description = trim($row->{'description_' . $current_lang}); 
	$p->setMetaData('description', $page->description);
} else {
	// Set title as description meta-tags
	$p->setMetaData('description', str_replace(TITLE_SEPARATOR, '. ', $title));
}

if (!empty($row->{'keywords_' . $current_lang}))
{
	$page->keywords = trim($row->{'keywords_' . $current_lang}); 
	$p->setMetaData('keywords', $page->keywords);
} else {
	// Set title as keywords meta-tags
	$p->setMetaData('keywords', str_replace(TITLE_SEPARATOR, ', ', $title));
}

// create site menu
//require_once('./blk/blk_menu.php'); // autoload class
//$blk = new blk_menu($active_id);
//$blk->active_id = $active_id;

//echo 'active:'.$active_id.'<br />';

//$page->blk_menu = $blk->out();

//$tpl->compile('footer.html');
//$page->footer = $tpl->bufferedOutputObject($page);

//$addr = explode('@', SITE_EMAIL);
//$page->mailto = '<script language="javascript">email("'.$addr[0].'","'.$addr[1].'");</script>';

// additional content on main page
/*
if ($q === DEFAULT_URL_PATH)
{
	$page->main_add = 1;
	include_once('./blk/blk_news.php');
	$blk = new blk_news;
	
	// create news block
	$page->news = $blk->out();
}
*/
// choose main template
if (! is_readable('./tpl/'.$main_tpl)) 
{ 
	$main_tpl = 'index.html'; 
}

// find all blocks in the template
$blocks = array();
preg_match_all('/{(blk_[a-z0-9_\(\)]+)/', file_get_contents('./tpl/'.$main_tpl), $blocks);

$blocks = $blocks[1]; // real block names without modifiers


//echo '<pre>';
//echo '$main_tpl: '.$main_tpl ."\n";
//var_dump($blocks);



//class blk_menu_hor extends blk_menu {};
//$blk = new blk_menu_hor($active_id);
//$page->blk_menu_hor = $blk->out();

foreach ($blocks as $block)
{
	$param = '';
	$module = $block;
	
	// FIXME: left main content constant for now
	if ($block == 'blk_content') { continue; }

	// check module existence
	if (! is_readable('./blk/'.$block .'.php')) 
	{
		$module = '';
		
		// check if block has parameters inside braces
		$brace = strpos($block, '(');
		if ($brace !== false) 
		{
		 	$param = substr(substr($block, $brace + 1), 0, strlen($block) - $brace -2);
			$module = substr($block, 0, $brace);
			//echo 'param='.$param.' // $block='. $block .' // $block_tmp='. $block_tmp ."<br>";
			if (! is_readable('./blk/'.$module .'.php')) 
			{
				$module = '';
			}
		}
		if ($module && $param)
		{
			$params = explode(',', $param);
			foreach($params as $key=>$par)
			{
				//$par = '$'.trim($par);
				$par = trim($par);
				//echo '$par='.$par.' // $$par='.$$par."<br>";
				//echo 'active_id='.$active_id."<br>";
				$params[$key] = isset ($$par) ? $$par : '';
			}
			$param = implode(',', $params);
		}
		if ($module)
		{
			//echo '$blk = new '.$module.'('.$param.');' ."<br>" ;
			eval('$blk = new '.$module.'('.$param.');');
			$page->{$block} = $blk->out();
		} else {
			$page->{$block} = 'unknown block';
		}
	} // check module existence
}
//var_dump($page);
//echo '</pre>';

$tpl->compile($main_tpl);

$p->addBodyContent($tpl->bufferedOutputObject($page));

HTTP_Session::updateIdle();

// print to browser
$p->display();
?>