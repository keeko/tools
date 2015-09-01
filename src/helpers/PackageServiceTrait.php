<?php
namespace keeko\tools\helpers;

use keeko\tools\services\CommandService;
use keeko\core\schema\PackageSchema;
use keeko\core\schema\ModuleSchema;
use keeko\core\schema\ActionSchema;
use keeko\core\schema\AppSchema;

trait PackageServiceTrait {
	
	/** @var PackageSchema */
	protected $package;
	
	/**
	 * @return CommandService
	 */
	abstract protected function getService();
	
	/**
	 * @return PackageSchema the package
	 */
	protected function getPackage() {
		return $this->getService()->getPackageService()->getPackage();
	}

// 	/**
// 	 * Returns the vendor part of the package name
// 	 * 
// 	 * @return String the vendor part
// 	 */
// 	protected function getVendorName() {
// 		return $this->getService()->getPackageService()->getVendorName();
// 	}
	
// 	/**
// 	 * Returns the second part of the package name
// 	 * 
// 	 * @return String
// 	 */
// 	protected function getPackageName() {
// 		return $this->getService()->getPackageService()->getPackageName();
// 	}
	
// 	/**
// 	 * Returns the full package name of vendor/name
// 	 * 
// 	 * @return String
// 	 */
// 	protected function getFullPackageName() {
// 		return $this->getService()->getPackageService()->getFullPackageName();
// 	}

// 	/**
// 	 * Returns the composer file name
// 	 * 
// 	 * @return String
// 	 */
// 	protected function getComposerFile() {
// 		return $this->getService()->getPackageService()->getComposerFile();
// 	}
	
// 	/**
// 	 * Returns the api file name
// 	 * 
// 	 * @return String
// 	 */
// 	protected function getApiFile() {
// 		return $this->getService()->getPackageService()->getApiFile();
// 	}

	/**
	 * Returns the keeko node from the composer.json extra
	 *
	 * @return KeekoSchema
	 */
	protected function getKeeko() {
		return $this->getService()->getPackageService()->getKeeko();
	}
	
	/**
	 * Returns the keeko module schema
	 *
	 * @return ModuleSchema
	 */
	protected function getModule() {
		return $this->getService()->getPackageService()->getModule();
	}
	
	/**
	 * Returns the keeko app schema
	 *
	 * @return AppSchema
	 */
	protected function getApp() {
		return $this->getService()->getPackageService()->getApp();
	}
	
	/**
	 * Returns an action
	 * 
	 * @param string $name
	 * @return ActionSchema
	 */
	protected function getAction($name) {
		return $this->getService()->getPackageService()->getAction($name);
	}

// 	/**
// 	 * Returns the keeko.module.actions node from the composer.json extra
// 	 * 
// 	 * @return array
// 	 */
// 	protected function getActions() {
// 		return $this->getService()->getPackageService()->getActions();
// 	}

// 	protected function hasAction($name) {
// 		return $this->getService()->getPackageService()->hasAction($name);
// 	}

// 	protected function getAction($name) {
// 		return $this->getService()->getPackageService()->getAction($name);
// 	}
	
	protected function getActionType($name, $model) {
		return $this->getService()->getPackageService()->getActionType($name, $model);
	}

// 	protected function updateAction($name, $data) {
// 		return $this->getService()->getPackageService()->updateAction($name, $data);
// 	}
	
// 	private function getSlug($package = null) {
// 		if ($package === null) {
// 			$package = $this->getPackage();
// 		}
// 		return str_replace('/', '.', $package['name']);
// 	}

	protected function savePackage(PackageSchema $package = null) {
		return $this->getService()->getPackageService()->savePackage($package);
	}

// 	protected function updatePackage($package) {
// 		return $this->getService()->getPackageService()->updatePackage($package);
// 	}
}