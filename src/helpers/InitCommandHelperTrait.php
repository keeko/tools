<?php
namespace keeko\tools\helpers;

use keeko\tools\services\CommandService;
use keeko\tools\utils\NamespaceResolver;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;

trait InitCommandHelperTrait {
	
	private $gitConfig;
	
	/**
	 * @return CommandService
	 */
	abstract protected function getService();
	
	private function getPackage() {
		return $this->getService()->getPackageService()->getPackage();
	}

	private function getPackageKeeko($type) {
		$keeko = $this->getPackage()->getKeeko();
		$pkg = $keeko->getKeekoPackage($type);
	
		if ($pkg == null) {
			throw new \Exception(sprintf('Unknown package type <%s>', $type));
		}
	
		return $pkg;
	}
	
	private function getPackageTitle() {
		$input = $this->getService()->getIOService()->getInput();
		$type = $this->getPackageType();
		$keeko = $this->getPackageKeeko($type);
		$pkgTitle = $keeko === null ? null : $keeko->getTitle();
		$title = $input->getOption('title');
		$title = $title === null && !empty($pkgTitle) ? $pkgTitle : $title;
	
		// fallback to default value
		if ($title === null) {
			$title = ucwords(str_replace('/', ' ', $input->getOption('name')));
		}
	
		return $title;
	}
	
	private function getPackageClass() {
		$input = $this->getService()->getIOService()->getInput();
		$type = $this->getPackageType();
		$keeko = $this->getPackageKeeko($type);
		$pkgClass = $keeko === null ? null : $keeko->getClass();
		$classname = $input->getOption('classname');
		$classname = $classname === null && !empty($pkgClass) ? $pkgClass : $classname;
	
		// default value
		if ($classname === null) {
			$pkgName = $this->getPackage()->getFullName();
			$parts = explode('/', $pkgName);
			$ns = $input->getOption('namespace');
			$namespace = !empty($ns) ? $ns : str_replace('/', '\\', $pkgName);
			$classname = $namespace . '\\' . ucfirst($parts[1]);
	
			// suffix
			if ($type === 'module') {
				$classname .= 'Module';
			} else if ($type === 'app') {
				$classname .= 'Application';
			}
		}
	
		return $classname;
	}
	
	private function getPackageType() {
		$input = $this->getService()->getIOService()->getInput();
		$type = $input->getOption('type');
		$pkgType = $this->getPackage()->getType();
		return $type === null && !empty($pkgType)
		? str_replace('keeko-', '', $pkgType)
		: $type;
	}
	
	private function getPackageName() {
		$input = $this->getService()->getIOService()->getInput();
		$name = $input->getOption('name');
		$pkgName = $this->getPackage()->getFullName();
		return $name === null && !empty($pkgName) ? $pkgName : $name;
	}
	
	private function getPackageDescription() {
		$input = $this->getService()->getIOService()->getInput();
		$desc = $input->getOption('description');
		$pkgDesc = $this->getPackage()->getDescription();
		return $desc === null && !empty($pkgDesc) ? $pkgDesc : $desc;
	}
	
	private function getPackageLicense() {
		$input = $this->getService()->getIOService()->getInput();
		$license = $input->getOption('license');
		$pkgLicense = $this->getPackage()->getLicense();
		return $license === null && !empty($pkgLicense) ? $pkgLicense : $license;
	}
	
	private function hasAutoload() {
		return NamespaceResolver::getNamespace('src', $this->package);
	}
	
	private function validateName($name) {
		if (!preg_match('{^[a-z0-9_.-]+/[a-z0-9_.-]+$}', $name)) {
			throw new \InvalidArgumentException(
				'The package name ' . $name . ' is invalid, it should be lowercase and have a vendor name, a forward slash, and a package name, matching: [a-z0-9_.-]+/[a-z0-9_.-]+'
			);
		}
	}
	
	private function setAutoload($namespace) {
		$autoload = $this->getPackage()->getAutoload();
	
		// remove existing src/ entry
		$autoload->getPsr0()->removePath('src');
		$autoload->getPsr4()->removePath('src');
	
		// add src/ to psr4
		$autoload->getPsr4()->setPath($namespace, 'src/');
	}
	
	protected function getGitConfig() {
		if (null !== $this->gitConfig) {
			return $this->gitConfig;
		}
		$finder = new ExecutableFinder();
		$gitBin = $finder->find('git');
		$cmd = new Process(sprintf('%s config -l', ProcessUtils::escapeArgument($gitBin)));
		$cmd->run();
		if ($cmd->isSuccessful()) {
			$this->gitConfig = [];
			$matches = [];
			preg_match_all('{^([^=]+)=(.*)$}m', $cmd->getOutput(), $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$this->gitConfig[$match[1]] = $match[2];
			}
			return $this->gitConfig;
		}
		return $this->gitConfig = [];
	}
}