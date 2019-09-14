<?php
/**
 * nano - a tiny server api framework
 * 
 * @author    Daniel Robin <danrobin113@github.com>
 * @version   1.4
 * 
 * last-update 09-2019
 */

set_error_handler(function ($code, $str, $file, $line)
{
	switch ($code)
	{
		case E_USER_NOTICE: 	echo "<b>Note:</b>"; break;
		case E_USER_WARNING: 	echo "<b>Warning:</b>"; break;
		case E_USER_ERROR: 		echo "<b>Error:</b>"; break;
	}

	echo " $str in $file ($line)\r\n";

	return true;
}, E_USER_NOTICE | E_USER_WARNING | E_USER_ERROR);

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

if (!function_exists('redirect'))
{
	function redirect($url, $statusCode = 303)
	{
		if (!headers_sent()) {
			header('Location: ' . $url, true, $statusCode);
			die();
		}

		echo "<script>window.location.replace('$url')</script>";
		exit();
	}
}

if (!function_exists('view'))
{
	function view($content, $context = [])
	{
		if (file_exists(get_include_path() . '/' . $content))
			$content = file_get_contents($content, true);

		return new nano\View\View($content, $context);
	}
}

if (!function_exists('json'))
{
	function json($input)
	{
		if (is_string($input) && file_exists($input))
			return nano\View\Json::fromFile($input);

		return new nano\View\Json($input);
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