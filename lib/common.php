<?php
/*
 * Common functions
 *
 * @author vdb
 * @version CVS: $Id$
 */
class common
{
	/**
	 * Make normalized url path.
	 *
	 * @param $path string raw path to be transformed
	 * @param $query_string string CGI QUERY_STRING
	 * @return string url path
	 */
	function &get_url_path($path = '', $query_string = '')
	{
		$p = '';
		//if ($path && ($path !== DEFAULT_URL_PATH))
		if ($path)
		{
			if ($path == DEFAULT_URL_PATH)
			{
				$p = 'http://'.BASE_HREF;
			} else {
				//$p = '?q='.$path;
				$p = $path;
			}
		}
		if ($query_string)
		{
			//$p .= '&' . $query_string;
			$p .= '?' . $query_string;
		}
		return $p;
	}

	/**
	 * Make normalized current module name from url path.
	 *
	 * @return string current module name
	 */
	function &get_module_name()
	{
		static $module_name;

		//echo 'req: '.$_SERVER['REQUEST_URI'] .'<br />';
		if (empty($module_name))
		{
			$path_info = common::get_path_info();

			$q = '';
			if (! empty($path_info)) 
			{
				$q = str_replace('.', ':', $path_info);
			} else {
				if ($q === '')
				{		
					$q = DEFAULT_URL_PATH;
				}
			}

			$module_name = reset(explode('/', $q));

		}
		//echo 'mod: '. $module_name .'<br />';
		return $module_name;
	}


	/**
	 * Get path info.
	 *
	 * @return string current path info
	 */
	function &get_path_info()
	{
		static $path_info, $doc_root;

		if (! isset($doc_root))
		{
			// sometimes $_SERVER['DOCUMENT_ROOT'] is not available, so...
			if (! isset($_SERVER['DOCUMENT_ROOT'])) 
			{
				$script_name = end(explode('/', $_SERVER['SCRIPT_FILENAME']));
				$_SERVER['SCRIPT_NAME'] = $script_name;

				echo 'script_name:' .$script_name .'<br />';
				//error_log('script_name_s:' .$_SERVER['SCRIPT_NAME']  .'<br />');
				//error_log('script_fname_s:' .$_SERVER['SCRIPT_FILENAME']  .'<br />');
				$_SERVER['DOCUMENT_ROOT'] = $doc_root = 
					realpath(
						reset(
							explode($_SERVER['SCRIPT_NAME'], 
							$_SERVER['SCRIPT_FILENAME'])
						)
					);
			}
			$doc_root = $_SERVER['DOCUMENT_ROOT'];
		}

		if (! isset($path_info))
		{
			//echo 'docroot: '. $_SERVER['DOCUMENT_ROOT'] .'<br />';
			//echo 'query_str: '. $_SERVER['QUERY_STRING'] .'<br />';
			//echo 'script_name_s:' .realpath($_SERVER['SCRIPT_NAME'])  .'<br />';
			//echo 'script_fname_s:' .realpath($_SERVER['SCRIPT_FILENAME'])  .'<br />';
			//echo 'php_self: '. $_SERVER['PHP_SELF'] .'<br />';
			//echo 'req_uri: '. $_SERVER['REQUEST_URI'] .'<br />';

			$path_info = $_SERVER['REQUEST_URI'];
			//echo 'path: '. $path_info .'<br />';

			// remove QUERY_STRING from path
			$path_info = reset(explode('?', $path_info));

			// remove 'index.php' part
			$path_info = trim(end(explode('index.php', $path_info)), '/');
			//echo 'path2: '. $path_info .'<br />';

			// little heuristics: check if we have 1st level path
			// remove local part if site docroot is not top level path
			$path_arr = explode('/', $path_info);
			$end_doc_root = end(explode('/', $doc_root));
			$path = current($path_arr);
			while($path !== false)
			{
				if ($path=== $end_doc_root) {break;}
				$path = next($path_arr);
			}
			if ($path)
			{
				$path_info = trim(end(explode($path, $path_info)), '/');
			}
			//echo 'path3: '. $path_info .'<br />';
		} // if (! isset($path_info))
		//echo 'path4: '. $path_info .'<br />';

		if ($path_info === '')
		{		
			$path_info = DEFAULT_URL_PATH;
		}

		return $path_info;
	} // function &get_path_info()

	/**
	 * Return a component of the current url-path.
	 *
	 * When viewing a page at the path "admin/node/configure", for example, arg(0)
	 * would return "admin", arg(1) would return "node", and arg(2) would return
	 * "configure".
	 *
	 * @param $index
	 *   The index of the component, where each component is separated by a '/'
	 *   (forward-slash), and where the first component has an index of 0 (zero).
	 *
	 * @return
	 *   The component specified by $index, or empty string if the specified component was
	 *   not found.
	 */
	function &arg($index = 0) 
	{
		static $arguments, $q;
		$ret = '';

		if (! isset($q)) { $q = common::get_path_info(); }
		if (! isset($arguments)) 
		{
			$arguments = explode('/', $q);
		}

		if (isset($arguments[$index])) 
		{
			$ret = $arguments[$index];
		}

		return $ret;
	}

	/**
	 * Return a first N components of the current url-path.
	 *
	 * When viewing a page at the path "admin/node/configure", for example, narg(2)
	 * would return "admin/node", narg(1) would return "admin" like arg(0)
	 *
	 * @param $n
	 *   Number of first components to return, where each component is separated by a '/'
	 *   (forward-slash).
	 *
	 * @return
	 *   First $n components of the current url-path, or empty string if no components was found.
	 *
	 */
	function &narg($n = 1) 
	{
		static $arguments, $q;
		$a = array(); $ret = '';

		if ($n <= 0) { $n = 1; }
		if (! isset($q)) { $q = common::get_path_info(); }
		if (! isset($arguments)) 
		{
			$arguments = explode('/', $q);
		}


		for($i = 0; $i < $n; $i++)
		{
			if (isset($arguments[$i])) 
			{
				$a[$i] = $arguments[$i];
			}
		}

		$ret = implode('/', $a);
		return $ret;
	}

	/**
	 * Return a number of arguments in the current url-path.
	 *
	 * When viewing a page at the path "admin/node/configure", for example, 
	 * arg_number() would return 3.
	 *
	 * @return
	 *   The number of arguments in the current url-path
	 */
	function &arg_num() 
	{
		static $num, $q;

		if (! isset($q)) { $q = common::get_path_info(); }
		if (! isset($num)) 
		{
			$num = count(explode('/', $q));
		}

		return $num;
	}

	/**
	 * Evaluate a string of PHP code.
	 *
	 * This is a wrapper around PHP's eval(). It uses output buffering to capture both
	 * returned and printed text. Unlike eval(), we require code to be surrounded by
	 * <?php ?> tags; in other words, we evaluate the code as if it were a stand-alone
	 * PHP file.
	 *
	 * Using this wrapper also ensures that the PHP code which is evaluated can not
	 * overwrite any variables in the calling code, unlike a regular eval() call.
	 *
	 * @param $code string
	 *   The code to evaluate.
	 * @return
	 *   A string containing the printed output of the code, followed by the returned
	 *   output of the code.
	 */
	function php_eval($code) 
	{
		ob_start();
		print eval('?>'. $code);
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}    
}
?>