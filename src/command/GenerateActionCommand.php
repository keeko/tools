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
use keeko\tools\helpers\PackageHelperTrait;
use keeko\tools\helpers\ModelHelperTrait;
use keeko\tools\helpers\CodeGeneratorHelperTrait;
use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpTrait;
use gossi\codegen\model\AbstractPhpStruct;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use keeko\tools\helpers\NamespaceResolver;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use keeko\tools\helpers\QuestionHelperTrait;
use Symfony\Component\Console\Question\Question;

class GenerateActionCommand extends AbstractGenerateCommand {
	
	use PackageHelperTrait;
	use ModelHelperTrait;
	use CodeGeneratorHelperTrait;
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
		$module = $this->getKeekoModule();
		if (count($module) == 0) {
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
		if ($generateModel && !($this->getPackageVendor() === 'keeko' && $this->isCoreSchema())) {
			
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
			$action = $this->getKeekoAction($name);
			
			// ask for title
			$title = $input->getOption('title');
			if ($title === null && isset($action['title'])) {
				$title = $action['title'];
			}
			$titleQuestion = new Question('What\'s the title for your action?', $title);
			$title = $this->askQuestion($titleQuestion);
			$input->setOption('title', $title);
			
			// ask for classname
			$classname = $input->getOption('classname');
			if ($classname === null) {
				if (isset($action['class'])) {
					$classname = $action['class'];
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
			$this->generateAction($name, $this->getKeekoAction($name));
		}

		// create action(s) from a model
		else if ($model) {
			$this->generateModel($model);
		}
		
		// if this is a core-module, find the related model
		else if ($this->getPackageVendor() == 'keeko' && $this->isCoreSchema()) {
			$model = $this->getPackageNameWithoutVendor();
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
	
	private function generateModel($model) {
		$this->logger->info('Generate Action from Model: ' . $model);
		$input = $this->getInput();
		if (($type = $type = $input->getOption('type')) !== null) {
			$types = [$type];
		} else {
			$types = ['create', 'read', 'list', 'update', 'delete'];
		}

		foreach ($types as $type) {
			$input->setOption('acl', ['admin']);
			$input->setOption('type', $type);
			$name = $model . '-' . $type;
			$action = $this->getKeekoAction($name);
			if (!isset($action['title'])) {
				$action['title'] = $this->getActionTitle($model, $type);
			}
			$this->generateAction($name, $action);
		}
		
		$input->setOption('type', null);
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
	 * @param string $name
	 */
	private function generateAction($name, $action) {
		$this->logger->info('Generate Action: ' . $name);
		$input = $this->getInput();
		
		if (($title = $input->getOption('title')) !== null) {
			$action['title'] = $title;
		}
		
		if (!isset($action['title'])) {
			throw new \RuntimeException(sprintf('Cannot create action %s, because I am missing a title for it', $name));
		}
		
		if (($classname = $input->getOption('classname')) !== null) {
			$action['class'] = $classname;
		}
		
		// guess classname if there is none set yet
		if (!isset($action['class'])) {
			$action['class'] = $this->guessClassname($name);
		}
		
		// set acl
		$action['acl'] = $this->getAcl($action);
		
		$this->updateAction($name, $action);
		$this->generateCode($name, $action);
	}
	
	private function guessClassname($name) {
		$namespace = NamespaceResolver::getNamespace('src/action', $this->getPackage());
		return $namespace . NameUtils::toStudlyCase($name) . 'Action';
	}
	
	private function getAcl($action) {
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
		if (isset($action['acl'])) {
			return $action['acl'];
		}
		
		return $acls;
	}
	
	/**
	 * Generates code for an action
	 * 
	 * @param string $name
	 * @param array $action
	 */
	private function generateCode($name, $action) {
		$input = $this->getInput();
		$trait = null;
		
		// class
		$class = new PhpClass($action['class']);
		$filename = $this->getFilename($class);
		$traitNs = $class->getNamespace() . '\\base';
		$traitName = $class->getName() . 'Trait';
		$overwrite = false;
		
		// load from reflection, when class exists
		if (file_exists($filename)) {
			// load trait
			$folder = $this->getSourcePath($traitNs);
			if ($folder !== null && file_exists($folder . '/' . $traitName . '.php')) {
				require_once($folder . '/' . $traitName . '.php');
			}

			// load class
			require_once($filename);
			$class = PhpClass::fromReflection(new \ReflectionClass($action['class']));
		} 
		
		// anyway seed required information
		else {
			$class->addUseStatement('keeko\\core\\action\\AbstractAction');
			$class->setParentClassName('AbstractAction');
			$class->setDescription($action['title']);
			$class->setLongDescription(isset($action['description']) ? $action['description'] : '');
			$this->addAuthors($class, $this->getPackage());
		}
		
		// create base trait
		if ($input->getOption('model') !== null) {
			$trait = PhpTrait::create($traitNs . '\\' . $traitName)
				->addUseStatement('Symfony\\Component\\HttpFoundation\\Request')
				->addUseStatement('Symfony\\Component\\HttpFoundation\\Response')
				->setDescription('Base methods for ' . $action['title'])
				->setLongDescription('This code is automatically created');
			
			$this->addAuthors($trait, $this->getPackage());
			$this->generateModelRunMethod($trait, $name);
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
	
	private function generateModelRunMethod(AbstractPhpStruct $struct, $name) {
		$loader = new \Twig_Loader_Filesystem($this->templateRoot . '/actions');
		$twig = new \Twig_Environment($loader);
		
		$body = '';
		$input = $this->getInput();
		$module = $this->getKeekoModule();
		$database = $this->getDatabase();
		$model = $this->getModelNameByActionName($name);
		$modelName = $this->getModel($model)->getPhpName();
		
		// add model to use statements
		$namespace = $database->getNamespace();
		$nsModelName = $namespace . '\\' . $modelName;
		$struct->addUseStatement($nsModelName);

		switch ($input->getOption('type')) {
			case 'create':
				$struct->addUseStatement('keeko\\core\\exceptions\\ValidationException');
				$struct->addUseStatement('keeko\\core\\utils\\HydrateUtils');

				$body = $twig->render('create-run.twig', [
					'model' => $model,
					'class' => $modelName,
					'fields' => $this->getWriteFields($module, $model)
				]);
				break;

			case 'read':
				$struct->addUseStatement($nsModelName . 'Query');
				$struct->addUseStatement('Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException');
				$struct->addUseStatement('Symfony\\Component\\OptionsResolver\\OptionsResolverInterface');
				$struct->setMethod(PhpMethod::create('setDefaultParams')
					->addParameter(PhpParameter::create('resolver')->setType('OptionsResolverInterface'))
					->setBody($twig->render('read-setDefaultParams.twig'))
				);

				$body = $twig->render('read-run.twig', [
					'model' => $model,
					'class' => $modelName
				]);
				break;

			case 'update':
				$struct->addUseStatement($nsModelName . 'Query');
				$struct->addUseStatement('keeko\\core\\exceptions\\ValidationException');
				$struct->addUseStatement('keeko\\core\\utils\\HydrateUtils');
				$struct->addUseStatement('Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException');
				$struct->addUseStatement('Symfony\\Component\\OptionsResolver\\OptionsResolverInterface');
				$struct->setMethod(PhpMethod::create('setDefaultParams')
					->addParameter(PhpParameter::create('resolver')->setType('OptionsResolverInterface'))
					->setBody($twig->render('update-setDefaultParams.twig'))
				);

				$body = $twig->render('read-run.twig', [
					'model' => $model,
					'class' => $modelName,
					'fields' => $this->getWriteFields($module, $model)
				]);
				break;

			case 'delete':
				$struct->addUseStatement($nsModelName . 'Query');
				$struct->addUseStatement('Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException');
				$struct->addUseStatement('Symfony\\Component\\OptionsResolver\\OptionsResolverInterface');
				$struct->setMethod(PhpMethod::create('setDefaultParams')
					->addParameter(PhpParameter::create('resolver')->setType('OptionsResolverInterface'))
					->setBody($twig->render('delete-setDefaultParams.twig'))
				);

				$body = $twig->render('delete-run.twig', [
					'model' => $model,
					'class' => $modelName
				]);
				break;

			case 'list':
				$struct->addUseStatement($nsModelName . 'Query');
				$struct->addUseStatement('Symfony\\Component\\OptionsResolver\\OptionsResolverInterface');
				$struct->setMethod(PhpMethod::create('setDefaultParams')
					->addParameter(PhpParameter::create('resolver')->setType('OptionsResolverInterface'))
					->setBody($twig->render('list-setDefaultParams.twig'))
				);

				$body = $twig->render('list-run.twig', [
					'model' => $model,
					'class' => $modelName
				]);
				break;
		}
		
		$struct->setMethod($this->generateRunMethod($body));
	}
	
	private function generateRunMethod($body = '') {
		return PhpMethod::create('run')
			->setDescription('Automatically generated run method')
			->setType('Response')
			->addParameter(PhpParameter::create('request')->setType('Request'))
			->setBody($body);
	}

}