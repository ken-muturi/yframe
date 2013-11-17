<?php
/**
 * Util helper class
 */
class util {

	public static function teaser($string, $length)
	{
		return substr($string, 0, $length) . (($length < strlen($string)) ? " ..." : null);
	}
	
	public static function printr($var)
	{
		echo "<pre>" . print_r($var, 1) . "</pre>";
	}

	public static function prepare_date($format = '', $date = '')
	{
		return date($format, strtotime($date));
	}

    public static function find_stmt ($needle, $array)
    {
        foreach ($array as $key => $element) 
        {    
            if (is_array($element)) 
            {
                self::find_stmt($needle, $element);
            } 
           	else  
           	{
                if ($needle == $element->queryString) 
                {
                    if (!isset(db_conn::instance()->Qstats[$key]))
                    db_conn::instance()->Qstats[$key] = 0;
                    db_conn::instance()->Qstats[$key]++;
                    return $element;
                }
            }
        }
        return FALSE;
    }

	public static function parse_ini_file($file)
	{
		$str = file_get_contents($file);
		preg_match_all('/(([a-z0-9_-]+) *= *(.+))/i', $str, $match);
		
		return array_combine($match[2], $match[3]);
	}

	public static function conf ($item = null) 
	{
		$conf = parse_ini_file(APPPATH . 'configs/application.ini', TRUE);
		
		return ($item)  ? $conf[$item] : $conf;	
	}

    public static function escape ($str)
    {
        return htmlentities($str, ENT_QUOTES, "UTF-8");
    }

	public static function selected($field, $value, $selected_value = null)
	{
		$selected =  (isset($_REQUEST[$field])) ? ($_REQUEST[$field] == $value) : null;
		if ($selected) return "selected='selected'";
		
		if ($selected_value) 
		{
			if ($value == $selected_value) 
			{
				return "selected='selected'";
			}
		}
	}
	
    public static function formval($prop, $default_value)
    {
		if (isset($_REQUEST[$prop])) 
		{
			return  self::escape($_REQUEST[$prop]);   
		}        
        return $default_value;   
    }

    public static function remember($var)
    {
        $input = array_merge($_POST, $_GET);
        $var_val ='';        
        if (isset($input[$var])) 
        {    
            $_SESSION[$var] = $input[$var];
            $var_val = $input[$var];   
        }

        if (isset($_SESSION[$var])) 
        {
            $var_val = $_SESSION[$var];
        }
        return $var_val; 
    }

	public static function redirect($url)
	{
		header("Location:{$url}");
		exit();
	}

	public static function get_flash_data($key)
	{
		if (isset($_SESSION['flash'][$key])) 
		{
			$flash_data = $_SESSION['flash'][$key];
			unset($_SESSION['flash'][$key]);
			return $flash_data;
		}	
		return null;
	} 
	
	public static function set_flash_data($key, $data)
	{
		if (!isset($_SESSION['flash'])) $_SESSION['flash'] = array();
		$_SESSION['flash'][$key] = $data;
		return true;
	}         
}