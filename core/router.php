<?php
/**
 *  YFrame Router Class
 */
class Router 
{	
	public static function route(Request $request)
	{	
		$controller = $request->controller();
		$method = $request->method();
		$args = $request->args();
		$query_string = $request->querystring();

		$controller = new $controller;
		$method = (is_callable(array($controller, $method))) ? $method : 'index';	
		
		if( ! empty($args) )
		{
			call_user_func_array(array($controller,$method),$args);
		}
		else
		{	
			call_user_func(array($controller,$method));
		}		
		throw new Exception('404 - '.$request->controller().' not found');
	}
}
