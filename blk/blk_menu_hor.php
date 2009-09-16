<?php
/**
 * Site menu.
 *
 * @author vdb
 * @version CVS: $Id$
 */

class blk_menu_hor extends blk_menu
{
	function __construct($active_id = 0, $filter)
	{
		parent::__construct($active_id, $filter);
		$this->dao_name = 'dao_menu';
	}
} // class blk_menu extends block
?>