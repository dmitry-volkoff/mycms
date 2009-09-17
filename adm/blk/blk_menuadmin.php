<?php
/*
 * Main menu block
 *
 * @package admin
 * @author vdb
 * @version CVS: $Id$
 */
class blk_menuadmin extends block
{
	function output()
	{
		global $tpl, $tr, $current_lang, $q;
		if (! $this->template) { $this->template = get_class($this) .'.html'; }
		$page = new stdClass;

		/**
		 * Define main menu
		 */
		$m = array(
			//'Main'  => DEFAULT_URL_ADMIN_PATH,
			'Left Menu' => 'menu',
			'Pages'	=> 'site_pages&type=1',
			'News'	=> 'site_pages&type=2',
			'Articles' => 'site_pages&type=3',
			'Examples' => 'examples',
		);

		$q0 = $q;
		if ($q == 'site_pages')
		{
			if(isset($_REQUEST['type'])) 
			{ 
				$q0 = 'site_pages&'. 'type=' . (int)$_REQUEST['type']; 
			} else {
				$q0 = 'site_pages&type=1'; 
			}
		}
		foreach($m as $title => $link)
		{
			//$link0 = reset(explode('&', $link));
			$page->menu[$link]['title'] = $tr->t($title);
			$page->menu[$link]['link'] = '?q=' . $link;
			$page->menu[$link]['style'] = ($q0 == $link ? 'menuactive' : 'menulink');
		}

		/** 
		 * Define main objects to manage
		 */
		$m_obj = array(
			//'Products' => 'products', 
			//'Examples' => 'examples', 
			);

		foreach($m_obj as $title => $link)
		{
			$page->menu_objects[$link]['title'] = $tr->t($title);
			$page->menu_objects[$link]['link'] = '?q=' . $link;
			$page->menu_objects[$link]['style'] = ($q == $link ? 'menuactive' : 'menulink');

			switch($link)
			{
			case 'products':
				$dao_name = 'dao_product_types';
				$query_field = 'type';
				break;
			}

			if (($link === 'products') && ($q === $link))
			{
				// create types tree submenu
				include_once('./dao/' . $dao_name . '.php');
				$dao =& new $dao_name;
				$m = $dao->get_menu_array();

				$page->menu_objects[$q]['submenu'] = '<ul class="sub">';

				// now assign menu options
				foreach($m as $id => $val)
				{
					$style = isset($_REQUEST[$query_field]) && 
						((int)$_REQUEST[$query_field] == $val['id']) ?
						'class="menuactive" ' : '';

					$page->menu_objects[$q]['submenu'] .= 
						'<li class="sub">'. 
						str_repeat('---', strlen($m[$id]['sort'])/12 - 1) .
						'<a '. $style .
						'href="?q='.$link.'&'.$query_field.'='.$val['id'].'">'. 
						$val['name'] .
						'</a></li>';
				}
				$page->menu_objects[$q]['submenu'] .= '</ul>';
			}
		}

		/** 
		 * Define dictionaries
		 */
		$m_dict = array(
			'Page Types'    => 'page_types',
			'Currency' 	=> 'currencies',
		);

		foreach($m_dict as $title => $link)
		{
			$page->menu_dict[$link]['title'] = $tr->t($title);
			$page->menu_dict[$link]['link'] = '?q=' . $link;
			$page->menu_dict[$link]['style'] = ($q == $link ? 'menuactive' : 'menulink');
		}

		/** 
		 * Define setup links
		 */
		$page->menu_setup['settings']['title'] = $tr->t('Settings');
		$page->menu_setup['settings']['link'] = '?q=settings&edit=1';
		$page->menu_setup['settings']['style'] = ($q == 'settings' ? 'menuactive' : 'menulink');

		$page->menu_setup['logout']['title'] = $tr->t('Logout');
		$page->menu_setup['logout']['link'] = '?q=logout';
		$page->menu_setup['logout']['style'] = 'menulink';

		$tpl->compile($this->template);
		return $tpl->bufferedOutputObject($page);
	}
}
?>