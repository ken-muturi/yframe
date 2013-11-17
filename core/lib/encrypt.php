<?php if ( ! defined('FS_PATH')) exit('No direct script access allowed');

class Encrypt {
	
	public static $encryption_key = '';
	public static $initial_vector = '';

	public function __construct($key)
	{
		self::$encryption_key = $key;
	}

    public static function instance()
    {
		$conf = util::conf();

		return new self($conf['encryption']['key']);
    }

	/**
	 * [crypt_string description]
	 * @param  [type] $str [description]
	 * @return [type]      [description]
	 */
	public static function crypt($str) 
	{ 
      $td = mcrypt_module_open('rijndael-128', '', 'cbc', self::$initial_vector);
      mcrypt_generic_init($td, self::$encryption_key, self::$initial_vector);

      $encrypted = mcrypt_generic($td, $str);

      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);

      return bin2hex($encrypted);

	}

	/**
	 * [decrypt description]
	 * @param  [type] $code [description]
	 * @return [type]       [description]
	 */
    public static function decrypt($code) 
    {
    	if(is_file($code) && is_readable($code)) 
    	{
    		$contents = file_get_contents($code);
    		$code = self::_hex2bin($contents);
    	} 
    	else 
    	{
    		$code = self::_hex2bin($code);
    	}
		
		$td = mcrypt_module_open('rijndael-128', '', 'cbc', self::$initial_vector);

		mcrypt_generic_init($td, self::$encryption_key, self::$initial_vector);
		$decrypted = mdecrypt_generic($td, $code);

		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return utf8_encode(trim($decrypted));
    }

    /**
     * [_hex2bin description]
     * @param  [type] $hexdata [description]
     * @return [type]          [description]
     */
	private static function _hex2bin($hexdata) 
	{
	   $bindata = '';
	   for ($i = 0; $i < strlen($hexdata); $i += 2) 
	   {
	  	 $bindata .= chr(hexdec(substr($hexdata, $i, 2)));
	   }

	   return $bindata;
	}


}