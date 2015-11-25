<?php
namespace keeko\tools\services;

use keeko\core\schema\ActionSchema;
use keeko\core\schema\AppSchema;
use keeko\core\schema\KeekoSchema;
use keeko\core\schema\ModuleSchema;
use keeko\core\schema\PackageSchema;
use phootwork\file\exception\FileException;
use phootwork\file\File;

class PackageService extends AbstractService {

	/** @var PackageSchema */
	private $package = null;
	private $keeko = null;
	private $module = null;
	private $actions = null;
	private $app = null;
	
	/**
	 *
	 * @throws FileException
	 * @return PackageSchema
	 */
	public function getPackage() {
		if ($this->package === null) {
			$file = new File($this->service->getProject()->getComposerFileName());
			if ($file->exists()) {
				$this->package = PackageSchema::fromFile($file->getPathname());
			} else {
				$this->package = new PackageSchema();
			}
		}

		return $this->package;
	}
	
	/**
	 * Returns the keeko node from the composer.json extra
	 *
	 * @return KeekoSchema
	 */
	public function getKeeko() {
		if ($this->keeko === null) {
			$package = $this->getPackage();
			$this->keeko = $package->getKeeko();
		}
	
		return $this->keeko;
	}
	
	/**
	 * Returns the keeko module schema
	 *
	 * @return ModuleSchema
	 */
	public function getModule() {
		if ($this->module === null) {
			$keeko = $this->getKeeko();
			$this->module = $keeko->getModule();
		}
	
		return $this->module;
	}
	
	/**
	 * Returns the keeko app schema
	 *
	 * @return AppSchema
	 */
	public function getApp() {
		if ($this->app === null) {
			$keeko = $this->getKeeko();
			$this->app = $keeko->getApp();
		}
	
		return $this->app;
	}
	
	/**
	 * Returns an action
	 *
	 * @param string $name
	 * @return ActionSchema
	 */
	public function getAction($name) {
		$module = $this->getModule();
		if ($module !== null && $module->hasAction($name)) {
			return $module->getAction($name);
		}

		return null;
	}

	public function getActionType($name, $model) {
		$input = $this->io->getInput();
		$type = $input->hasOption('type') ? $input->getOption('type') : null;
		if ($type === null) {
			if (($pos = strpos($name, '-')) !== false) {
				$type = substr($name, $pos + 1);
			} else if ($model == $name) {
				$type = 'read';
			}
		}
		return $type;
	}

// 	public function updateAction($name, $data) {
// 		$this->actions[$name] = $data;
// 		$this->package['extra']['keeko']['module']['actions'] = $this->actions;
// 	}
	
// 	private function getSlug($package = null) {
// 		if ($package === null) {
// 			$package = $this->getPackage();
// 		}
// 		return str_replace('/', '.', $package['name']);
// 	}
	
	public function savePackage(PackageSchema $package = null) {
		if ($package === null) {
			$package = $this->getPackage();
		}
		$filename = $this->service->getProject()->getComposerFileName();
		$this->service->getJsonService()->write($filename, $package->toArray());
		
// 		print_r($package->toArray());
	
		$this->io->writeln(sprintf('Package <info>%s</info> written at <info>%s</info>', $package->getFullName(), $filename));
	}
}
