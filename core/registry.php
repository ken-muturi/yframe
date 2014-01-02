<?php
/**
 * YFrame Singleton pattern
 */
class Registry {

	private static $_instance;

	private $_storage;

	private function __construct(){}

	public static function instance()
	{
		if(!self::$_instance instanceof self)
		{
			self::$_instance = new Registry;
		}
		return self::$_instance;
	}

	public function __set($key,$val)
	{
		$this->_storage[$key] = $val;
	}

	public function __get($key)
	{
		if(isset($this->_storage[$key]))
		{
			return $this->_storage[$key];
		}
		return false;
	}
}

