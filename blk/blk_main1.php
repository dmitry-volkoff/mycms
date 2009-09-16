<?php
/*
 * Main admin block
 *
 * @package admin
 * @author vdb
 * @version CVS: $Id$
 */
class blk_main extends block
{
    function output()
    {
	global $tpl;
	if (! $this->template) { $this->template = get_class($this) .'.html'; }
	$page = new stdClass;
	$tpl->compile($this->template);
	return $tpl->bufferedOutputObject($page);
    }
}
?>