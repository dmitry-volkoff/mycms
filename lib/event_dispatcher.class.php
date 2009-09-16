<?php 
/**
 * Event Dispatcher class
 *
 * @author vdb
 * @date 2009-05-02
 */
class event_dispatcher
{
	public static $listeners = array();
	
	public static function add_listener($action_class_method, $observer_class_method)
	{
		self::$listeners[$action_class_method][] = $observer_class_method;
	}
	
	public static function emmit(&$obj = null)
	{
		$bt = debug_backtrace();
		$emmitter_class_method = $bt[1]['class'].$bt[1]['type'].$bt[1]['function'];
		//echo "Emmit: ". $emmitter_class_method."\n";
		
		if (isset(self::$listeners[$emmitter_class_method]))
		{
			foreach(self::$listeners[$emmitter_class_method] as $index => $l)
			{
				call_user_func_array(explode('::', $l), array(&$obj));
			}
		}
	}
} // class event_dispatcher
?>

