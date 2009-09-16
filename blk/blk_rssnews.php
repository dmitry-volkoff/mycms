<?php 
/**
 * Name
 *
 * @package name
 * @author vdb
 */
//error_reporting(E_ALL);
//ini_set("display_errors", true);

require_once('./lib/rssnews.php');
class blk_rssnews extends rssnews
{
	public function __construct()
	{
		parent::__construct();
		$this->rss_filename_remote = 'http://news.yandex.ru/Russia/auto.rss';
		$this->cache = true;
	}

	protected function _tpl_assign_rows(&$r)
	{
		$i = 0;
		$count = 0;
		$random_news_id_1 = mt_rand(1, 5);
		$random_news_id_2 = mt_rand(6, 10);
		//$random_news_id_3 = mt_rand(7, 10);

		foreach ($r->getItems() as $value) 
		{
			$i++;
			if ($i > 10) { break; }
			if (! 
				(($i == $random_news_id_1) || 
				($i == $random_news_id_2))
			) { continue; }
				
			$this->content->rows[$i]['title'] = $this->charset_convert(trim($value['title']));
			//$this->content->rows[$i]['description'] = $this->charset_convert(trim($value['description']));
			$link = trim($value['link']);
			// remove yandex redirection part of url
			$link = str_replace('http://news.yandex.ru/yandsearch?cl4url=','', $link);
			$link = str_replace('&country=Russia&cat=99','', $link);
			//$link = str_replace('&amp;country=Russia&amp;cat=99','', $link);
			$link = 'http://'. urldecode($link);
			//$this->content->rows[$i]['link'] = trim($value['link']);
			$this->content->rows[$i]['link'] = $link;
			$count++;
			if ($count == 3) { break; }
		}
	}
} // class blk_rssnews extends block
?>