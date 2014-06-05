<?php
namespace keeko\tools\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use CG\Model\PhpClass;
use CG\Model\PhpMethod;
use CG\Model\PhpParameter;
use keeko\tools\utils\NameUtils;
use Symfony\Component\Console\Command\Command;

class GenerateInitCommand extends AbstractGenerateCommand {
	
	protected function configure() {
		$this
			->setName('generate:init')
			->setDescription('Initializes the project based on the type attribute in composer.json')
		;
		
		self::configureParameters($this);

		parent::configure();
	}
	
	public static function configureParameters(Command $command) {
		return $command
			->addOption(
				'title',
				't',
				InputOption::VALUE_OPTIONAL,
				'The package\'s title (If ommited, second part of the package name is used)',
				null
			)
			->addOption(
				'classname',
				'c',
				InputOption::VALUE_OPTIONAL,
				'The main class name (If ommited, second part of the package name is used)',
				null
			)
			->addOption(
				'namespace',
				'ns',
				InputOption::VALUE_OPTIONAL,
				'The package\'s namespace for the src/ folder (If ommited, the package name is used)',
				null
			)
		;
	}
	
	public function getOptionKeys() {
		return array_merge(['title', 'classname', 'namespace'], parent::getOptionKeys());
	}
	
	public function getArgumentKeys() {
		return array_merge([], parent::getArgumentKeys());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$package = $this->getPackage($input);
		
		if (!isset($package['type'])) {
			throw new \DomainException(sprintf('no type found in package %s', $this->getComposerFile($input)));
		}
		
		if (!in_array($package['type'], ['keeko-module', 'keeko-app'])) {
			throw new \DomainException(sprintf('Type %s not understand. Type must be keeko-module or keeko-app', $package['type']));
		}
		
		$package = $this->fixAutoload($package, $input);
		
		switch ($package['type']) {
			case 'keeko-app':
				$this->initApp($package, $input, $output);
				break;
				
			case 'keeko-module':
				$this->initModule($package, $input, $output);
				break;
		}
	}
	
	protected function fixAutoload($package, InputInterface $input) {
		if (!isset($package['autoload'])) {
			$package['autoload'] = [];
		}
		
		$autoload = $package['autoload'];
		$psr4 = [];
		$psr0 = []; 
		
		if (isset($autoload['psr-4'])) {
			$psr4 = $autoload['psr-4'];
		} 
		
		if (isset($autoload['psr-0'])) {
			$psr0 = $autoload['psr-0'];
		}
		
		// check if src/ is in $psr4
		foreach ($psr4 as $ns => $path) {
			if ($path === 'src' || $path === 'src/') {
				unset($psr4[$ns]);
				break;
			}
		}

		// check if src/ is in $psr0
		foreach ($psr0 as $ns => $path) {
			if ($path === 'src' || $path === 'src/') {
				echo 'unset ' . $ns . ' on psr0';
				unset($psr0[$ns]);
				break;
			}
		}

		// add src/ to psr4
		$ns = $input->getOption('namespace');
		if ($ns === null) {
			$ns = str_replace('/', '\\', $package['name']) . '\\';
		}
		$psr4[$ns] = 'src';
		
		$autoload['psr-4'] = $psr4;
		$autoload['psr-0'] = $psr0;
		
		if (count($psr0) == 0) {
			unset($autoload['psr-0']);
		}
		
		$package['autoload'] = $autoload;
		
		return $package;
	}
	
	protected function initApp($package, InputInterface $input, OutputInterface $output) {
// 		$extra = $this->initKeeko($package);
// 		$keeko = $input->hasOption('force') ? [] : $extra['keeko'];
	}
	
	protected function initModule($package, InputInterface $input, OutputInterface $output) {
		$extra = $this->initKeeko($package);
		$keeko = $extra['keeko'];
		$force = $input->hasOption('force');

		$title = $this->getTitle($package, $input);
		$module = isset($keeko['module']) ? $keeko['module'] : [];
		
		if (!isset($module['title']) || $force) {
			$module['title'] = ucfirst(NameUtils::pluralize($title));
		}
		
		if (!isset($module['class']) || $force) {
			$class = $this->getClassName('Module', $package, $input);
			$module['class'] = $class;
		}
		
		if (!isset($module['slug']) || $force) {
			$module['slug'] = $title;
		}
		
		if (!isset($module['default-action']) || $force) {
			$module['default-action'] = '';
		}
		
		if (!isset($module['actions']) || $force) {
			$module['actions'] = [];
		}
		
		if (!isset($module['api']) || $force) {
			$module['api'] = [];
		}
		
		$keeko['module'] = $module;
		$extra['keeko'] = $keeko;
		$package['extra'] = $extra;
		
		$this->saveComposer($package, $input, $output);
		
		// generate module class
		$className = $module['class'];
		
		$class = PhpClass::create($className)
			->setParentClassName('AbstractModule')
			->setMethod(PhpMethod::create('install'))
			->setMethod(PhpMethod::create('uninstall'))
			->setMethod(PhpMethod::create('update')
				->addParameter(new PhpParameter('from'))
				->addParameter(new PhpParameter('to'))
			)
			->addUseStatement('keeko\core\module\AbstractModule')
		;

		$fileName = $this->dumpClass($class, $input);
		$this->writeln($output, sprintf('Module <info>%s</info> created in <info>%s</info>', $module['title'], $fileName));
	}
	
	protected function initKeeko($package) {
		$extra = isset($package['extra']) ? $package['extra'] : [];
		$keeko = isset($extra['keeko']) ? $extra['keeko'] : [];
		$extra['keeko'] = $keeko;
		
		return $extra;
	}
	
	private function getTitle($package, InputInterface $input) {
		$title = $input->getOption('title');
		if ($title === null) {
			$name = $package['name'];
			$title = substr($name, strpos($name, '/') + 1);
		}
		
		return $title;
	}
	
	private function getClassName($suffix, $package, InputInterface $input) {
		$qualifiedName = $input->getOption('classname');
		if ($qualifiedName === null) {
			$qualifiedName = $package['name'];
		
			$className = NameUtils::toStudlyCase(basename($qualifiedName));
			
			// ends with suffix?
			if (substr($className, -strlen($suffix)) !== $suffix) {
				$className .= $suffix;
			}

			$qualifiedName = str_replace('/', '\\', $qualifiedName . '/' . $className);
		}

		return $qualifiedName;
	}

}
