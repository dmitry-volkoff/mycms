<?php
/**
 * Fast, light and safe Cache Class
 *
 * Cache_Lite is a fast, light and safe cache system. It's optimized
 * for file containers. It is fast and safe (because it uses file
 * locking and/or anti-corruption tests).
 *
 * There are some examples in the 'docs/examples' file
 * Technical choices are described in the 'docs/technical' file
 *
 * Memory Caching is from an original idea of
 * Mike BENOIT <ipso@snappymail.ca>
 *
 * Nota : A chinese documentation (thanks to RainX <china_1982@163.com>) is
 * available at :
 * http://rainx.phpmore.com/manual/cache_lite.html
 *
 * @package Cache_Lite
 * @category Caching
 * @version $Id: Lite.php,v 1.54 2009/07/07 05:34:37 tacker Exp $
 * @author Fabien MARTY <fab@php.net>
 * 
 * !!! Changed by vdb@ !!!
 */

define('CACHE_LITE_ERROR_RETURN', 1);
define('CACHE_LITE_ERROR_DIE', 8);

class Cache_Lite
{

	// --- Private properties ---

	/**
	 * Directory where to put the cache files
	 * (make sure to add a trailing slash)
	 *
	 * @var string $_cacheDir
	 */
	var $_cacheDir = '/tmp/';

	/**
	 * Enable / disable caching
	 *
	 * (can be very usefull for the debug of cached scripts)
	 *
	 * @var boolean $_caching
	 */
	var $_caching = true;

	/**
	 * Cache lifetime (in seconds)
	 *
	 * If null, the cache is valid forever.
	 *
	 * @var int $_lifeTime
	 */
	var $_lifeTime = 3600;

	/**
	 * File name (with path)
	 *
	 * @var string $_file
	 */
	var $_file;

	/**
	 * File name (without path)
	 *
	 * @var string $_fileName
	 */
	var $_fileName;

	/**
	 * Pear error mode (when raiseError is called)
	 *
	 * (see PEAR doc)
	 *
	 * @see setToDebug()
	 * @var int $_pearErrorMode
	 */
	var $_pearErrorMode = CACHE_LITE_ERROR_RETURN;

	/**
	 * Current cache id
	 *
	 * @var string $_id
	 */
	var $_id;

	/**
	 * Enable / disable automatic serialization
	 *
	 * it can be used to save directly datas which aren't strings
	 * (but it's slower)    
	 *
	 * @var boolean $_serialize
	 */
	var $_automaticSerialization = false;

	/**
	 * Disable / Tune the automatic cleaning process
	 *
	 * The automatic cleaning process destroy too old (for the given life time)
	 * cache files when a new cache file is written.
	 * 0               => no automatic cache cleaning
	 * 1               => systematic cache cleaning
	 * x (integer) > 1 => automatic cleaning randomly 1 times on x cache write
	 *
	 * @var int $_automaticCleaning
	 */
	var $_automaticCleaningFactor = 0;

	/**
	 * Nested directory level
	 *
	 * Set the hashed directory structure level. 0 means "no hashed directory 
	 * structure", 1 means "one level of directory", 2 means "two levels"... 
	 * This option can speed up Cache_Lite only when you have many thousands of 
	 * cache file. Only specific benchs can help you to choose the perfect value 
	 * for you. Maybe, 1 or 2 is a good start.
	 *
	 * @var int $_hashedDirectoryLevel
	 */
	var $_hashedDirectoryLevel = 0;

	/**
	 * Umask for hashed directory structure
	 *
	 * @var int $_hashedDirectoryUmask
	 */
	var $_hashedDirectoryUmask = 0700;

	// --- Public methods ---

	/**
	 * Constructor
	 *
	 * $options is an assoc. Available options are :
	 * $options = array(
	 *     'cacheDir' => directory where to put the cache files (string),
	 *     'caching' => enable / disable caching (boolean),
	 *     'lifeTime' => cache lifetime in seconds (int),
	 *     'pearErrorMode' => pear error mode (when raiseError is called) (cf PEAR doc) (int),
	 *     'automaticSerialization' => enable / disable automatic serialization (boolean),
	 *     'automaticCleaningFactor' => distable / tune automatic cleaning process (int),
	 *     'hashedDirectoryLevel' => level of the hashed directory system (int),
	 *     'hashedDirectoryUmask' => umask for hashed directory structure (int),
	 * );
	 *
	 * @param array $options options
	 * @access public
	 */
	function Cache_Lite($options = array(NULL))
	{
		foreach($options as $key => $value) {
			$this->setOption($key, $value);
		}
	}

	/**
	 * Generic way to set a Cache_Lite option
	 *
	 * see Cache_Lite constructor for available options
	 *
	 * @var string $name name of the option
	 * @var mixed $value value of the option
	 * @access public
	 */
	function setOption($name, $value) 
	{
		$availableOptions = array('errorHandlingAPIBreak', 'hashedDirectoryUmask', 'hashedDirectoryLevel', 'automaticCleaningFactor', 'automaticSerialization', 'fileNameProtection', 'memoryCaching', 'onlyMemoryCaching', 'memoryCachingLimit', 'cacheDir', 'caching', 'lifeTime', 'fileLocking', 'writeControl', 'readControl', 'readControlType', 'pearErrorMode');
		if (in_array($name, $availableOptions)) {
			$property = '_'.$name;
			$this->$property = $value;
		}
	}

	/**
	 * Test if a cache is available and (if yes) return it
	 *
	 * @param string $id cache id
	 * @return string data of the cache (else : false)
	 * @access public
	 */
	function get($id)
	{
		$data = false;
		if ($this->_caching) {
			$this->_setFileName($id);
			clearstatcache();
			if (is_null($this->_lifeTime)) {
				if (file_exists($this->_file)) {
					$data = $this->_read();
				}
			} else {
				if ((file_exists($this->_file)) && (@filemtime($this->_file) > time())) {
					$data = $this->_read();
				}
			}
			if (($this->_automaticSerialization) and (is_string($data))) {
				$data = unserialize($data);
			}
			return $data;
		}
		return false;
	}

	/**
	 * Save some data in a cache file
	 *
	 * @param string $data data to put in cache (can be another type than strings if automaticSerialization is on)
	 * @param string $id cache id
	 * @return boolean true if no problem (else : false or a PEAR_Error object)
	 * @access public
	 */
	function save($data, $ttl = 0, $id = NULL)
	{
		if (! $this->_caching) 	{ return false; }

		if ($this->_automaticSerialization) { $data = serialize($data);	}
		if (isset($id)) { $this->_setFileName($id); }
		if ($this->_automaticCleaningFactor>0 && ($this->_automaticCleaningFactor==1 || mt_rand(1, $this->_automaticCleaningFactor)==1)) 
		{
			$this->clean(false, 'old');			
		}

		if ($this->_hashedDirectoryLevel > 0) {
			$hash = md5($this->_fileName);
			$root = $this->_cacheDir;
			for ($i=0 ; $i<$this->_hashedDirectoryLevel ; $i++) 
			{
				$root = $root . 'cache_' . substr($hash, 0, $i + 1) . '/';
				if (!(@is_dir($root))) 
				{
					@mkdir($root, $this->_hashedDirectoryUmask);
				}
			}
		}
		// simple lock_file/semaphore strategy
		$tmp_file = $this->_file . '.tmp';
		if (file_exists($tmp_file)) 
		{
			if ((filemtime($tmp_file) + 30) > time()) 
			{
				// File locked within 30 sec by another process. Just go away. 
				;	
			} else {
				// Something is wrong. Staled file.
				@unlink($tmp_file);
			}
			return false;
		} else {
			$fp = @fopen($tmp_file, "wb");
			if (! $fp) 
			{
				return $this->raiseError('Cache_Lite : Unable to write cache file : '.$this->_file, -1);
			} 

			$mqr = get_magic_quotes_runtime();
			if ($mqr) { set_magic_quotes_runtime(0); }
			@fwrite($fp, $data);
			if ($mqr) { set_magic_quotes_runtime($mqr); }
			@fclose($fp) && @rename($tmp_file, $this->_file) && $this->set_ttl($ttl);
			//@touch($this->_file, time() + abs($this->_lifeTime));
			return true;
		}
	} // function save($data, $ttl = 0, $id = NULL)

	/**
	 * Remove a cache file
	 *
	 * @param string $id cache id
	 * @param boolean $checkbeforeunlink check if file exists before removing it
	 * @return boolean true if no problem
	 * @access public
	 */
	function remove($id)
	{
		$this->_setFileName($id);
		return $this->_unlink($this->_file);
	}

	/**
	 * Clean the cache
	 *
	 * @param string $mode flush cache mode : 'old', 'callback_myFunction'
	 * @return boolean true if no problem
	 * @access public
	 */
	function clean($mode = 'old')
	{
		return $this->_cleanDir($this->_cacheDir, $mode);
	}

	/**
	 * Set to debug mode
	 *
	 * When an error is found, the script will stop and the message will be displayed
	 * (in debug mode only). 
	 *
	 * @access public
	 */
	function setToDebug()
	{
		$this->setOption('pearErrorMode', CACHE_LITE_ERROR_DIE);
	}

	/**
	 * Set a new life time
	 *
	 * @param int $newLifeTime new life time (in seconds)
	 * @access public
	 */
	function setLifeTime($newLifeTime)
	{
		$this->_lifeTime = $newLifeTime;
	}

	/**
	 * Return the cache last modification time
	 *
	 * BE CAREFUL : THIS METHOD IS FOR HACKING ONLY !
	 *
	 * @return int last modification time
	 */
	function lastModified() 
	{
		return @filemtime($this->_file);
	}

	/**
	 * Trigger a PEAR error
	 *
	 * To improve performances, the PEAR.php file is included dynamically.
	 * The file is so included only when an error is triggered. So, in most
	 * cases, the file isn't included and perfs are much better.
	 *
	 * @param string $msg error message
	 * @param int $code error code
	 * @access public
	 */
	function raiseError($msg, $code)
	{
		include_once('PEAR.php');
		return PEAR::raiseError($msg, $code, $this->_pearErrorMode);
	}

	/**
	 * Extend the life of a valid cache file
	 * 
	 * see http://pear.php.net/bugs/bug.php?id=6681
	 * 
	 * @access public
	 */
	function set_ttl($ttl = 0)
	{
		$ttl = (int) $ttl;
		if (! $ttl) { $ttl = $this->_lifeTime; }
		@touch($this->_file, time() + $ttl);
	}

	// --- Private methods ---

	/**
	 * Remove a file
	 * 
	 * @param string $file complete file path and name
	 * @return boolean true if no problem
	 * @access private
	 */
	function _unlink($file)
	{
		if (!@unlink($file)) {
			return $this->raiseError('Cache_Lite : Unable to remove cache !', -3);
		}
		return true;        
	}

	/**
	 * Recursive function for cleaning cache file in the given directory
	 *
	 * @param string $dir directory complete path (with a trailing slash)
	 * @param string $mode flush cache mode : 'old', 'callback_myFunction'
	 * @return boolean true if no problem
	 * @access private
	 */
	function _cleanDir($dir, $mode = 'old')
	{
		$motif = 'cache_';
		if (!($dh = opendir($dir))) {
			return $this->raiseError('Cache_Lite : Unable to open cache directory !', -4);
		}
		$result = true;
		while ($file = readdir($dh)) 
		{
			if (($file === '.') || ($file === '..')) { continue; }

			if (substr($file, 0, 6) !== 'cache_') { continue; }
			$file2 = $dir . $file;
			if (! is_file($file2)) { continue; }
			switch (substr($mode, 0, 9)) {
			case 'old':
				// files older than lifeTime get deleted from cache
				if (!is_null($this->_lifeTime)) {
					if (@filemtime($file2) < time()) {
						$result = ($result and ($this->_unlink($file2)));
					}
				}
				break;
			case 'callback_':
				$func = substr($mode, 9, strlen($mode) - 9);
				if ($func($file2)) {
					$result = ($result and ($this->_unlink($file2)));
				}
				break;
			}
			if ((is_dir($file2)) and ($this->_hashedDirectoryLevel>0)) {
				$result = ($result and ($this->_cleanDir($file2 . '/', $mode)));
			}
		}
		return $result;
	}

	/**
	 * Make a file name (with path)
	 *
	 * @param string $id cache id
	 * @access private
	 */
	function _setFileName($id)
	{
		$suffix = 'cache_'. md5($id);
		$root = $this->_cacheDir;
		if ($this->_hashedDirectoryLevel>0) {
			$hash = md5($suffix);
			for ($i=0 ; $i<$this->_hashedDirectoryLevel ; $i++) {
				$root = $root . 'cache_' . substr($hash, 0, $i + 1) . '/';
			}   
		}
		$this->_id = $id;
		$this->_fileName = $suffix;
		$this->_file = $root.$suffix;
	}

	/**
	 * Read the cache file and return the content
	 *
	 * @return string content of the cache file (else : false or a PEAR_Error object)
	 * @access private
	 */
	function _read()
	{
		$fp = @fopen($this->_file, "rb");
		if (! $fp) 
		{
			return $this->raiseError('Cache_Lite : Unable to read cache !', -2); 
		}
		clearstatcache();
		$length = @filesize($this->_file);
		$mqr = get_magic_quotes_runtime();
		if ($mqr) {
			set_magic_quotes_runtime(0);
		}
		if ($length) {
			$data = @fread($fp, $length);
		} else {
			$data = '';
		}
		if ($mqr) {
			set_magic_quotes_runtime($mqr);
		}
		@fclose($fp);
		return $data;
	}

	/**
	 * Start the cache
	 *
	 * @param string $id cache id
	 * @return boolean true if the cache is hit (false else)
	 * @access public
	 */
	function start($id)
	{
		$data = $this->get($id);
		if ($data !== false) {
			echo($data);
			return true;
		}
		ob_start();
		ob_implicit_flush(false);
		return false;
	}

	/**
	 * Stop the cache
	 *
	 * @access public
	 */
	function end($ttl = 0)
	{
		$data = ob_get_contents();
		ob_end_clean();
		$this->save($data, $ttl);
		echo($data);
	}
}
?>
