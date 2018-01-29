<?php
class testament extends DAO 
{	
    public $pk = "id"; 
    public function __construct($id = '*', $table = 'testaments')
    {
        parent::__construct($table, $id);        
    }

	public function __get($var) 
	{		
		return parent::__get($var);
	}
}