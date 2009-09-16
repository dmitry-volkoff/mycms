<?php
/**
 * Contacts
 *
 * @author vdb
 * @version CVS: $Id$
 */
class blk_contacts extends block
{
	/**
	 * Constructor (php4)
	 */
	function blk_contacts()
	{
		$this->__construct();
	}

	/**
	 * Constructor
	 */
	function __construct()
	{
		global $default_lang, $tr;

		$this->template = get_class($this) .'.html'; 
		if (! is_readable('./tpl/'.$this->template)) 
		{ 
			$this->template = 'default.html'; 
		}	
	} // end constructor


	/**
	 * Output to browser
	 */
	function output()
	{
		global $tpl, $db, $tr, $default_lang, $p;
		$out = '';

		$this->content->site_phone = SITE_PHONE;
		if (defined('SITE_PHONE2'))
		{
			$this->content->site_phone2 = SITE_PHONE2;
		}
		if (defined('SITE_ADDRESS'))
		{
			$this->content->site_address = SITE_ADDRESS;
		}
		$addr = explode('@', SITE_EMAIL);
		$this->content->site_email = '<script language="javascript">email("'.$addr[0].'","'.$addr[1].'");</script>';
		$this->content->site_email .= '<noscript>'.$addr[0].' at '.$addr[1].'</noscript>';

		// output
		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($this->content);
	}    
}
?>
