<?php
/**
 * Product menu.
 *
 * @author vdb
 * @version CVS: $Id$
 */

class blk_menu_product extends block
{
	var $active_link = ''; // active page link
	var $active_id = 0; // active menu id 

	function output()
	{
		global $tpl, $q, $tr;
		if (! $this->template) { $this->template = get_class($this) .'.html'; }
		if (! $this->active_link) { $this->active_link = $q; } // default to current path
		$module =& common::get_module_name();

		if (($module != 'news') && ($module != 'newsitem'))
		{
			if (! $this->active_id) { $this->active_id = common::arg(1); } 
		}

		if (! is_readable('./tpl/'.$this->template)) 
		{ 
			$this->template = 'blk_menu.html'; 
		}

		$page = new stdClass;

		$dao_name = 'dao_product_types';
		require_once('./dao/'.$dao_name.'.php');

	/*
	if (substr(get_class($this), 4) !== 'menu')
	{
		eval('class ' . $dao_name .' extends dao_menu {}; ');
	}
	 */

		$menu = new $dao_name();
		if ($menu->error) 
		{
			die('dao error in '. get_class($this) .': '.$menu->error->message);
		}

		$menu->sql['parent_join']['where'] = '(hide IS NULL OR ! hide)'; 

		// get a sorted array with menu items
		$m = $menu->get_menu_array();

		// Find out active menu item; if we have active childs, then assign parent as active. 
		$active_parent = 0;

		foreach($m as $key => $val)
		{
			$active = ($val['link'] ==  $this->active_link) || ($val['id'] == $this->active_id);
			if ($active)
			{
				if($val['parent_id'])
				{
					$active_parent = $val['parent_id'];
				} else {
					$active_parent = $val['id'];
				}
				break;
			}
		}
		reset($m);

		$submenus = array
			(
				'Sell' => 'productslist',
				'Parts'=> 'partslist',
				'Repair'=> 'repairlist',
			);

		$i = 0;
		$pos = 1;
		foreach($m as $key => $val)
		{
			$i = $val['id'];
			if ($val['parent_id'])
			{
				if ($val['parent_id'] == $active_parent)
				{
					if(! isset($page->items[$active_parent]['childs'])) 
					{ $page->items[$active_parent]['childs'][$i]['index_first'] = true; }
					$page->items[$active_parent]['childs'][$i]['name'] = ucfirst($val['name']);
					$page->items[$active_parent]['childs'][$i]['link'] =& common::get_url_path($val['link']);
				}
				continue;
			}
			$active = ($val['id'] ==  $active_parent);

			/***** Special menu programming: 3 submenu in each active parent *****/
			if ($active)
			{
				$c = 1;
				$page->items[$active_parent]['childs'][$c]['index_first'] = true;

				foreach($submenus as $subname => $sublink)
				{
					$page->items[$active_parent]['childs'][$c]['name'] = $tr->t(ucfirst($subname));
					$page->items[$active_parent]['childs'][$c]['link'] =& common::get_url_path($sublink.'/'.$val['id']);
					$c++;
				}
			}
			/* end special menu */

			$page->items[$i]['name'] = ucfirst($val['name']);
			$page->items[$i]['link'] =& common::get_url_path('products/'.$val['id']);
			$page->items[$i]['active'] = $active;
			if ($active) { $active_parent = $val['id']; }

			$page->items[$i]['index_first'] = ($pos == 1);
			$pos++;
		}
	/*
	echo '<pre>';
	echo 'active_parent: '.$active_parent.'<br />';
	echo 'this->active_link: '.$this->active_link.'<br />';
	print_r($page);
	echo '</pre>';
	 */
		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($page);
	}
}
?>
