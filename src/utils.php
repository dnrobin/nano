<?php
/**
 * nano - lazy server app framework
 *
 * @author	  Daniel Robin <daniel.robin.1@ulaval.ca>
 * @version		1.4
 *
 * last updated: 09-2019
 */

require_once __DIR__ . '/Helpers/json.php';

/**
 * error functions
 */
function notice($msg)
{
	if ($_ENV['APP_MODE'] == 'DEVELOPMENT')
		user_error($msg, E_USER_NOTICE);
}

function warn($msg)
{
	if ($_ENV['APP_MODE'] == 'DEVELOPMENT')
		user_error($msg, E_USER_WARNING);
}

function error($msg)
{
	if ($_ENV['APP_MODE'] == 'DEVELOPMENT')
		user_error($msg, E_USER_ERROR);
}

/**
 * General purpose helpers
 */
function object_to_array($object)
{
  return json_decode(json_encode($object), true);
}

/**
 * Streamline view creation
 */
function view($name, $context)
{
	$filename = get_include_path() . $name;

	if (!file_exists($filename))
		error("View file '$filename' not found");

	return nano\View::fromFile($filename, new nano\Context($context));
}

/**
 * Typography case helpers
 */
function camel($str)
{
	return _convert($str, function($item){
			static $b = 0;
			if ($b++>0) return ucfirst(strtolower($item));
			return strtolower($item);
		}, '');
}

function kebab($str)
{
	return _convert($str, function($item){
			return strtolower($item);
		}, '-');
}

function snake($str)
{
	return _convert($str, function($item){
			return strtolower($item);
		}, '_');
}

function pascal($str)
{
	return _convert($str, function($item){
			return ucfirst(strtolower($item));
		});
}

function upper($str)
{
	return _convert($str, function($item){
			return strtoupper($item);
		}, '_');
}

function title($str)
{
	return _convert($str, function($item){
		static $b = 0;
		if ($b++==0) return ucfirst(strtolower($item));
		if (in_array(strtolower($item), ["a", "an", "the", "at", "on", "in", "into", "above", "below", "and", "or", "from", "for", "of"])) return strtolower($item);
		return ucfirst(strtolower($item));
	}, ' ');
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