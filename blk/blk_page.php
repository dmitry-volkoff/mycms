<?php
/**
 * Info Block.
 *
 * @author vdb
 * @version CVS: $Id$
 */

class blk_page extends block
{
	var $page_name = ''; 
	var $dao_name = 'pages'; // db_table 

	function __construct()
	{
		$this->cache = false;
		$this->template = 'blk_page.html';
		if (is_readable('./tpl/'.get_class($this) .'.html')) 
		{ 
			$this->template = get_class($this) .'.html'; 
		}
	}

	function init($page_name)
	{
		$this->page_name = $page_name; 
	}

	function output()
	{
		global $tpl, $tr, $current_lang;

		//$page = new stdClass;
		$page = new page;
		$pages =& new dao_pages();
		$res = $pages->selectResult('all', 'link = '.$pages->quote($this->page_name));
		$page->site_phone = SITE_PHONE;
		$page->site_address = SITE_ADDRESS;

		// Check if page is called by id (no url alias)
		if (! $res->numRows() && is_numeric($this->page_name) && $this->page_name)
		{
			$res = $pages->selectResult('all', 'id = '.$pages->quote((int)$this->page_name));
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
					if (is_readable('./blk/blk_'.$this->page_name.'.php'))
					{
						//include_once('./blk/blk_'.$active_block.'.php');
						$blk_name = 'blk_'. $this->page_name;
						$blk =& new $blk_name;
						$page->blk_content = $blk->out($page->blk_content);		
					}
				}
				break;
			default:
				$page->blk_content = $tr->t('Unsupported page type');
			}
		} else {
			//header("HTTP/1.1 404 Not Found");
			$page->title = $tr->t('Under construction');
			//$page->title = $tr->t('Page not found');
			$page->blk_content = 'Block not found: '.$this->page_name;
		}

		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($page);
	} // function output()
} // class blk_page extends block
?>
