<?php
/**
 * nano - a lazy server api framework
 * 
 * @author    Daniel Robin <danrobin113@github.com>
 * @version   1.4
 * 
 * last-update 09-2019
 */

require_once __DIR__ . '/json.php';

if (!function_exists('notice'))
{
	function notice($msg)
	{
		if ($_ENV['APP_MODE'] == 'DEVELOPMENT')
			user_error($msg, E_USER_NOTICE);
	}
}

if (!function_exists('warn'))
{
	function warn($msg)
	{
		if ($_ENV['APP_MODE'] == 'DEVELOPMENT')
			user_error($msg, E_USER_WARNING);
	}
}

if (!function_exists('error'))
{
	function error($msg)
	{
		if ($_ENV['APP_MODE'] == 'DEVELOPMENT')
			user_error($msg, E_USER_ERROR);
	}
}

if (!function_exists('view'))
{
	function view($whatever, $context = [], $basepath = './')
	{
		$basepath = get_include_path() . $basepath;
		$filename = $basepath . $whatever;

		if (is_object($context))
			$context = object_to_array($context);

		if (!file_exists($filename)) {
			return new nano\View\View($whatever, $context, $basepath);
		}

		$content = file_get_contents($filename);
		
		return new nano\View\View($content, $context, $basepath);
	}
}

if (!function_exists('object_to_array'))
{
	function object_to_array($object)
	{
		return json_decode(json_encode($object), true);
	}
}

if (!function_exists('camel'))
{
	function camel($str)
	{
		return _convert($str, function($item){
				static $b = 0;
				if ($b++>0) return ucfirst(strtolower($item));
				return strtolower($item);
			}, '');
	}
}

if (!function_exists('kebab'))
{
	function kebab($str)
	{
		return _convert($str, function($item){
				return strtolower($item);
			}, '-');
	}
}

if (!function_exists('snake'))
{
	function snake($str)
	{
		return _convert($str, function($item){
				return strtolower($item);
			}, '_');
	}
}

if (!function_exists('pascal'))
{
	function pascal($str)
	{
		return _convert($str, function($item){
				return ucfirst(strtolower($item));
			});
	}
}

if (!function_exists('upper'))
{
	function upper($str)
	{
		return _convert($str, function($item){
				return strtoupper($item);
			}, '_');
	}
}

if (!function_exists('title'))
{
	function title($str)
	{
		return _convert($str, function($item){
			static $b = 0;
			if ($b++==0) return ucfirst(strtolower($item));
			if (in_array(strtolower($item), 
				["a", "an", "the", "at", "on", "in", "into", "above", "below", "and", "or", "from", "for", "of"]
			)) return strtolower($item);
			return ucfirst(strtolower($item));
		}, ' ');
	}
}


function _tokenize($str)
{
	$str = preg_replace(['/([a-z0-9])([A-Z])/','/_|-/'], ['$1 $2',' '], $str);

	if (preg_match_all('/[^\s]+/', $str, $m))
		return $m[0];

	return [$str];
}

function _convert($str, $callback, $glue = '')
{
	return join($glue, array_map($callback, _tokenize($str)));
}