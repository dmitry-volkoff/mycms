<?php
/*
 * datagrid helper class to print links 
 *
 * @package common
 * @author vdb
 * @version CVS: $Id$
 */

include_once('PHP/Compat/Function/http_build_query.php');

class dg_printer
{
	function printUp($params)
	{
		extract($params);
		$id = $record['id'];

		if (! isset($_SERVER['QUERY_STRING'])) { $_SERVER['QUERY_STRING'] = ''; }
		parse_str($_SERVER['QUERY_STRING'], $query);
		//unset($query['action']);
		if (isset($q))
		{
			$query['q'] = $q;
		}
		$query['action'] = 'up';
		$query['id'] = urlencode($id);	
		$query_new = php_compat_http_build_query($query);
		return "<a style=\"text-decoration:none;\" title=\"Up\" href=\"?$query_new\">$label</a>";
	}

	function printDown($params)
	{
		extract($params);
		$id = $record['id'];

		if (! isset($_SERVER['QUERY_STRING'])) { $_SERVER['QUERY_STRING'] = ''; }
		parse_str($_SERVER['QUERY_STRING'], $query);

		if (isset($q))
		{
			$query['q'] = $q;
		}
		$query['action'] = 'down';
		$query['id'] = urlencode($id);	
		$query_new = php_compat_http_build_query($query);
		return "<a style=\"text-decoration:none;\" title=\"Down\" href=\"?$query_new\">$label</a>";
	}

	function printEdit($params)
	{
		extract($params);

		if (! isset($id_field)) { $id_field = 'id'; }
		$id = $record[$id_field];
		
		if (! isset($_SERVER['QUERY_STRING'])) { $_SERVER['QUERY_STRING'] = ''; }
		parse_str($_SERVER['QUERY_STRING'], $query);
		$query['action'] = 'edit';
		$query[$id_field] = $id;
		$query_new = php_compat_http_build_query($query);
		return "<a href=\"?$query_new\">$label</a>";
	}

	function printDelete($params)
	{
		global $tr;
		extract($params);

		if (! isset($id_field)) { $id_field = 'id'; }
		$id = $record[$id_field];

		if (! isset($_SERVER['QUERY_STRING'])) { $_SERVER['QUERY_STRING'] = ''; }
		parse_str($_SERVER['QUERY_STRING'], $query);
		$query['action'] = 'delete';
		$query[$id_field] = $id;
		$query_new = php_compat_http_build_query($query);
		return "<a href=\"?$query_new\" onClick=\"if(!confirm('".$tr->t('Are you really want to delete this item?')."')){return false;}\">$label</a>";
	}

	function printBool($params)
	{
		extract($params);
		$val = $record[$field] ? 'X' : '&nbsp;';

		return $val;
	}

	function printLinkDetails($params)
	{
		extract($params);
		$id = $record['id'];

		$val = $record[$field] ? '<b>'.$record[$field] .'</b>' : '&nbsp;';
		return "<a href=\"?q=details&id=${id}\">$val</a>";
	}

	function printCurrency_old($params)
	{
		extract($params);
		$val = $record[$field] ? '<font color=#cc0033><b>'.$record[$field] .'</b></font>' : '&nbsp;';

		return $val;
	}

	function printCurrency($params)
	{
		extract($params);
		//$val = $record[$field] ? '<font color=#cc0033><b>'.$record[$field] .'</b></font>' : '&nbsp;';
		$val = $record[$field] ? (int) ($record[$field] * SITE_CURRENCY_RATE) : '&nbsp;';

		return $val;
	}

	function printPhoto($params)
	{
		// pass $path and $block parameters to this function
		extract($params);
		$id = $record['id'];

		//$val = $record[$field] ? $record[$field] : '&nbsp';
		return "<img src=\"thumb/" . $block . $width .".php?${id}\" width=\"".$width."px\" alt=\"\" />";
	}

	function printConcat($params)
	{
		extract($params);

		//return $field;
		$fields = explode(' ',$field);
		$val = '';
		foreach($fields as $key => $field)
		{
			$val .= $record[$field] .' ';
		}
		return $val;
	}

	function printToBasket($params)
	{
		global $tr;
		extract($params);
		$id = $record['id'];

		if (! isset($_SERVER['QUERY_STRING'])) { $_SERVER['QUERY_STRING'] = ''; }
		parse_str($_SERVER['QUERY_STRING'], $query);
		unset($query['id']);
		$query['badd'] = $id;
		$query_new = php_compat_http_build_query($query);

		return "<a href=\"?$query_new\"><img src=\"images/basket.png\" width=\"20px\" height=\"19px\" alt=\"to basket\" title=\"".$tr->t('to basket')."\"></a>";
	}
}
?>