<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\docblock\tags\LicenseTag;
use keeko\tools\helpers\QuestionHelperTrait;
use keeko\tools\utils\NamespaceResolver;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;
use keeko\framework\schema\AuthorSchema;

class InitCommand extends AbstractGenerateCommand {
	
	use QuestionHelperTrait;

	private $gitConfig;

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
// 			->addOption(
// 				'default-action',
// 				'',
// 				InputOption::VALUE_OPTIONAL,
// 				'The module\'s default action'
// 			)
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

	protected function interact(InputInterface $input, OutputInterface $output) {
		$force = $input->getOption('force');
		$formatter = $this->getHelperSet()->get('formatter');
		$output->writeln([
			'',
			$formatter->formatBlock('Welcome to the Keeko initializer', 'bg=blue;fg=white', true),
			''
		]);
		$output->writeln([
			'',
			'This command will guide you through creating your Keeko composer package.',
			'',
		]);

		$name = $this->getPackageName();
		$askName = $name === null;
		if ($name === null) {
			$git = $this->getGitConfig();
			$cwd = realpath(".");
			$name = basename($cwd);
			$name = preg_replace('{(?:([a-z])([A-Z])|([A-Z])([A-Z][a-z]))}', '\\1\\3-\\2\\4', $name);
			$name = strtolower($name);
			$localName = $this->package->getFullName();
			if (!empty($localName)) {
				$name = $this->package->getFullName();
			} else if (isset($git['github.user'])) {
				$name = $git['github.user'] . '/' . $name;
			} elseif (!empty($_SERVER['USERNAME'])) {
				$name = $_SERVER['USERNAME'] . '/' . $name;
			} elseif (get_current_user()) {
				$name = get_current_user() . '/' . $name;
			} else {
				// package names must be in the format foo/bar
				$name = $name . '/' . $name;
			}
		} else {
			$this->validateName($name);
		}
		
		// asking for the name
		if ($askName || $force) {
			$name = $this->askQuestion(new Question('Package name (<vendor>/<name>)', $name));
			$this->validateName($name);
			$input->setOption('name', $name);
		}

		// asking for a description
		$desc = $this->getPackageDescription();
		if ($desc === null || $force) {
			$desc = $this->askQuestion(new Question('Description', $desc));
			$input->setOption('description', $desc);
		}
		
		// asking for the author
		if ($this->package->getAuthors()->isEmpty() || $force) {
			$author = $input->getOption('author');
			if ($author === null && isset($git['user.name'])) {
				$author = $git['user.name'];
				
				if (isset($git['user.email'])) {
					$author = sprintf('%s <%s>', $git['user.name'], $git['user.email']);
				}
			}
	
			$author = $this->askQuestion(new Question('Author', $author));
			$input->setOption('author', $author);
		}
		
		// asking for the package type
		$type = $this->getPackageType();
		if ($type === null || $force) {
			$types = ['module', 'app'];
			$question = new Question('Package type (module|app)', $type);
			$question->setAutocompleterValues($types);
			$question->setValidator(function($answer) use ($types) {
				if (!in_array($answer, $types)) {
					throw new \RuntimeException('The name of the type should be one of: ' . 
							implode(',', $types));
				}
				return $answer;
			});
			$question->setMaxAttempts(2);
			$type = $this->askQuestion($question);
		}
		$input->setOption('type', $type);
		
		// asking for the license
		$license = $this->getPackageLicense();
		if ($license === null || $force) {
			$license = $this->askQuestion(new Question('License', $license));
			$input->setOption('license', $license);
		}
		
		// asking for namespace
// 		if (!$this->hasAutoload() || $force) {
// 			$namespace = $input->getOption('namespace');
// 			if ($namespace === null) {
// 				$namespace = str_replace('/', '\\', $name);
// 			}
// 			$namespace = $this->askQuestion(new Question('Namespace for src/', $namespace));
// 			$input->setOption('namespace', $namespace);
// 		}

		//
		// KEEKO values
		$output->writeln([
			'',
			'Information for Keeko ' . ucfirst($type),
			''
		]);

		// ask for the title
		$title = $this->getPackageTitle();
		if ($title === null || $force) {
			$title = $this->askQuestion(new Question('Title', $title));
			$input->setOption('title', $title);
		}

		// ask for the class
		$classname = $this->getPackageClass();
		if ($classname === null || $force) {
			$classname = $this->askQuestion(new Question('Class', $classname));
			$input->setOption('classname', $classname);
		}

// 		// -- module
// 		if ($type === 'module') {
			// ask for the default action
// 			$defaultAction = $this->getPackageDefaultAction();
// 			$defaultAction = $this->askQuestion(new Question('Default Action', $defaultAction));
// 			$input->setOption('default-action', $defaultAction);
// 		}
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
		
// 		// additions for keeko-module
// 		if ($keeko instanceof ModuleSchema) {
// 			// default-action
// // 			if (($defaultAction = $this->getPackageDefaultAction()) !== null) {
// // 				$keeko->setDefaultAction($defaultAction);
// // 			}
// 		}
		
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

	private function getPackageKeeko($type) {
		$keeko = $this->package->getKeeko();
		$pkg = $keeko->getKeekoPackage($type);
		
		if ($pkg == null) {
			throw new \Exception(sprintf('Unknown package type <%s>', $type));
		}
		
		return $pkg;
	}

// 	private function getPackageSlug() {
// 		$type = $this->getPackageType();
// 		if ($type !== 'module') {
// 			return;
// 		}

// 		$input = $this->io->getInput();
// 		$keeko = $this->getPackageKeeko('module');
// 		$pkgSlug = $keeko->getSlug();
// 		$slug = $input->getOption('slug');
// 		$slug = $slug === null && !empty($pkgSlug) ? $pkgSlug : $slug;
		
// 		// fallback to default value
// 		if ($slug === null) {
// 			$slug = str_replace('/', '.', $this->package->getFullName());
// 		}
		
// 		return $slug;
// 	}
	
// 	private function getPackageDefaultAction() {
// 		$type = $this->getPackageType();
// 		if ($type !== 'module') {
// 			return;
// 		}
	
// 		$input = $this->getInput();
// 		$keeko = $this->getPackageKeeko('module');
// 		$defaultAction = $input->getOption('default-action');
// 		$defaultAction = $defaultAction === null && isset($keeko['default-action']) ? $keeko['default-action'] : $defaultAction;
	
// 		return $defaultAction;
// 	}

	private function getPackageTitle() {
		$input = $this->io->getInput();
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
		$input = $this->io->getInput();
		$type = $this->getPackageType();
		$keeko = $this->getPackageKeeko($type);
		$pkgClass = $keeko === null ? null : $keeko->getClass();
		$classname = $input->getOption('classname');
		$classname = $classname === null && !empty($pkgClass) ? $pkgClass : $classname;
	
		// default value
		if ($classname === null) {
			$pkgName = $this->package->getFullName();
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
		$input = $this->io->getInput();
		$type = $input->getOption('type');
		$pkgType = $this->package->getType();
		return $type === null && !empty($pkgType) 
			? str_replace('keeko-', '', $pkgType) 
			: $type;
	}
	
	private function getPackageName() {
		$input = $this->io->getInput();
		$name = $input->getOption('name');
		$pkgName = $this->package->getFullName();
		return $name === null && !empty($pkgName) ? $pkgName : $name;
	}
	
	private function getPackageDescription() {
		$input = $this->io->getInput();
		$desc = $input->getOption('description');
		$pkgDesc = $this->package->getDescription();
		return $desc === null && !empty($pkgDesc) ? $pkgDesc : $desc;
	}
	
	private function getPackageLicense() {
		$input = $this->io->getInput();
		$license = $input->getOption('license');
		$pkgLicense = $this->package->getLicense();
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
		$autoload = $this->package->getAutoload();
		
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
