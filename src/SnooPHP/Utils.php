<?php

namespace SnooPHP;

use SnooPHP\Http\Request;
use SnooPHP\Vue\Component;

/**
 * Set of utility methods
 * 
 * @author sneppy
 */
class Utils
{
	/**
	 * Include view
	 * 
	 * @param string	$name		view name
	 * @param array		$args		arguments to expose
	 * @param Request	$request	request if differs from current request
	 * 
	 * @return bool
	 */
	public static function view($name, array $args, Request $request = null)
	{
		$request = $request ?: Request::current();
		$fullPath = path("views")."/{$name}.php";
		if (file_exists($fullPath))
		{
			include $fullPath;
			return true;
		}

		return false;
	}

	/**
	 * Include a vue component
	 * 
	 * @param string	$name		view name
	 * @param array		$args		arguments to expose
	 * @param Request	$request	request if differs from current request
	 * 
	 * @return bool
	 */
	public static function vueComponent($name, array $args, Request $request = null)
	{
		$request = $request ?: Request::current();
		
		$component = new Component($name, $args, $request);
		if (!$component->valid()) return false;
		echo $component->parse();

		return true;
	}

	/**
	 * Merge together styles and scripts blocks after body
	 * 
	 * @param string $content a valid html document content
	 * 
	 * @return string
	 */
	public static function optimizeView($content)
	{
		// Valid html only contains one body block
		if ($pos = strpos($content, "</body>"))
		{
			$preBody	= substr($content, 0, $pos);		
			$postBody	= substr($content, $pos);
			$style		= "";
			$script		= "";
			
			if (env("merge_blocks"))
			{			
				/** @todo this only works if scripts doesn't contains other '</script>' tags */
				if (preg_match_all("~(?:<style>((?:(?!</).)*)</style>|<script>((?:(?!</sc).)*)</script>)~s", $postBody, $matches))
				{
					foreach ($matches[1] as $styleContent) $style .= $styleContent;
					foreach ($matches[2] as $scriptContent) $script .= $scriptContent;
				}
				$postBody = preg_replace("~(?:<style>|<script>).*(?:</style>|</script>)~s", "", $postBody);

				// Set merged style and script
				$content = $preBody.preg_replace("~</body>~", "</body><style>".$style."</style><script>".$script."</script>", $postBody);
			}
		}

		return $content;
	}

	/**
	 * Convert to json string
	 * 
	 * @deprecated 1.0.1
	 * 
	 * @param mixed $content content to convert
	 * 
	 * @return string
	 */
	public static function toJson($content)
	{
		return to_json($content);
	}

	/**
	 * Decode from json string
	 * 
	 * @deprecated 1.0.1
	 * 
	 * @param string	$content	content to decode
	 * @param bool		$assoc		if true return array rather than object
	 * 
	 * @return object|array
	 */
	public static function fromJson($content, $assoc = false)
	{
		return from_json($content, $assoc);
	}

	/**
	 * Get or set session errors
	 * 
	 * @param string $err error string
	 */
	public static function errors($err = null)
	{
		// Start session
		if (!isset($_SESSION)) session_start();
		if ($err)$_SESSION["errors"][] = $err;
		return isset($_SESSION["errors"]) ? $_SESSION["errors"] : [];
	}

	/**
	 * Flush all errors
	 */
	public static function flushErrors()
	{
		if (isset($_SESSION)) unset($_SESSION["errors"]);
	}

	/**
	 * Get path relative to public folder
	 * 
	 * @param string $absolutePath absolute path
	 * 
	 * @return string|bool
	 */
	public static function publicPath($absolutePath)
	{
		$publicPath = path("public");
		if (preg_match("@^{$publicPath}(.*)$@", $absolutePath, $matches))
		{
			return $matches[1];
		}

		return false;
	}

	/**
	 * Parse string as value
	 * 
	 * @param string $val string to parse
	 * 
	 * @return mixed
	 */
	public static function parseValue($val)
	{
		$val = trim($val);
		if (preg_match("/^(?:TRUE|FALSE|ON|OFF)$/i", $val))				return strcasecmp($val, "TRUE") === 0 || strcasecmp($val, "ON") === 0;
		else if (preg_match("/^[0-9]+$/", $val) && strlen($val) < 16)	return (int)$val;
		else if (preg_match("/^[0-9]*\.(?:[0-9]+f?|f)$/", $val))		return (float)$val;

		return $val;
	}

	/**
	 * Check ip address against a test ip
	 * 
	 * Ips should be in x.y.z.x/w form, where w is the mask
	 * 
	 * @param string	$ip		ip address to check
	 * @param string	$test	ip address used as test
	 * 
	 * @return bool
	 */
	public static function validateIp($ip, $test)
	{
		if (!is_string($ip) || !is_string($test)) return false;
		$testBytes = preg_split("@(?:\.|/)@", $test);
		$ipBytes = preg_split("@(?:\.|/)@", $ip);
		$test = unpack("N", pack("C*", $testBytes[0], $testBytes[1], $testBytes[2], $testBytes[3]))[1];
		$ip = unpack("N", pack("C*", $ipBytes[0], $ipBytes[1], $ipBytes[2], $ipBytes[3]))[1];
		if (isset($testBytes[4]) && $mask = (int)$testBytes[4])
		{
			$test = $test >> (32 - $mask);
			$ip = $ip >> (32 - $mask);
		}
		return $test === $ip;
	}
}