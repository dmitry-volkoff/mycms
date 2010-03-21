<?php 
/**
 * Name
 *
 * @package name
 * @author vdb
 */
//error_reporting(E_ALL);
//ini_set("display_errors", true);

class rssnews extends block
{
	// source rss
	public $rss_filename_remote = '';
	
	// file to put the rss content
	public $rss_filename = './rss.xml';
	public $rss_filename_tmp = './rss.xml.tmp';
	
	// flag whether to download new copy
	public $do_download = false;
	
	// output charset
	public $charset_convert_to = 'CP1251//IGNORE';
	//public $charset_convert_to = 'KOI8-R//TRANSLIT';

	public function __construct()
	{
		$this->charset_convert_to = CHARSET .'//IGNORE';
		//$this->cache = true;

		$this->template = get_class($this) .'.html'; 
		if (! is_readable('./tpl/'.$this->template)) 
		{ 
			$this->template = 'blk_itemslist.html'; 
		}

	}

	public function output()
	{	
		global $tpl;
		/** custom cache implementation with items rotation*/
		if (! file_exists($this->rss_filename))
		{
			$this->do_download = true;
		} else {
			//$f_age_max = 1; 
			$f_age_max = 60*60*24*7; 
			$f_date = @filectime($this->rss_filename);
			//echo (int)($f_date + $f_age_max)."<br>\n";
			//echo (int)time()."<br>\n";
			if (($f_date + $f_age_max) < time())
			{
				$this->do_download = true;
			}
		}

		$allow_url_fopen = ini_get("allow_url_fopen");
		if (($this->do_download === true) && 
			(($allow_url_fopen == true) || (strtolower($allow_url_fopen) == 'on')))
		{
			//echo 'Downloading new copy'."<br>\n";
			$this->download_rss();
		} // if ($do_download === true)

		include("XML/RSS.php");
		$r =& new XML_RSS($this->rss_filename);

		$r->parse();
		
		$this->_tpl_assign_rows($r);
		//return var_dump($this->content->rows);
		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($this->content);
	} // function output()

	protected function _tpl_assign_rows(&$r)
	{
		$i = 0;
		$count = 0;
		$random_news_id_1 = mt_rand(1, 5);
		$random_news_id_2 = mt_rand(6, 10);

		foreach ($r->getItems() as $value) 
		{
			$i++;
			if ($i > 10) { break; }
			if (! (($i == $random_news_id_1) || ($i == $random_news_id_2))) { continue; }
			$this->content->rows[$i]['title'] = $this->charset_convert(trim($value['title']));
			//$this->content->rows[$i]['description'] = $this->charset_convert(trim($value['description']));
			$this->content->rows[$i]['link'] = trim($value['link']);
			$count++;
			if ($count == 2) { break; }
		}
	}

	protected function charset_convert($str)
	{
		if (CHARSET == "UTF-8") { return $str; }
		return @iconv("UTF-8", $this->charset_convert_to, $str);
	}

	protected function download_rss()
	{
		$this->download_rss_fopen();
	}

	protected function download_rss_fopen()
	{
		/** Download using fopen  */
		
		$rss_fh = @fopen($this->rss_filename_tmp, 'wb'); 
		if ($rss_fh === false) { return ''; }

		$rss_fh_remote = @fopen($this->rss_filename_remote, 'rb'); 
		if ($rss_fh_remote === false) { return ''; }

		stream_set_timeout($rss_fh_remote, 1);
	
		while (! @feof($rss_fh_remote)) 
		{
			$str = @fread($rss_fh_remote, 4096);
			@fwrite($rss_fh, $str);
		}

		@fclose($rss_fh_remote);
		@fclose($rss_fh);
	
		@copy($this->rss_filename, $this->rss_filename .'.old'); //{ return 'Cannot move file\n'; }
		@rename($this->rss_filename_tmp, $this->rss_filename);
	} // protected function download_rss_fopen()

	protected function download_rss_curl()
	{
		/** Download using Curl  */

		$rss_fh = @fopen($this->rss_filename_tmp, 'wb'); 
		if ($rss_fh === false) { return ''; }

		// create a new cURL resource
		$ch = curl_init($rss_filename_remote);

		// set URL and other appropriate options
		//curl_setopt($ch, CURLOPT_URL, "http://www.example.com/");
		//curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1) ;
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_FILE, $rss_fh);
	
		// grab URL and pass it to the browser
		$curl_exec($ch);

		// close cURL resource, and free up system resources
		curl_close($ch);

		@fclose($rss_fh);
	
		@copy($this->rss_filename, $this->rss_filename .'.old'); //{ return 'Cannot move file\n'; }
		@rename($this->rss_filename_tmp, $this->rss_filename);
	} // protected function download_rss_curl()
} // class rssnews extends block
?>