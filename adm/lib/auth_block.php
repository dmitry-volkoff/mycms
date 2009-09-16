<?php
/*
 * html block base class.
 *
 * @author vdb
 * @version CVS: $Id$
 */
class auth_block extends block
{
	/**
	 * Constructor (old style)
	 */
	function auth_block()
	{
		$this->__construct();
	}
	
	/**
	 * Constructor
	 */
	function __construct()
	{
		if (! $this->template)
		{
			$this->template = get_class($this) .'.html'; 
		}
		if (! is_readable('./tpl/'.$this->template)) 
		{ 
			$this->template = 'blk_default.html'; 
		}
	}
	
	function out($static_html_part = '')
	{
		global $page, $auth, $title, $tr;
		
		if (! $auth->checkAuth() || ((int)$auth->getAuthData('id') !== 1)) 
		{ 
			$page->title = $tr->t('Sign in');
			$title = $title .' :: '. $page->title;
			return login_form();
		} else {
			$this->content =& new stdClass;
			$this->content->static_html =& $static_html_part;
			return $this->output(); // real output function
		}
	}
}
?>