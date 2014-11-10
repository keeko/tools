<?php
namespace keeko\tools\command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use keeko\tools\helpers\PackageHelperTrait;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;
use gossi\json\Json;
use keeko\tools\helpers\QuestionHelperTrait;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use gossi\codegen\generator\CodeGenerator;
use Symfony\Component\Filesystem\Filesystem;
use gossi\docblock\tags\AuthorTag;
use gossi\docblock\Docblock;
use gossi\docblock\tags\UnknownTag;
use gossi\docblock\tags\LicenseTag;
use gossi\codegen\generator\CodeFileGenerator;
use keeko\tools\helpers\CodeGeneratorHelperTrait;

class InitCommand extends AbstractGenerateCommand {
	
	use PackageHelperTrait;
	use QuestionHelperTrait;
	use CodeGeneratorHelperTrait;
	
	private $localPackage;
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
			->addOption(
				'slug',
				'',
				InputOption::VALUE_OPTIONAL,
				'The slug (if this package is a keeko-module, anyway it\'s ignored)'
			)
			->addOption(
				'default-action',
				'',
				InputOption::VALUE_OPTIONAL,
				'The module\'s default action'
			)
			->addOption(
				'force',
				'f',
				InputOption::VALUE_NONE,
				'Allows to overwrite existing values'
			)
		;
	}

	protected function initialize(InputInterface $input, OutputInterface $output) {
		parent::initialize($input, $output);
		try {
			$this->localPackage = $this->getPackage();
		} catch (\Exception $e) {
			$this->localPackage = [];
		}
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
		
		if (!$name = $input->getOption('name')) {
			$git = $this->getGitConfig();
			$cwd = realpath(".");
			$name = basename($cwd);
			$name = preg_replace('{(?:([a-z])([A-Z])|([A-Z])([A-Z][a-z]))}', '\\1\\3-\\2\\4', $name);
			$name = strtolower($name);
			if (isset($this->localPackage['name'])) {
				$name = $this->localPackage['name'];
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
		$name = $this->askQuestion(new Question('Package name (<vendor>/<name>)', $name));
		$this->validateName($name);
		$input->setOption('name', $name);
		
		// asking for a description
		$desc = $this->getPackageDescription();
		if ($desc === null || $force) {
			$desc = $this->askQuestion(new Question('Description', $desc));
			$input->setOption('description', $desc);
		}
		
		// asking for the author
		if (!isset($this->localPackage['authors']) || $force) {
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
			$question->setValidator(function ($answer) use ($types) {
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
		
		// namespace
		if (!$this->hasAutoload()) {
			$namespace = $input->getOption('namespace');
			if ($namespace === null) {
				$namespace = str_replace('/', '\\', $name);
			}
			$namespace = $this->askQuestion(new Question('Namespace for src/', $namespace));
			$input->setOption('namespace', $namespace);
		}

		//
		// KEEKO
		$output->writeln([
			'',
			'Information for Keeko ' . ucfirst($type),
			''
		]);

		// ask for the title
		$title = $this->getPackageTitle($type);
		$title = $this->askQuestion(new Question('Title', $title));
		$input->setOption('title', $title);

		// ask for the class
		$classname = $this->getPackageClass($type);
		$classname = $this->askQuestion(new Question('Class', $classname));
		$input->setOption('classname', $classname);

		// -- module
		if ($type === 'module') {
			
			// ask for the slug
			$slug = $this->getPackageSlug();
			$slug = $this->askQuestion(new Question('Slug', $slug));
			$input->setOption('slug', $slug);
			
			// ask for the default action
			$defaultAction = $this->getPackageDefaultAction();
			$defaultAction = $this->askQuestion(new Question('Default Action', $defaultAction));
			$input->setOption('default-action', $defaultAction);
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$force = $input->getOption('force');
		
		// name
		if (!isset($this->localPackage['name']) && $input->getOption('name') === null) {
			throw new \RuntimeException('No name for the package given');
		}
		
		if (($force || !isset($this->localPackage['name'])) && ($name = $input->getOption('name')) !== null) {
			$this->validateName($name);
			$this->localPackage['name'] = $name;
		}

		// description
		if (($desc = $input->getOption('description')) !== null) {
			$this->localPackage['description'] = $desc;
		}
		
		// type
		if (($type = $input->getOption('type')) !== null) {
			if (in_array($type, ['app', 'module'])) {
				$this->localPackage['type'] = 'keeko-' . $type;
			}
		}
		
		// license
		if (($license = $input->getOption('license')) !== null) {
			$this->localPackage['license'] = $license;
		}
		
		// author
		if (($author = $input->getOption('author')) !== null 
				&& (!isset($this->localPackage['authors']) || $force)) {
			list($name, $email) = sscanf($author, '%s <%s>');

			$author = [];
			if ($name !== null) {
				$author['name'] = $name;
			}
				
			if ($email !== null) {
				if (substr($email, -1) == '>') {
					$email = substr($email, 0, -1);
				}
				$author['email'] = $email;
			}
				
			if (count($author)) {
				$this->localPackage['authors'] = [$author];
			}
		}
		
		// autoload
		if (($namespace = $input->getOption('namespace')) !== null && !$this->hasAutoload()) {
			if (substr($namespace, -2) !== '\\') {
				$namespace .= '\\';
			}
			$this->package['autoload']['psr-4'][$namespace] = 'src';
		}
		
		$this->manageDependencies();
		
		// KEEKO
		
		// title
		$keeko = $this->getPackageKeeko($type);
		if (($title = $this->getPackageTitle( $type)) !== null) {
			$keeko['title'] = $title;
		}
		
		// class
		if (($classname = $this->getPackageClass($type)) !== null) {
			$keeko['class'] = $classname;
		}
		
		// additions for keeko-module
		if ($type === 'module') {
			
			// slug
			if (($slug = $this->getPackageSlug()) !== null) {
				// validate slug
				if (strpos($slug, '.') === false && strpos($slug, '/') !== false) {
					throw new \Exception('Slug not valid. Must contain a dot(.) and no slash.');
				}
				$keeko['slug'] = $slug;
			}
			
			// default-action
			if (($defaultAction = $this->getPackageDefaultAction()) !== null) {
				$keeko['default-action'] = $defaultAction;
			}
		}

		if ($type !== null) {
			if (!isset($this->localPackage['extra'])) {
				$this->localPackage['extra'] = [];
			}
			$this->localPackage['extra']['keeko'][$type] = $keeko;
		}	
		
		$this->savePackage($this->localPackage);
		$this->generateClass($input);
	}
	
	private function manageDependencies() {
		// add require statements
		$require = isset($this->localPackage['require']) ? $this->localPackage['require'] : [];
		
		if (!isset($require['php'])) {
			$require['php'] = '>=5.4';
		}
		
		if (!isset($require['keeko/composer-installer'])) {
			$require['keeko/composer-installer'] = '*';
		}

		$this->localPackage['require'] = $require;

		// add require dev statements
		$requireDev = isset($this->localPackage['require-dev']) ? $this->localPackage['require-dev'] : [];
		$requireDev['composer/composer'] = '@dev';
		$requireDev['keeko/core'] = 'dev-master';
		$requireDev['propel/propel'] = '@dev';

		$this->localPackage['require-dev'] = $requireDev;
	}

	private function generateClass(InputInterface $input) {
		if (!isset($this->localPackage['extra']) || !isset($this->package['extra']['keeko'])) {
			return;
		}
		
		$type = $this->getPackageType();
		$fqcn = str_replace('\\', '/', $this->package['extra']['keeko'][$type]['class']);
		$classname = basename($fqcn);
		$filename = 'src/' . $classname . '.php';
		$fqcn = str_replace('/', '\\', $fqcn);

		if (!file_exists($filename) || $input->getOption('force')) {
			$class = PhpClass::create($fqcn);
			$class->setDescription($this->package['extra']['keeko'][$type]['title']);
			if ($type === 'module') {
				$class->setParentClassName('AbstractModule');
				$class->addUseStatement('keeko\\core\\module\\AbstractModule');
			} else if ($type === 'app') {
				$class->setParentClassName('AbstractApplication');
				$class->addUseStatement('keeko\\core\\application\\AbstractApplication');
			}
			
			$docblock = $class->getDocblock();
			$docblock->appendTag(new LicenseTag($this->localPackage['license']));
			$this->addAuthors($class, $this->localPackage);
			
			if ($type === 'module') {
				$this->addModuleMethods($class);
			} else if ($type === 'app') {
				$this->addAppMethods($class);
			}
			
			$generator = new CodeFileGenerator();
			$code = $generator->generate($class);
			
			$fs = new Filesystem();
			$fs->dumpFile($filename, $code, 0755);

			$this->writeln(sprintf('Class <info>%s</info> written at <info>%s</info>', $fqcn, $filename));
		}
	}

	private function addAppMethods(PhpClass $class) {
		// public function run(Request $request, $path)
		$class->setMethod(PhpMethod::create('run')
			->addParameter(PhpParameter::create('request')->setType('Request'))
			->addParameter(PhpParameter::create('path')->setType('string'))
		);
		$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
	}
	
	private function addModuleMethods(PhpClass $class) {
		$class->setMethod(PhpMethod::create('install'));
		$class->setMethod(PhpMethod::create('uninstall'));
		$class->setMethod(PhpMethod::create('update')
			->addParameter(PhpParameter::create('from')->setType('mixed'))
			->addParameter(PhpParameter::create('to')->setType('mixed'))
		);
	}
	
	private function getPackageKeeko($type) {
		$extra = isset($this->localPackage['extra']) ? $this->localPackage['extra'] : [];
		$keeko = isset($extra['keeko']) ? $extra['keeko'] : [];
		if (isset($keeko[$type])) {
			$keeko = $keeko[$type];
		} else {
			$keeko = [];
		}
		return $keeko;
	}
	
	private function getPackageSlug() {
		$type = $this->getPackageType();
		if ($type !== 'module') {
			return;
		}

		$input = $this->getInput();
		$keeko = $this->getPackageKeeko('module');
		$slug = $input->getOption('slug');
		$slug = $slug === null && isset($keeko['slug']) ? $keeko['slug'] : $slug;
		
		if ($slug === null) {
			$slug = $this->getSlug($this->localPackage);
		}
		
		return $slug;
	}
	
	private function getPackageDefaultAction() {
		$type = $this->getPackageType();
		if ($type !== 'module') {
			return;
		}
	
		$input = $this->getInput();
		$keeko = $this->getPackageKeeko('module');
		$defaultAction = $input->getOption('default-action');
		$defaultAction = $defaultAction === null && isset($keeko['default-action']) ? $keeko['default-action'] : $defaultAction;
	
		return $defaultAction;
	}

	private function getPackageTitle($type) {
		$input = $this->getInput();
		$keeko = $this->getPackageKeeko($type);
		$title = $input->getOption('title');
		$title = $title === null && isset($keeko['title']) ? $keeko['title'] : $title;
		
		// default value
		if ($title === null) {
			$title = ucwords(str_replace('/', ' ', $input->getOption('name')));
		}
		
		return $title;
	}
	
	private function getPackageClass($type) {
		$input = $this->getInput();
		$keeko = $this->getPackageKeeko($type);
		$classname = $input->getOption('classname');
		$classname = $classname === null && isset($keeko['class']) ? $keeko['class'] : $classname;
	
		// default value
		if ($classname === null) {
			$ns = $input->getOption('namespace');
			$parts = explode('\\', $ns);
			if (count($parts) > 1) {
				$classname = $ns . '\\' . ucfirst($parts[1]);
				
				// suffix
				if ($type === 'module') {
					$classname .= 'Module';
				} else if ($type === 'app') {
					$classname .= 'Application';
				}
			}
		}
	
		return $classname;
	}
	
	private function getPackageType() {
		$input = $this->getInput();
		$type = $input->getOption('type');
		return $type === null && isset($this->localPackage['type']) 
			? str_replace('keeko-', '', $this->localPackage['type']) 
			: $type;
	}
	
	private function getPackageDescription() {
		$input = $this->getInput();
		$desc = $input->getOption('description');
		return $desc === null && isset($this->localPackage['description']) 
			? $this->localPackage['description'] 
			: $desc;
	}
	
	private function getPackageLicense() {
		$input = $this->getInput();
		$license = $input->getOption('license');
		return $license === null && isset($this->localPackage['license']) 
			? $this->localPackage['license'] 
			: $license;
	}
	
	private function hasAutoload() {
		return isset($this->localPackage['autoload']) 
			&& ((isset($this->package['autload']['psr-0']) 
					&& (in_array('src', $this->package['autload']['psr-0']) 
						|| in_array('src/', $this->package['autload']['psr-0'])
					)
				)
				|| (isset($this->package['autload']['psr-4']) 
					&& (in_array('src', $this->package['autload']['psr-4']) 
						|| in_array('src/', $this->package['autload']['psr-4'])
					)
				)
			);
	}
	
	private function validateName($name) {
		if (!preg_match('{^[a-z0-9_.-]+/[a-z0-9_.-]+$}', $name)) {
			throw new \InvalidArgumentException(
				'The package name '.$name.' is invalid, it should be lowercase and have a vendor name, a forward slash, and a package name, matching: [a-z0-9_.-]+/[a-z0-9_.-]+'
			);
		}
	}
	
	private function setAutoload($namespace) {
		if (!isset($this->localPackage['autoload'])) {
			$this->localPackage['autoload'] = [];
		}
	
		$autoload = $this->localPackage['autoload'];
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
		$psr4[$namespace] = 'src';
	
		$autoload['psr-4'] = $psr4;
		$autoload['psr-0'] = $psr0;
	
		if (count($psr0) == 0) {
			unset($autoload['psr-0']);
		}
	
		$this->localPackage['autoload'] = $autoload;
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
