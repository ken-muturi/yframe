<?php

/**
 *  to be completed
 */
class YGErrors extends Core {
	
	public function __construct()
	{
		parent::__construct();
	}

	public function index(){}
	
	public function display($message = 'No information about the error')
	{
		util::printr($message);		
	}
	
}
