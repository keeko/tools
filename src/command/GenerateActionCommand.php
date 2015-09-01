<?php
namespace keeko\tools\command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use keeko\tools\utils\NameUtils;
use gossi\docblock\tags\AuthorTag;
use gossi\docblock\Docblock;
use Symfony\Component\Console\Command\Command;
use Propel\Generator\Model\Database;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpTrait;
use gossi\codegen\model\AbstractPhpStruct;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use keeko\tools\helpers\QuestionHelperTrait;
use Symfony\Component\Console\Question\Question;
use keeko\tools\generator\GeneratorFactory;
use keeko\tools\helpers\PackageServiceTrait;
use keeko\tools\helpers\ModelServiceTrait;
use keeko\tools\helpers\CodeGeneratorServiceTrait;
use keeko\tools\utils\NamespaceResolver;
use keeko\core\schema\ActionSchema;
use phootwork\file\File;

class GenerateActionCommand extends AbstractGenerateCommand {
	
	use ModelServiceTrait;
	use CodeGeneratorServiceTrait;
	use QuestionHelperTrait;

	protected function configure() {
		$this
			->setName('generate:action')
			->setDescription('Generates an action')
			->addArgument(
				'name',
				InputArgument::OPTIONAL,
				'The name of the action, which should be generated. Typically in the form %nomen%-%verb% (e.g. user-create)'
			)
			->addOption(
				'classname',
				'c',
				InputOption::VALUE_OPTIONAL,
				'The main class name (If ommited, class name will be guessed from action name)',
				null
			)
			->addOption(
				'model',
				'm',
				InputOption::VALUE_OPTIONAL,
				'The model for which the actions should be generated, when there is no name argument (if ommited all models will be generated)'
			)
			->addOption(
				'title',
				'',
				InputOption::VALUE_OPTIONAL,
				'The title for the generated option'
			)
			->addOption(
				'type',
				'',
				InputOption::VALUE_OPTIONAL,
				'The type of this action (list|create|read|update|delete) (if ommited template is guessed from action name)'
			)->addOption(
				'acl',
				'',
				InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
				'The acl\s for this action (guest, user and/or admin)'
			)
		;
		
		parent::configure();
	}

	/**
	 * Checks whether actions can be generated at all by reading composer.json and verify
	 * all required information are available
	 */
	private function preCheck() {
		$module = $this->getModule();
		if ($module === null) {
			throw new \DomainException('No module definition found in composer.json - please run `keeko init`.');
		}
	}
	
	protected function interact(InputInterface $input, OutputInterface $output) {
		$this->preCheck();
		
		// check if the dialog can be skipped
		$name = $input->getArgument('name');
		$model = $input->getOption('model');
		
		if ($model !== null) {
			return;
		} else if ($name !== null) {
			$generateModel = false;
		} else {
			$modelQuestion = new ConfirmationQuestion('Do you want to generate an action based off a model?');
			$generateModel = $this->askConfirmation($modelQuestion);
		}
		
		// ask questions for a model
		if ($generateModel && !($this->package->getVendorName() === 'keeko' && $this->isCoreSchema())) {

			$schema = str_replace(getcwd(), '', $this->getSchema());
			$allQuestion = new ConfirmationQuestion(sprintf('For all models in the schema (%s)?', $schema));
			$allModels = $this->askConfirmation($allQuestion);

			if (!$allModels) {
				$modelQuestion = new Question('Which model');
				$modelQuestion->setAutocompleterValues($this->getModelNames());
				$model = $this->askQuestion($modelQuestion);
				$input->setOption('model', $model);
			}
		} else if (!$generateModel) {
			$action = $this->getAction($name);
			
			// ask for title
			$pkgTitle = $action->getTitle();
			$title = $input->getOption('title');
			if ($title === null && !empty($pkgTitle)) {
				$title = $pkgTitle;
			}
			$titleQuestion = new Question('What\'s the title for your action?', $title);
			$title = $this->askQuestion($titleQuestion);
			$input->setOption('title', $title);
			
			// ask for classname
			$pkgClass = $action->getClass();
			$classname = $input->getOption('classname');
			if ($classname === null) {
				if (!empty($pkgClass)) {
					$classname = $pkgClass;
				} else {
					$classname = $this->guessClassname($name);
				}
			}
			$classname = $this->askQuestion(new Question('Classname', $classname));
			$input->setOption('classname', $classname);
			
			// ask for acl
			$acls = $this->getAcl($action);
			$aclQuestion = new Question('ACL (comma separated list, with these options: guest, user, admin)', implode(', ', $acls));
			$acls = $this->askQuestion($aclQuestion);
			$input->setOption('acl', $acls);
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->preCheck();
		
		// 1. find out which action(s) to generate
		// 2. generate the information in the package
		// 3. generate the code for the action
		
		$name = $input->getArgument('name');
		$model = $input->getOption('model');

		// only a specific action
		if ($name) {
			$this->generateAction($name, $this->getAction($name));
		}

		// create action(s) from a model
		else if ($model) {
			$this->generateModel($model);
		}
		
		// if this is a core-module, find the related model
		else if ($this->getVendorName() == 'keeko' && $this->isCoreSchema()) {
			$model = $this->getPackageName();
			if ($this->hasModel($model)) {
				$input->setOption('model', $model);
				$this->generateModel($model);
			} else {
				$this->logger->error('Tried to find model on my own, wasn\'t lucky - please provide model with the --model option');
			}
		}

		// anyway, generate all
		else {
			foreach ($this->getModels() as $model) {
				$this->generateModel($model->getOriginCommonName());
			}
		}
		
		$this->savePackage();
	}

	private function generateModel($modelName) {
		$this->logger->info('Generate Action from Model: ' . $modelName);
		$input = $this->getInput();
		$typeDump = $input->getOption('type');
		if ($typeDump !== null) {
			$types = [$typeDump];
		} else {
			$types = ['create', 'read', 'list', 'update', 'delete'];
		}

		foreach ($types as $type) {
			$input->setOption('acl', ['admin']);
			$input->setOption('type', $type);
			$actionName = $modelName . '-' . $type;
			$action = $this->getAction($actionName);
			$title = $action->getTitle();
			if (empty($title)) {
				$action->setTitle($this->getActionTitle($modelName, $type));
			}
			$this->generateAction($actionName, $action);
		}
		
		$input->setOption('type', $typeDump);
	}
	
	private function getActionTitle($model, $type) {
		switch ($type) {
			case 'list':
				return 'List all ' . NameUtils::pluralize($model);
				
			case 'create':
			case 'read':
			case 'update':
			case 'delete':
				return ucfirst($type) . 's ' . (in_array($model[0], ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a') . ' ' . $model;
		}
	}

	
	/**
	 * Generates an action.
	 *  
	 * @param string $actionName
	 * @param ActionSchema $action the action node from composer.json
	 */
	private function generateAction($actionName, ActionSchema $action) {
		$this->logger->info('Generate Action: ' . $actionName);
		$input = $this->getInput();
		
		if (($title = $input->getOption('title')) !== null) {
			$action->setTitle($title);
		}
		
		if ($action->getTitle() === null) {
			throw new \RuntimeException(sprintf('Cannot create action %s, because I am missing a title for it', $actionName));
		}
		
		if (($classname = $input->getOption('classname')) !== null) {
			$action->setClass($classname);
		}
		
		// guess classname if there is none set yet
		if ($action->getClass() === null) {
			$action->setClass($this->guessClassname($actionName));
		}
		
		// set acl
		$action->setAcl($this->getAcl($action));
		
		// generate code
		$this->generateCode($actionName, $action);
	}
	
	private function guessClassname($name) {
		$namespace = NamespaceResolver::getNamespace('src/action', $this->getPackage());
		return $namespace . NameUtils::toStudlyCase($name) . 'Action';
	}
	
	private function getAcl(ActionSchema $action) {
		$acls = [];
		$acl = $this->getInput()->getOption('acl');
		if ($acl !== null && count($acl) > 0) {
			if (!is_array($acl)) {
				$acl = [$acl];
			}
			foreach ($acl as $group) {
				if (strpos($group, ',') !== false) {
					$groups = explode(',', $group);
					foreach ($groups as $g) {
						$acls[] = trim($g);
					}
				} else {
					$acls[] = $group;
				}
			}
			
			return $acls;
		}
		
		// read default from package
		if (!$action->getAcl()->isEmpty()) {
			return $action->getAcl()->toArray();
		}

		return $acls;
	}
	
	/**
	 * Generates code for an action
	 * 
	 * @param string $name
	 * @param ActionSchema $action
	 */
	private function generateCode($name, ActionSchema $action) {
		$input = $this->getInput();
		$trait = null;
		
		// class
		$class = new PhpClass($action->getClass());
		$filename = $this->getFilename($class);
		$traitNs = $class->getNamespace() . '\\base';
		$traitName = $class->getName() . 'Trait';
		$overwrite = false;
		
		// load from reflection, when class exists
		if (file_exists($filename)) {
			// load trait
			$trait = new PhpTrait($traitNs . '\\' . $traitName);
			$traitFile = new File($this->getFilename($trait));

			if ($traitFile->exists()) {
				require_once($traitFile->getPathname());
			}
		
			// load class
			require_once($filename);
			$class = PhpClass::fromReflection(new \ReflectionClass($action->getClass()));
		}
		
		// anyway seed class information
		else {
			$class->addUseStatement('keeko\\core\\action\\AbstractAction');
			$class->setParentClassName('AbstractAction');
			$class->setDescription($action->getTitle());
			$class->setLongDescription($action->getDescription());
			$this->addAuthors($class, $this->getPackage());
		}
		
		// create base trait
		if ($input->getOption('model') !== null) {
			$generator = GeneratorFactory::createActionTraitGenerator($this->getInput()->getOption('type'), $this->service);
			$trait = $generator->generate($traitNs . '\\' . $traitName, $action);

			$this->addAuthors($trait, $this->getPackage());
			$this->dumpStruct($trait, true);
			
			if (!$class->hasTrait($trait)) {
				$class->addTrait($trait);
				$overwrite = true;
			}
		} else {
			if (!$class->hasMethod('run')) {
				$loader = new \Twig_Loader_Filesystem($this->templateRoot . '/actions');
				$twig = new \Twig_Environment($loader);
				$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
				$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Response');
				$class->setMethod($this->generateRunMethod($twig->render('blank-run.twig')));
				$overwrite = true;
			}
		}

		$this->dumpStruct($class, $overwrite);
	}

}
