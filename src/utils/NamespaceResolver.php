<?php
namespace keeko\tools\utils;

use keeko\core\schema\PackageSchema;
use phootwork\file\Path;
use phootwork\lang\Text;

class NamespaceResolver {
	
	/**
	 * Returns the source path for a given namespace or null if none can be found.
	 *
	 * @param string $namespace
	 * @param PackageSchema $package
	 * @return string|null
	 */
	public static function getSourcePath($namespace, PackageSchema $package) {
		$relativeSourcePath = null;
		$autoload = $package->getAutoload();
		
		$suffix = '';
		$ns = new Path(str_replace('\\', '/', $namespace));
		$ns->removeTrailingSeparator();
		
		do {
			$namespace = $ns->getPathname()->replace('/', '\\')->toString();
			
			// find paths in psr-s
			$relativeSourcePath = $autoload->getPsr4()->getPath($namespace . '\\');

			if ($relativeSourcePath === null) {
				$relativeSourcePath = $autoload->getPsr0()->getPath($namespace);
			}

			// keep track of suffix
			if ($relativeSourcePath === null) {
				$suffix = $ns->lastSegment() . (!empty($suffix) ? '/' : '') . $suffix;
				$ns = $ns->upToSegment($ns->segmentCount() - 1);
			}
		} while ($relativeSourcePath == null && $ns->segmentCount() >= 1);
		
		$path = new Path($relativeSourcePath);
		$path->removeTrailingSeparator();
		$path = $path->append($suffix);
		$path->addTrailingSeparator();
		
		return $path->getPathname()->toString();
	}

	
	/**
	 * Returns the namespace for a given path or null if none can be found.
	 *
	 * @param string $path
	 * @param PackageSchema $package
	 * @return string|null
	 */
	public static function getNamespace($path, PackageSchema $package) {
		$autoload = $package->getAutoload();
		$namespace = null;
		
		$suffix = '';
		$namespace = null;
		$path = new Path($path);
		$path->removeTrailingSeparator();
		
		do {
			$pathname = $path->getPathname()->toString();
			
			// find namespace in psr-4
			$namespace = $autoload->getPsr4()->getNamespace($pathname);
			if ($namespace === null) {
				$namespace = $autoload->getPsr4()->getNamespace($pathname . '/');
			}
			
			// find namespace in psr-0
			if ($namespace === null) {
				$namespace = $autoload->getPsr0()->getNamespace($pathname);
			}
			if ($namespace === null) {
				$namespace = $autoload->getPsr0()->getNamespace($pathname . '/');
			}
			
			// keep track of suffix
			if ($namespace === null) {
				$suffix = $path->lastSegment() . (!empty($suffix) ? '\\' : '') . $suffix;
				$path = $path->upToSegment($path->segmentCount() - 1);
			}
		} while ($namespace === null && $path->segmentCount() >= 1);
		
		$namespace = new Text($namespace . $suffix);
		if ($namespace->endsWith('\\')) {
			$namespace = $namespace->substring(0, -1);
		}

		return $namespace->toString();
	}
}