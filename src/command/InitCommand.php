<?php
namespace keeko\tools\command;

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
		$requireDev->set('keeko/framework', 'dev-master');
		$requireDev->set('keeko/core', '@dev');
		$requireDev->set('propel/propel', '@alpha');
		$requireDev->set('puli/repository', '@beta');
		$requireDev->set('puli/composer-plugin', '@beta');
		$requireDev->set('puli/twig-extension', '@beta');
		$requireDev->set('puli/url-generator', '@beta');
		$requireDev->set('puli/discovery', '@beta');
	}

	private function generateCode() {
		$type = $this->getPackageType();
		$package = $this->package->getKeeko()->getKeekoPackage($type);
		$generator = $this->factory->createPackageGenerator($type);
		$class = $generator->generate($package);

		$this->codeService->dumpStruct($class, true);
	}
}
