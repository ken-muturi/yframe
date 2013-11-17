<?php

class View {

	public $arr = array();
    public $file;

	public function __construct($file) 
    {
        $this->file = $file. '.php';
    }

	protected function get_sub_views() 
    {
        foreach ($this as $varname => $var) 
        {
            if ($var instanceof View) 
            { 
                $this->arr[$varname] = $var->get_sub_views($var); 
            } 
            else 
            {
                $this->arr[$varname] = $var;  
            }
        }
                
        extract($this->arr);

        ob_start();        
        if (file_exists(SITE_PATH.APPPATH . "views/" . $this->file)) 
        {
            include SITE_PATH.APPPATH . "views/" . $this->file;
        } 
        else 
        {
            throw new Exception("The view file " . SITE_PATH.APPPATH . "views/" . $this->file . " is not available");
        }
        
        $html = ob_get_clean(); 
        return $html;
	}

    public function render() 
    {
        echo self::get_sub_views();		    
    }

	public function get_view()
	{
		return self::get_sub_views();
	}
}
