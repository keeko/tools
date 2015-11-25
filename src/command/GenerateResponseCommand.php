<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use keeko\tools\helpers\QuestionHelperTrait;
use keeko\tools\utils\NameUtils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use keeko\tools\generator\GeneratorFactory;
use keeko\tools\generator\BlankJsonResponseGenerator;
use keeko\tools\generator\TwigHtmlResponseGenerator;
use keeko\tools\generator\BlankHtmlResponseGenerator;

class GenerateResponseCommand extends AbstractGenerateCommand {

	use QuestionHelperTrait;
	
	protected $abstracts = [];
	
	protected function configure() {
		$this
			->setName('generate:response')
			->setDescription('Generates code for a response')
			->addArgument(
				'name',
				InputArgument::OPTIONAL,
				'The name of the action, which should be generated. Typically in the form %nomen%-%verb% (e.g. user-create)'
			)
			->addOption(
				'format',
				'',
				InputOption::VALUE_OPTIONAL,
				'The response format to create',
				'json'
			)
			->addOption(
				'template',
				'',
				InputOption::VALUE_OPTIONAL,
				'The template for the body method (blank or twig)',
				'blank'
			)
		;
		
		$this->configureGenerateOptions();

		parent::configure();
	}

	/**
	 * Checks whether actions can be generated at all by reading composer.json and verify
	 * all required information are available
	 */
	private function preCheck() {
		$module = $this->packageService->getModule();
		if ($module === null || count($module->getActionNames()) == 0) {
			throw new \DomainException('No action definition found in composer.json - please run `keeko generate:action`.');
		}
	}

	protected function interact(InputInterface $input, OutputInterface $output) {
		$this->preCheck();
		
		// check if the dialog can be skipped
		$name = $input->getArgument('name');
		$specificAction = false;
		
		if ($name === null) {
			$formatQuestion = new ConfirmationQuestion('Do you want to generate a response for a specific action?');
			$specificAction = $this->askConfirmation($formatQuestion);
		}
		
		// ask which action
		if ($specificAction) {
			$names = [];
			$actions = $this->getKeekoActions();
			foreach (array_keys($actions) as $name) {
				$names[] = $name;
			}
			
			$formatQuestion = new Question('Which action');
			$formatQuestion->setAutocompleterValues($names);
			$name = $this->askQuestion($formatQuestion);
			$input->setArgument('name', $name);
		} 
		
		
		// ask which format
		$formatQuestion = new Question('Which format', 'json');
		$formatQuestion->setAutocompleterValues(['json', 'html']);
		$format = $this->askQuestion($formatQuestion);
		$input->setOption('format', $format);
		
		// ask which template
		$templateQuestion = new Question('Which template', 'blank');
		$templateQuestion->setAutocompleterValues(['blank', 'twig']);
		$template = $this->askQuestion($templateQuestion);
		$input->setOption('template', $template);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->preCheck();
		
		$name = $input->getArgument('name');

		// only a specific action
		if ($name) {
			$this->generateResponse($name);
		}
		
		// anyway all actions
		else {
			$actions = $this->packageService->getModule()->getActionNames();
			
			foreach ($actions as $name) {
				$this->generateResponse($name);
			}
		}
		
		$this->packageService->savePackage();
	}
	
	private function generateResponse($name) {
		$module = $this->packageService->getModule();
		
		if (!$module->hasAction($name)) {
			throw new \RuntimeException(sprintf('action (%s) not found', $name));
		}
		
		$input = $this->io->getInput();
// 		$package = $this->getPackage();
		$database = $this->modelService->getDatabase();
		$action = $module->getAction($name);
		$model = $this->modelService->getModelNameByAction($action);
		$format = $input->getOption('format');
		$template = $input->getOption('template');

		if (!$action->hasResponse($format)) {
			$action->setResponse($format, str_replace(['Action', 'action'], [ucwords($format) . 'Response', 'response'], $action->getClass()));
		}
		
// 		$this->updateAction($name, $action);

		// find generator
		$type = $this->packageService->getActionType($name, $model);
		$tableName = $database->getTablePrefix() . $model;
		$isModel = $type && $this->modelService->hasSchema() && $database->hasTable($tableName); 

		// model given and format is json
		if ($isModel && $format === 'json') {
			$generator = GeneratorFactory::createJsonResponseGenerator($type, $this->service);
		}
		
		// blank json
		else if ($format === 'json') {
			$generator = new BlankJsonResponseGenerator($this->service);
		}
		
		// html + twig
		else if ($format === 'html' && $template == 'twig') {
			$generator = new TwigHtmlResponseGenerator($this->service);
		}
		
		// blank html as default
		else {
			$generator = new BlankHtmlResponseGenerator($this->service);
		}
		
		$class = $generator->generate($action);
		

// 		// create class
// 		$class = new PhpClass($action->getResponse($format));
// 		$class->addUseStatement('keeko\core\action\AbstractResponse');
// 		$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
// 		$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Response');
// 		$class->setParentClassName('AbstractResponse');
// 		$class->setDescription(isset($action['title']) ? ucwords($format) . 'Response for ' . $action['title'] : '');
// 		$class->setLongDescription(isset($action['description']) ? $action['description'] : '');
// 		$this->addAuthors($class, $package);
		
// 		// set up tempaltes
// 		$loader = new \Twig_Loader_Filesystem($this->templateRoot . '/response/' . $format . '/');
// 		$twig = new \Twig_Environment($loader);
// 		$type = $this->getActionType($name, $model);
// 		$tableName = $database->getTablePrefix() . $model;
		
// 		// model given
// 		if ($type && $this->hasSchema() && $database->hasTable($tableName)) {
// 			$modelName = $database->getTable($tableName)->getPhpName();

// 			// add model to use statements
// 			$namespace = $database->getNamespace();
// 			$nsModelName = $namespace . '\\' . $modelName;
// 			$class->addUseStatement($nsModelName);
			
// 			if ($format === 'json') {
// 				$class->addUseStatement('Symfony\\Component\\HttpFoundation\\JsonResponse');
// 			}
			
// 			// create abstract response
// 			$abstract = $this->generateAbstractResponse($model, $modelName, $type, $format, $responses[$format]);
			
// 			// set the abstract
// 			$class->removeUseStatement('keeko\core\action\AbstractResponse');
// 			$class->setParentClassName($abstract->getName());

// 			// json only at the moment
// 			if ($format === 'json') {
// 				switch ($type) {
// 					case 'create':
// 						$body = $twig->render('create-run.twig', ['model' => $model]);
// 						break;

// 					case 'read':
// 					case 'update':
// 					case 'delete':
// 						$body = $twig->render('dump-model.twig', ['model' => $model]);
// 						break;

// 					case 'list':
// 						$body = $twig->render('list-run.twig', [
// 							'model' => $model,
// 							'models' => NameUtils::pluralize($model)
// 						]);
// 						break;
// 				}
// 			} else {
// 				$body = $twig->render('blank-run.twig');
// 			}
// 		}
		
// 		// no model given - render given template
// 		else {
// 			if ($template == 'twig') {
// 				$file = str_replace(['-create', '-edit'], '-form', $name);
// 				$body = $twig->render('twig-run.twig', ['name' => $file]);
// 			} else {
// 				$body = $twig->render('blank-run.twig');
// 			}
// 		}

// 		// add run method
// 		$run = new PhpMethod('run');
// 		$run->setDescription('Automatically generated method, will be overridden');
// 		$run->setType('Response');
// 		$run->addParameter(PhpParameter::create('request')->setType('Request'));
// 		$run->setBody($body);
// 		$class->setMethod($run);

		// write to file
		$this->codegenService->dumpStruct($class, true);
	}
	
	protected function generateAbstractResponse($model, $modelName, $type, $format, $actionName) {
		$database = $this->getDatabase();
		
		$abstractName = str_replace([ucfirst($format), ucfirst($type)], '', $actionName);
		$abstractName = str_replace('response\\', 'response\\Abstract', $abstractName);
		
		if (isset($this->abstracts[$abstractName])) {
			return $this->abstracts[$abstractName];
		}
		
		$loader = new \Twig_Loader_Filesystem($this->templateRoot . '/response/');
		$twig = new \Twig_Environment($loader);
		
		// add model to use statements
		$namespace = $database->getNamespace();
		$nsModelName = $namespace . '\\' . $modelName;
		
		$abstract = new PhpClass($abstractName);
		$abstract->setAbstract(true);
		$abstract->setDescription('Abstract Response for ' . $model . ', containing filter functionality.');
		$abstract->setLongDescription('This class is generated automatically, your changes may be overwritten - take care.');
		$abstract->addUseStatement($nsModelName);
		$abstract->addUseStatement('keeko\core\action\AbstractResponse');
		$abstract->addUseStatement('keeko\\core\\utils\\FilterUtils');
		$abstract->addUseStatement('Propel\\Runtime\\Map\\TableMap');
		$abstract->setParentClassName('AbstractResponse');
		$abstract->setMethod(PhpMethod::create('filter')
			->setDescription('Automatically generated method, will be overridden')
			->addParameter(PhpParameter::create($model)->setType('array'))
			->setVisibility('protected')
			->setBody($twig->render('filter.twig', [
				'model' => $model,
				'filter' => $this->arrayToCode($this->getFilter($model, 'read'))
			]))
		);
		$abstract->setMethod(PhpMethod::create($model . 'ToArray')
			->setDescription('Automatically generated method, will be overridden')
			->addParameter(PhpParameter::create($model)->setType($modelName))
			->setVisibility('protected')
			->setBody($twig->render('modelToArray.twig', ['model' => $model]))
		);
		$this->addAuthors($abstract, $this->getPackage());
		
		// write to file
		$this->dumpStruct($abstract, true);
		
		$this->abstracts[$abstractName] = $abstract;
		
		return $abstract;
	}
}