<?php
namespace keeko\tools\command;

use keeko\framework\schema\ActionSchema;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\domain\DomainGenerator;
use keeko\tools\generator\domain\DomainTraitGenerator;
use keeko\tools\generator\domain\ReadOnlyDomainTraitGenerator;
use keeko\tools\helpers\QuestionHelperTrait;
use keeko\tools\utils\NamespaceResolver;
use phootwork\lang\Text;
use Propel\Generator\Model\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class GenerateDomainCommand extends AbstractGenerateCommand {

	use QuestionHelperTrait;
	
	private $twig;

	protected function configure() {
		$this
			->setName('generate:domain')
			->setDescription('Generates a domain object')
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
		;
		
		$this->configureGenerateOptions();
		
		parent::configure();
	}

	protected function initialize(InputInterface $input, OutputInterface $output) {
		parent::initialize($input, $output);

		$loader = new \Twig_Loader_Filesystem($this->service->getConfig()->getTemplateRoot() . '/domain');
		$this->twig = new \Twig_Environment($loader);
	}

	/**
	 * Checks whether actions can be generated at all by reading composer.json and verify
	 * all required information are available
	 */
	private function preCheck() {
		$module = $this->packageService->getModule();
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
			$modelQuestion = new ConfirmationQuestion('Do you want to generate a domain object based off a model?');
			$generateModel = $this->askConfirmation($modelQuestion);
		}
		
		// ask questions for a model
		if ($generateModel) {
			$schema = str_replace(getcwd(), '', $this->modelService->getSchema());
			$allQuestion = new ConfirmationQuestion(sprintf('For all models in the schema (%s)?', $schema));
			$allModels = $this->askConfirmation($allQuestion);

			if (!$allModels) {
				$modelQuestion = new Question('Which model');
				$modelQuestion->setAutocompleterValues($this->modelService->getModelNames());
				$model = $this->askQuestion($modelQuestion);
				$input->setOption('model', $model);
			}
			
		// ask question for a name
		} else if (!$generateModel) {
			// TODO: What to do here?
// 			$action = $this->getAction($name);
			
// 			// ask for classname
// 			$pkgClass = $action->getClass();
// 			$classname = $input->getOption('classname');
// 			if ($classname === null) {
// 				if (!empty($pkgClass)) {
// 					$classname = $pkgClass;
// 				} else {
// 					$classname = $this->guessClassname($name);
// 				}
// 			}
// 			$classname = $this->askQuestion(new Question('Classname', $classname));
// 			$input->setOption('classname', $classname);

		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->preCheck();
		
		// 1. find out which action(s) to generate
		// 2. generate the information in the package
		// 3. generate the code for the action
		
		$name = $input->getArgument('name');
		$model = $input->getOption('model');

		// only a specific domain
		if ($name) {
// 			$this->generateAction($name);
		}

		// create action(s) from a model
		else if ($model) {
			$this->generateModel($model);
		}

		// anyway, generate all
		else {
			foreach ($this->modelService->getModels() as $model) {
				$modelName = $model->getOriginCommonName();
				$input->setOption('model', $modelName);
				$this->generateModel($modelName);
			}
		}
	}

	private function generateModel($modelName) {
		$this->logger->info('Generate Domain from Model: ' . $modelName);
		$model = $this->modelService->getModel($modelName);
		
		// generate class
		$generator = new DomainGenerator($this->service);
		$class = $generator->generate($model);
		$this->codegenService->dumpStruct($class, true);
		
		// generate trait
		$generator = $model->isReadOnly()
			? new ReadOnlyDomainTraitGenerator($this->service)
			: new DomainTraitGenerator($this->service);
		$trait = $generator->generate($model);
		$this->codegenService->dumpStruct($trait, true);
	}

	/**
	 * Generates a domain with trait for the given model
	 * 
	 * @TODO: Externalize this into its own command and call the command from here 
	 * 
	 * @param Table $model
	 */
	private function generateDomain(Table $model) {
		// generate class
		$generator = new DomainGenerator($this->service);
		$class = $generator->generate($model);
		$this->codegenService->dumpStruct($class, true);
		
		// generate trait
		$generator = $model->isReadOnly()
			? new ReadOnlyDomainTraitGenerator($this->service)
			: new DomainTraitGenerator($this->service);
		$trait = $generator->generate($model);
		$this->codegenService->dumpStruct($trait, true);
	}
	
// 	/**
// 	 * Generates an action.
// 	 *  
// 	 * @param string $actionName
// 	 * @param ActionSchema $action the action node from composer.json
// 	 */
// 	private function generateAction($actionName) {
// 		$this->logger->info('Generate Action: ' . $actionName);
// 		$input = $this->io->getInput();
		
// 		// get action and create it if it doesn't exist
// 		$action = $this->getAction($actionName);
		
// 		if (($title = $input->getOption('title')) !== null) {
// 			$action->setTitle($title);
// 		}

// 		if (Text::create($action->getTitle())->isEmpty()) {
// 			throw new \RuntimeException(sprintf('Cannot create action %s, because I am missing a title for it', $actionName));
// 		}

// 		if (($classname = $input->getOption('classname')) !== null) {
// 			$action->setClass($classname);
// 		}
		
// 		// guess classname if there is none set yet
// 		if (Text::create($action->getClass())->isEmpty()) {
// 			$action->setClass($this->guessClassname($actionName));
// 		}
		
// 		// guess title if there is none set yet
// 		if (Text::create($action->getTitle())->isEmpty() 
// 				&& $this->modelService->isModelAction($action)
// 				&& $this->modelService->isCrudAction($action)) {
// 			$modelName = $this->modelService->getModelNameByAction($action);
// 			$type = $this->modelService->getOperationByAction($action);
// 			$action->setTitle($this->getActionTitle($modelName, $type));
// 		}
		
// 		// set acl
// 		$action->setAcl($this->getAcl($action));
		
// 		// generate code
// 		$this->generateCode($action);
// 	}
	
// 	private function guessClassname($name) {
// 		$namespace = NamespaceResolver::getNamespace('src/action', $this->package);
// 		return $namespace . '\\' . NameUtils::toStudlyCase($name) . 'Action';
// 	}
	
}
