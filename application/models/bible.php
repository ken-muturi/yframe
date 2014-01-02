<?php

class bible extends DAO {
	
    public $pk = "id"; 
    
    public function __construct($id = '*', $version = 'nlt')
    {
        parent::__construct($version, $id);        
    }

}