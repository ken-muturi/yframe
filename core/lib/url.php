<?php
/**
 *  Url helper class
 */
class url
{
    public static $redirecting = false;
    public static $params = array();
    
    public static function redirect($url)
    {
        self::$redirecting = true;
        header('Location:' . $url) or die();
    }
    
    public static function current()
    {
        return REQUEST_URI;
    }    

    public static function site_url($url)
    {
        return HTTP.$url;
    }
    
    public static function referer()
    {
        return trim(str_replace(HTTP, '', $_SERVER['HTTP_REFERER']), '/');
    }    
    
    public static function params($url)
    {
        // strip out query string
		$url = preg_replace('/\?.+$/i', '', REQUEST_URI);
		self::$params = explode("/", $url);
		
		return url::$params;      	
    }
    
    public static function back()
    {
    	self::redirect(self::referer());
    }
    
}
