<?php 
/** 
 * Custom Auth loginFunction
 *
 * @author vdb
 * @version CVS: $Id$
 */

function login_form()
{
	/*
	 * Change the HTML output so that it fits to your
	 * application.
	 */
	global $tr, $tpl;

	// Page params
	$page = new stdClass;

	// Put content into the body
	$tpl->compile('login_form.html');
	return $tpl->bufferedOutputObject($page);
}

function loginFunction()
{
	/*
	 * Change the HTML output so that it fits to your
	 * application.
	 */
	global $tr;
	
	echo "<form method=\"post\" action=\"index.php\">";
	
	echo "<div>".$tr->t('Email').": <input type=\"text\" name=\"username\"></div>";
	echo "<div>".$tr->t('Password').": <input type=\"password\" name=\"password\"></div>";
	echo "<div><input type=\"submit\" value=\"".$tr->t('Enter')."\"></div>";
	echo "</form>";
}
?>