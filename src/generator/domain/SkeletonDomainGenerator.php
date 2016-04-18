<?php
namespace keeko\tools\generator\domain;

class SkeletonDomainGenerator extends AbstractDomainGenerator {

	/**
	 * Add default blank methods
	 * 
	 * @param string $className
	 */
	public function generate($className) {
		$class = $this->generateClass($className);
		$class = $this->loadClass($class);
		
		$this->ensureBasicSetup($class);

		return $class;
	}
}