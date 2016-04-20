<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\LicenseTag;
use keeko\framework\schema\AuthorSchema;
use keeko\tools\helpers\InitCommandHelperTrait;
use keeko\tools\services\IOService;
use keeko\tools\ui\InitUI;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InitCommand extends AbstractKeekoCommand {
	
	use InitCommandHelperTrait;

	protected function configure() {
		$this
			->setName('init')
			->setDescription('Initializes composer.json with keeko related values')
			->addOption(
				'name',
				'',
				InputOption::VALUE_REQUIRED,
				'Name of the package'
			)
			->addOption(
				'description',
				'd',
				InputOption::VALUE_OPTIONAL,
				'Description of the package'
			)
			->addOption(
				'author',
				'',
				InputOption::VALUE_OPTIONAL,
				'Author name of the package'
			)
			->addOption(
				'type',
				't',
				InputOption::VALUE_REQUIRED,
				'The type of the package (app|module)'
			)
			->addOption(
				'namespace',
				'ns',
				InputOption::VALUE_OPTIONAL,
				'The package\'s namespace for the src/ folder (If ommited, the package name is used)'
			)
			->addOption(
				'license',
				'l',
				InputOption::VALUE_OPTIONAL,
				'License of the package'
			)
			->addOption(
				'title',
				'',
				InputOption::VALUE_OPTIONAL,
				'The package\'s title (If ommited, second part of the package name is used)',
				null
			)
			->addOption(
				'classname',
				'c',
				InputOption::VALUE_OPTIONAL,
				'The main class name (If ommited, there is a default handler)',
				null
			)
			->addOption(
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Allows to overwrite existing values'
			)
		;
		
		$this->configureGlobalOptions();
	}

	protected function initialize(InputInterface $input, OutputInterface $output) {
		parent::initialize($input, $output);
	}
	
	/**
	 * @return PackageSchema
	 */
	protected function getPackage() {
		return $this->package;
	}
	
	/**
	 * @return IOService
	 */
	protected function getIO() {
		return $this->io;
	}

	protected function interact(InputInterface $input, OutputInterface $output) {
		$ui = new InitUI($this);
		$ui->show();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->generatePackage();
		$this->generateCode();
	}
	
	private function generatePackage() {
		$input = $this->io->getInput();
		$force = $input->getOption('force');

		// name
		$localName = $this->package->getFullName();
		if (empty($localName) && $input->getOption('name') === null) {
			throw new \RuntimeException('No name for the package given');
		}
		
		if (($force || empty($localName)) && ($name = $input->getOption('name')) !== null) {
			$this->validateName($name);
			$this->package->setFullName($name);
		}
		
		// description
		if (($desc = $input->getOption('description')) !== null) {
			$this->package->setDescription($desc);
		}
		
		// type
		if (($type = $input->getOption('type')) !== null) {
			if (in_array($type, ['app', 'module'])) {
				$this->package->setType('keeko-' . $type);
			}
		}
		
		// license
		if (($license = $input->getOption('license')) !== null) {
			$this->package->setLicense($license);
		}
		
		// author
		if (($author = $input->getOption('author')) !== null
				&& ($this->package->getAuthors()->isEmpty() || $force)) {
			list($name, $email) = sscanf($author, '%s <%s>');
		
			$author = new AuthorSchema();
			$author->setName($name);
		
			if (substr($email, -1) == '>') {
				$email = substr($email, 0, -1);
			}
			$author->setEmail($email);
				
			$this->package->getAuthors()->add($author);
		}

		// autoload
		if (!$this->hasAutoload()) {
			$namespace = $input->getOption('namespace');
			if ($namespace === null) {
				$namespace = str_replace('/', '\\', $this->package->getFullName());
			}
			if (substr($namespace, -2) !== '\\') {
				$namespace .= '\\';
			}
			$this->setAutoload($namespace);
		}
		
		$this->manageDependencies();
		
		// KEEKO
		if ($type === null) {
			$type = $this->getPackageType();
		}
		
		// title
		$keeko = $this->packageService->getKeeko()->getKeekoPackage($type);
		if (($title = $this->getPackageTitle()) !== null) {
			$keeko->setTitle($title);
		}
		
		// class
		if (($classname = $this->getPackageClass()) !== null) {
			$keeko->setClass($classname);
		}
		
		$this->packageService->savePackage($this->package);
	}

	private function manageDependencies() {
		// add require statements
		$require = $this->package->getRequire();

		if (!$require->has('php')) {
			$require->set('php', '>=5.4');
		}

		if (!$require->has('keeko/composer-installer')) {
			$require->set('keeko/composer-installer', '*');
		}

		// add require dev statements
		$requireDev = $this->package->getRequireDev();
		$requireDev->set('keeko/core', 'dev-master');
		$requireDev->set('composer/composer', '@dev');
		$requireDev->set('propel/propel', '@dev');
		$requireDev->set('puli/composer-plugin', '@beta');
	}

	private function generateCode() {
		$class = $this->generateClass();
		$type = $this->getPackageType();

		switch ($type) {
			case 'app':
				$this->handleAppClass($class);
				break;
				
			case 'module':
				$this->handleModuleClass($class);
				break;
		}

		$this->codegenService->dumpStruct($class, true);
	}

	private function generateClass() {
		$input = $this->io->getInput();
		$type = $this->getPackageType();
		$package = $this->package->getKeeko()->getKeekoPackage($type);
		$fqcn = str_replace(['\\', 'keeko-', '-module', '-app'], ['/', '', '', ''], $package->getClass());
		$classname = basename($fqcn);
		$filename = $this->project->getRootPath() . '/src/' . $classname . '.php';
		$fqcn = str_replace('/', '\\', $fqcn);
		
		if (!file_exists($filename) || $input->getOption('force')) {
			$class = PhpClass::create($fqcn);
			$class->setDescription($package->getTitle());
			
			$docblock = $class->getDocblock();
			$docblock->appendTag(new LicenseTag($this->package->getLicense()));
			$this->codegenService->addAuthors($class, $this->package);
		} else {
			$class = PhpClass::fromFile($filename);
		}
		
		return $class;
	}

	private function handleAppClass(PhpClass $class) {
		// set parent
		$class->setParentClassName('AbstractApplication');
		$class->addUseStatement('keeko\\framework\\foundation\\AbstractApplication');

		// method: run(Request $request, $path)
		if (!$class->hasMethod('run')) {
			$class->setMethod(PhpMethod::create('run')
				->addParameter(PhpParameter::create('request')->setType('Request'))
				->addParameter(PhpParameter::create('path')->setType('string'))
			);
			$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
		}
	}
	
	private function handleModuleClass(PhpClass $class) {
		// set parent
		$class->setParentClassName('AbstractModule');
		$class->addUseStatement('keeko\\framework\\foundation\\AbstractModule');
		
		// method: install()
		if (!$class->hasMethod('install')) {
			$class->setMethod(PhpMethod::create('install'));
		}
		
		// method: uninstall()
		if (!$class->hasMethod('uninstall')) {
			$class->setMethod(PhpMethod::create('uninstall'));
		}
		
		// method: update($from, $to)
		if (!$class->hasMethod('update')) {
			$class->setMethod(PhpMethod::create('update')
				->addParameter(PhpParameter::create('from')->setType('mixed'))
				->addParameter(PhpParameter::create('to')->setType('mixed'))
			);
		}
	}
}
