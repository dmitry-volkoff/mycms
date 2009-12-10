<?php
/**
 * Site menu (jquery).
 *
 * @author vdb
 * @version CVS: $Id$
 */

class blk_menu_horj extends blk_menu
{
	function __construct($active_id = 0, $filter)
	{
		parent::__construct($active_id, $filter);
		$this->dao_name = 'dao_menu';
		$this->menu_all_open = true;
	}
} // class blk_menu extends block
?>