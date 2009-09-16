<?php
/*
 * html block base class.
 *
 * @package common
 * @author vdb
 * @version CVS: $Id$
 */
class block
{
	/**
	 * Main template
	 */
	var $template = '';

	/**
	 * Error string
	 */
	var $error = '';

	/**
	 * Message string
	 */
	var $message = '';

	/**
	 * DB_Table object
	 */
	var $dao = '';

	/**
	 * Content object we pass to template engine
	 *
	 * @param $static_html_part
	 *    user supplied static html to include into our template under section $static_html
	 */
	var $content = null;

	/**
	 * Enable/disable caching output
	 */
	var $cache = false;

	function init()
	{
		;
	}

	function out($static_html_part = '')
	{
		global $cache_options;
		//require_once('Cache/Lite.php'); 

		$blk_cache = new Cache_Lite(array_merge($cache_options, array('caching' => $this->cache)));
		$data = '';
		if (! $data = $blk_cache->get(get_class($this))) 
		{
			$this->content = new stdClass;
			$this->content->static_html =& $static_html_part;
			$data = $this->output();
			$blk_cache->save($data);
		}
		return $data;
	} // function out($static_html_part = '')
} // class block
?>