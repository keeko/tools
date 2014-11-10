<?php

namespace keeko\tools\helpers;

class NamespaceResolver {
	
	/**
	 * Returns the source path for a given namespace or null if none can be found.
	 *
	 * @param string $namespace
	 * @param array $package
	 * @return string|null
	 */
	public static function getSourcePath($namespace, $package) {
		$relativeSourcePath = null;
	
		if (isset($package['autoload'])) {
	
			// check psr-4 first
			if (isset($package['autoload']['psr-4'])) {
				$relativeSourcePath = static::getSourcePathFromPsr($namespace . '\\', $package['autoload']['psr-4']);
			}
	
			// anyway check psr-0
			else if ($relativeSourcePath === null && isset($package['autoload']['psr-0'])) {
				$relativeSourcePath = static::getSourcePathFromPsr($namespace, $package['autoload']['psr-0']);
			}
		}
		
		return $relativeSourcePath;
	}
	
	/**
	 * Returns the path for a given namespace in a psr section or null if none can be found.
	 *
	 * @param string $namespace
	 * @param array $psr
	 * @return string|null
	 */
	private static function getSourcePathFromPsr($namespace, $psr) {
		// get longest match first
		$match = '';
		foreach (array_keys($psr) as $ns) {
			if (strpos($namespace, $ns) !== false && strlen($ns) > strlen($match)) {
				$match = $ns;
			}
		}
	
		// add tail
		if ($match !== '') {
			$path = $psr[$match];
	
			$tail = str_replace($match, '', $namespace);
			$path .= '/' . str_replace('\\', '/', $tail);
	
			return str_replace('//', '/', $path);
		}
	
		return null;
	}
	
	public static function getNamespace($path, $package) {
		$namespace = null;
		
		if (isset($package['autoload'])) {
		
			// check psr-4 first
			if (isset($package['autoload']['psr-4'])) {
				$namespace = static::getNamespaceFromPsr($path, $package['autoload']['psr-4']);
			}
		
			// anyway check psr-0
			else if ($namespace === null && isset($package['autoload']['psr-0'])) {
				$namespace = static::getNamespaceFromPsr($path, $package['autoload']['psr-0']);
			}
		}
		
		return $namespace;
	}
	
	private static function getNamespaceFromPsr($path, $psr) {
		// get longest match first
		$match = '';
		$path = trim($path, '/');
		foreach ($psr as $ns => $folder) {
			if (strpos($path, $folder) !== false && strlen($ns) > strlen($match)) {
				$match = $ns;
			}
		}
		
		// add tail
		if ($match !== '') {
			$namespace = $match;
		
			$tail = str_replace($psr[$match], '', $path);
			$namespace .= '\\' . str_replace('/', '\\', $tail) . '\\';

			return preg_replace('/(\\\)\\1+/', '$1', $namespace);
		}
		
		return null;
	}
}