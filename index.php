<?php
/**
 * PHP YFRAME MVC Framework
 * By Kenneth Muturi (@muturiken)
 */
session_start();

preg_match_all("/\/([^\/]+)\//i", $_SERVER['REQUEST_URI'], $match);
define('BASE_DIR', $match[1][0] . '/');
define('REQUEST_URI', 
(BASE_DIR != '/') ? trim(str_replace(BASE_DIR , '', $_SERVER['REQUEST_URI']), '/')
            : trim($_SERVER['REQUEST_URI'], '/'));
define ('FS_PATH', str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . '/' . BASE_DIR));
define ('HTTP_PATH', 'http://' . str_replace('//', '/', $_SERVER['SERVER_NAME'] . '/' . BASE_DIR));
define ('HTTP', HTTP_PATH);

define('SITE_PATH', realpath(dirname(__FILE__)).'/');
define('CORE', 'core/');
define('APPPATH', 'application/');

define('DEVELOPMENT', TRUE);

// lazy load all necessary files
spl_autoload_register(NULL, FALSE);
spl_autoload_extensions('.php');
spl_autoload_register('autoload');

function autoload($class) 
{
	$class = strtolower($class).".php";
	if (is_readable(CORE."$class")) 
	{
		include_once(CORE."$class");
	} 	
	elseif (is_readable(CORE."db/$class")) 
	{
		include_once(CORE."db/$class");
	} 	
	elseif (is_readable(CORE."lib/$class")) 
	{
		include_once(CORE."lib/$class");
	} 	
	elseif (is_readable(APPPATH."models/$class")) 
	{
		include_once(APPPATH."models/$class");
	} 
	elseif (is_readable(APPPATH."controllers/$class")) 
	{
		include_once(APPPATH."controllers/$class");
	} 
	elseif (is_readable(APPPATH."lib/".$class)) 
	{
		include_once(APPPATH."lib/".$class);
	}
}

$conf = parse_ini_file(APPPATH . "configs/application.ini", TRUE);
$log_dir = (isset($conf['logs'])) ? $conf['logging']['dir'] : 'logs';

define('LOGFILE', FS_PATH ."/{$log_dir}/" . date("d-M-Y") . ".log");

//[admin]
define ('ADMIN_EMAIL', $conf['admin']['email']);
define ('ADMIN_EMAIL_PASSWORD', $conf['admin']['password']);

//uploads
define ('UPLOADS', $conf['images']['dir']);
define ('IMG_TMP_DIR', FS_PATH . $conf['images']['tmp_dir']);
define ('RESOLUTION', $conf['images']['resolution']);
define ('THUMBNAIL_WIDTH', $conf['images']['thumbnail_width']);
define ('DEFAULT_IMG', $conf['images']['default_img']);
define ('WATERMARK', $conf['images']['watermark']);

//session
define ('SESSION_LIFETIME', $conf['session']['session_lifetime']);

ini_set('log_errors', 'On');
ini_set('error_log', LOGFILE);
ini_set('log_level', 4);

function exception_error_handler($errno, $errstr, $errfile, $errline ) 
{
	try 
	{
		error_log( "$errno, $errstr, $errfile, $errline");
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	} 
	catch (ErrorException $e) 
	{
		error_log($e->getMessage());
        error_log($e->getTraceAsString());
	}
}
set_error_handler("exception_error_handler");

try
{
	Router::route(new Request);
}
catch(Exception $e)
{
	$errors = new YGErrors;
	$error->display($e->getMessage());
}
