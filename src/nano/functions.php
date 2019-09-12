<?php
/**
 * nano - a tiny server api framework
 * 
 * @author    Daniel Robin <danrobin113@github.com>
 * @version   1.4
 * 
 * last-update 09-2019
 */

function redirect($url, $statusCode = 303)
{
	die("CALLRED");
	header('Location: ' . $url, true, $statusCode);
	die();
}

if (!function_exists('view'))
{
	function view($name, $context = [], $namespace = '')
	{
		return nano\View\ViewFactory::constructFromName($name, $context, $namespace);
	}
}

if (!function_exists('json'))
{
	function json($filename)
	{
		return nano\View\Json::fromFile($filename);
	}
}

if (!function_exists('object_to_array'))
{
	function object_to_array($object)
	{
		return json_decode(json_encode($object), true);
	}
}

if (!function_exists('notice'))
{
	function notice($msg)
	{
		// TODO: don't output in production mode
		user_error($msg, E_USER_NOTICE);
	}
}

if (!function_exists('warn'))
{
	function warn($msg)
	{
		// TODO: don't output in production mode
		user_error($msg, E_USER_WARNING);
	}
}

if (!function_exists('error'))
{
	function error($msg)
	{
		// TODO: don't output in production mode
		user_error($msg, E_USER_ERROR);
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