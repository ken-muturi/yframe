<?php
/**
 * The YFrame Request handler
 */
class Request {
	
	private $_controller;
	private $_method;
	private $_args;
	private $_query_string;

	public function __construct()
	{
		$urlArray = url::params(REQUEST_URI);
	    foreach (url::$params as $k => $r) 
	    {
	        if ($r == "") 
	        {
	            unset($urlArray[$k]);
	        }
	    }
	    
	    if (! count($urlArray)) 
	    {
	        $urlArray[0] = "welcome";
	        $urlArray[1] = "index";            
	    }     

	    if (count($urlArray) < 2) 
	    {
	        $urlArray[1] = "index";
	    }
	    
		$controller = $urlArray[0];
		array_shift($urlArray);
		$action = $urlArray[0];
		array_shift($urlArray);
		$queryString = array_map('urldecode', $urlArray);

		$this->_controller = $controller;
		$this->_method = $action;
		$this->_args = (isset($urlArray[0])) ? $urlArray : array();
		$this->_query_string = $queryString;
	}

	public function controller()
	{
		return $this->_controller;
	}

	public function method()
	{
		return $this->_method;
	}
	
	public function args()
	{
		return $this->_args;
	}	

	public function querystring()
	{
		return $this->_query_string;
	}
}
