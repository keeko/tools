<?php
namespace keeko\tools\helpers;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use gossi\json\Json;
use gossi\json\JsonException;
use keeko\tools\exceptions\ComposerJsonNotFoundException;
use keeko\tools\exceptions\ComposerJsonEmptyException;
use Symfony\Component\Console\Helper\HelperSet;

trait PackageHelperTrait {
	
	private $package = null;
	private $keeko = null;
	private $module = null;
	private $actions = null;
	private $app = null;
	
	abstract protected function writeln($message);
	abstract protected function getHelperSet();
	
	/**
	 * 
	 * @throws ComposerJsonNotFoundException
	 * @throws ComposerJsonEmptyException
	 * @throws \RuntimeException
	 * @return array the package
	 */
	protected function getPackage() {
		if ($this->package === null) {

			$jsonFile = $this->getComposerFile();

			if (!file_exists($jsonFile)) {
				throw new ComposerJsonNotFoundException('composer.json not found');
			}

			try {
				$this->package = Json::decode(file_get_contents($jsonFile));
			} catch (JsonException $e) {
				if ($this->package === null) {
					throw new ComposerJsonEmptyException('composer.json is empty');
				} else {
					throw new \RuntimeException(sprintf('Problem occured while decoding %s: %s', $jsonFile, $e->getMessage()));
				}
			}
		}

		return $this->package;
	}
	
	protected function getPackageVendor() {
		$package = $this->getPackage();
		$name = $package['name'];
		return substr($name, 0, strpos($name, '/'));
	}
	
	protected function getPackageNameWithoutVendor() {
		$package = $this->getPackage();
		$name = $package['name'];
		return substr($name, strpos($name, '/') + 1);
	}

	protected function getComposerFile() {
		$input = $this->getHelperSet()->get('io')->getInput();
		$jsonOpt = $input->hasOption('composer') ? $input->getOption('composer') : null;
		return $jsonOpt !== null ? $jsonOpt : getcwd() . '/composer.json';
	}
	
	/**
	 * Returns the keeko node from the composer.json extra
	 * 
	 * @return array
	 */
	protected function getKeeko() {
		if ($this->keeko === null) {
			$json = $this->getPackage();
		
			$this->keeko = [];
			if (isset($json['extra']) && isset($json['extra']['keeko'])) {
				$this->keeko = $json['extra']['keeko'];
			}
		}

		return $this->keeko;
	}

	/**
	 * Returns the keeko.module node from composer.json extra
	 * 
	 * @return array
	 */
	protected function getKeekoModule() {
		if ($this->module === null) {
			$keeko = $this->getKeeko();
		
			$this->module = [];
			if (isset($keeko['module'])) {
				$this->module = $keeko['module'];
			}
		}
	
		return $this->module;
	}
	
	/**
	 * Returns the keeko.app node from composer.json extra
	 *
	 * @return array
	 */
	protected function getKeekoApp() {
		if ($this->app === null) {
			$keeko = $this->getKeeko();
		
			$this->app = [];
			if (isset($keeko['app'])) {
				$this->app = $keeko['app'];
			}
		}
	
		return $this->app;
	}

	/**
	 * Returns the keeko.module.actions node from the composer.json extra
	 * 
	 * @return array
	 */
	protected function getKeekoActions() {
		if ($this->actions === null) {
			$module = $this->getKeekoModule();
		
			$this->actions = [];
			if (isset($module['actions'])) {
				$this->actions = $module['actions'];
			}
		}
	
		return $this->actions;
	}
	
	protected function hasAction($name) {
		$actions = $this->getKeekoActions();
		return isset($actions[$name]);
	}
	
	protected function getKeekoAction($name) {
		if ($this->hasAction($name)) {
			$actions = $this->getKeekoActions();
			return $actions[$name];
		}
		return [];
	}
	
	protected function getActionType($name, $model) {
		$input = $this->getInput();
		$type = $input->hasOption('type') ? $input->getOption('type') : null;
		if ($type === null) {
			if (($pos = strpos($name, '-')) !== false) {
				$type = substr($name, $pos + 1);
			}
			else if ($model == $name) {
				$type = 'read';
			}
		}
		return $type;
	}

	protected function updateAction($name, $data) {
		$this->actions[$name] = $data;
		$this->package['extra']['keeko']['module']['actions'] = $this->actions;
	}
	
	private function getSlug($package = null) {
		if ($package === null) {
			$package = $this->getPackage();
		}
		return str_replace('/', '.', $package['name']);
	}

	protected function savePackage($package = null) {
		if ($package !== null) {
			$this->updatePackage($package);
		}
		$filename = $this->getComposerFile();
		$contents = Json::encode($this->package, Json::PRETTY_PRINT | Json::UNESCAPED_SLASHES);
		$fs = new Filesystem();
		$fs->dumpFile($filename, $contents, 0755);

		$this->writeln(sprintf('Package <info>%s</info> written at <info>%s</info>', $this->package['name'], $filename));
	}

	protected function updatePackage($package) {
		$this->package = $package;
		$this->keeko = null;
		$this->module = null;
		$this->actions = null;
		$this->app = null;
	}
}