<?php
/**
 * Site menu.
 *
 * @author vdb
 * @version CVS: $Id$
 */

class blk_menu extends block
{
	var $active_link = ''; // active menu link
	var $active_id = 0; // active menu id 
	var $dao_name = ''; // menu db_table 
	var $filter = array(); // array of elements' id's to output
	var $menu_all_open = false; // flag to open all child submenus or only active
    
	function __construct()
	{
		$this->cache = false;
		$this->active_id = $active_id;
		$this->dao_name = 'dao_' . substr(get_class($this), 4);
		$this->menu_all_open = false;
	}
	
	function init($active_id = 0, $filter)
	{
		$this->active_id = $active_id;
		if (isset($filter))
		{
			$this->filter = $filter;
		}
	}
    
	function output()
	{
		global $tpl, $q;
		if (! $this->template) { $this->template = get_class($this) .'.html'; }
		if (! $this->active_link) { $this->active_link = $q; } // default to current path
		//if (! $this->active_link) { $this->active_link =& common::get_path_info(); } // default to current path

		if (! is_readable('./tpl/'.$this->template)) 
		{ 
			$this->template = 'blk_menu.html'; 
		}

		$page = new stdClass;
	
		//$dao_name = 'dao_' . substr(get_class($this), 4);
		require_once("./dao/dao_menu.php");
	
		//if (substr(get_class($this), 4) !== 'menu')
		if ($this->dao_name !== 'dao_menu')
		{
			eval('class ' . $this->dao_name .' extends dao_menu {}; ');
		}

		$menu = new $this->dao_name();
		$menu->sql['parent_join']['where'] = '(hide IS NULL OR ! hide)'; 
		
		$res = $menu->selectResult('all', 'link = ' . $menu->quote($this->active_link)); 
	
		$q1 = ''; $arg_num =& common::arg_num();
		if ((! $res->numRows()) && ($arg_num != 0))
		{
			for($i = $arg_num - 1; $i >= 0; $i--) 
			{
				$q1 = common::narg($i);
				$this->active_link = $q1; 
				//echo 'menu active_link: '.$q1.'<br />';
				//echo 'arg_num:'.$i.'<br />';
				//echo 'narg:'.common::narg($i).'<br />';
				$res = $menu->selectResult('all', 'link = '.
					$menu->quote($q1));
			
				if ($res->numRows())
				{
					$this->active_link = $q1; 
					//echo 'active_link2: '.$q1.'<br />';
					break;
				}
			}
		}

		// get a sorted array with menu items
		$m = $menu->get_menu_array();
		/*
		echo '<pre>';
		print_r($m);
		echo '</pre>';
		*/
		// Find out active menu item; if we have active childs, then assign parent as active. 
		$active_parent = 0;
		foreach($m as $key => $val)
		{
			$active = ($val['link'] ==  $this->active_link) || ($val['id'] == $this->active_id);
			//if ($this->active_link == 'productslist/7/1') {echo "val['link']: ".$val['link'].'//'.'active_link: '.$this->active_link.'// id:'.$val['id'].'/'.$this->active_id.'<br />';}
			if ($active)
			{
				//echo "val['link']: ".$val['link'].'//'.'active_link: '.$this->active_link.'// id:'.$val['id'].'/'.$this->active_id.'<br />';
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
		
		$i = 0;
		$pos = 1;
		$child_pos = 1;

		foreach($m as $key => $val)
		{
			//if (! in_array($val['id'], $this->filter)) { echo $val['id'] .' '.$val['name']; continue; }
			if (!empty($this->filter) && ! in_array($val['id'], $this->filter)) { continue; }
			//echo $val['id'] .' '.$val['name'] .'<br>';
			$i = $val['id'];
			if ($val['parent_id'])
			{
			
				if ($val['parent_id'] && 
					($this->menu_all_open || ($val['parent_id'] == $active_parent)))
				{
				/*
				if ($val['parent_id'] == $active_parent)
				{
					if(! isset($page->items[$active_parent]['childs'])) 
					{ $page->items[$active_parent]['childs'][$i]['index_first'] = true; }
					$page->items[$active_parent]['childs'][$i]['name'] = $val['name'];
					$page->items[$active_parent]['childs'][$i]['link'] =& common::get_url_path($val['link']);
					$page->items[$active_parent]['childs'][$i]['odd_position'] = (fmod($child_pos,2) ? ' odd' : '');
					$child_pos++;
				}
				*/

					if(! isset($page->items[$val['parent_id']]['childs'])) 
					{ $page->items[$val['parent_id']]['childs'][$i]['index_first'] = true; }
					$page->items[$val['parent_id']]['childs'][$i]['name'] = $val['name'];
					$page->items[$val['parent_id']]['childs'][$i]['link'] =& common::get_url_path($val['link']);
					$page->items[$val['parent_id']]['childs'][$i]['odd_position'] = (fmod($child_pos,2) ? ' odd' : '');
					$child_pos++;
				}
				continue;
			}
			$active = ($val['id'] ==  $active_parent);
			$page->items[$i]['name'] = $val['name'];
			$page->items[$i]['link'] =& common::get_url_path($val['link']);
			$page->items[$i]['active'] = $active;
			if ($active) { $active_parent = $val['id']; }
	    
			$page->items[$i]['index_first'] = ($pos == 1);
			//$odd_position = (fmod($pos,2) ? ' odd' : ' odd');
			$page->items[$i]['odd_position'] = (fmod($pos,2) ? ' odd' : '');
			//$page->items[$i]['odd_position'] = $odd_position;
			$pos++;
			$child_pos = 1;
		}
		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($page);
	} // function output()
} // class blk_menu extends block
?>