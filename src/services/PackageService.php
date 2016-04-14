<?php
namespace keeko\tools\services;

use keeko\framework\schema\PackageSchema;
use phootwork\file\exception\FileException;
use phootwork\file\File;
use keeko\tools\utils\NamespaceResolver;

class PackageService extends AbstractService {

	/** @var PackageSchema */
	private $package = null;
	private $keeko = null;
	private $module = null;
	private $app = null;
	private $namespace = null;
	
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
	 * Returns the root namespace for this package
	 *
	 * @return string the namespace
	 */
	public function getNamespace() {
		if ($this->namespace === null) {
			$input = $this->io->getInput();
			$ns = $input->hasOption('namespace')
			? $input->getOption('namespace')
			: null;
			if ($ns === null) {
				$package = $this->service->getPackageService()->getPackage();
				$ns = NamespaceResolver::getNamespace('src', $package);
			}
	
			$this->namespace = trim($ns, '\\');
		}
	
		return $this->namespace;
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

	public function getActionType($actionName, $modelName) {
		$input = $this->io->getInput();
		$type = $input->hasOption('type') ? $input->getOption('type') : null;
		if ($type === null) {
			if (($pos = strrpos($actionName, '-')) !== false) {
				$type = substr($actionName, $pos + 1);
			} else if ($modelName == $actionName) {
				$type = 'read';
			}
		}
		return $type;
	}
	
	public function savePackage(PackageSchema $package = null) {
		if ($package === null) {
			$package = $this->getPackage();
		}
		
		$filename = $this->service->getProject()->getComposerFileName();
		$this->service->getJsonService()->write($filename, $package->toArray());
		$this->io->writeln(sprintf('Package <info>%s</info> written at <info>%s</info>', $package->getFullName(), $filename));
	}
}
