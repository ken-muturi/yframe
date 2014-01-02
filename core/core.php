<?php

class Core {

	protected $_registry;

	public function __construct() 
	{
		$this->_registry = Registry::instance();
	}

	public function index() 
	{
		util::printr(__METHOD__);
	}

	final public function __get($key)
	{
		if($return = $this->_registry->$key)
		{
			return $return;
		}
		return false;
	}	
}

