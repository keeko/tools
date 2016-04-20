<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use keeko\tools\generator\GeneratorFactory;
use keeko\tools\generator\responder\ApiJsonResponderGenerator;
use keeko\tools\generator\responder\SkeletonHtmlResponderGenerator;
use keeko\tools\generator\responder\SkeletonJsonResponderGenerator;
use keeko\tools\generator\responder\ToManyRelationshipJsonResponderGenerator;
use keeko\tools\generator\responder\ToOneRelationshipJsonResponderGenerator;
use keeko\tools\generator\responder\TwigHtmlResponderGenerator;
use keeko\tools\helpers\QuestionHelperTrait;
use keeko\tools\ui\ResponseUI;
use keeko\tools\utils\NamespaceResolver;
use phootwork\collection\Set;
use phootwork\file\File;
use phootwork\lang\Text;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateResponseCommand extends AbstractKeekoCommand {

	use QuestionHelperTrait;
	
	protected $traits;
	
	protected function configure() {
		$this->traits = new Set();
		
		$this
			->setName('generate:response')
			->setDescription('Generates code for a responder')
			->addArgument(
				'name',
				InputArgument::OPTIONAL,
				'The name of the action, which should be generated. Typically in the form %nomen%-%verb% (e.g. user-create)'
			)
			->addOption(
				'model',
				'm',
				InputOption::VALUE_OPTIONAL,
				'The model for which the response should be generated, when there is no name argument (if ommited all models will be generated)'
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
			->addOption(
				'serializer',
				'',
				InputOption::VALUE_OPTIONAL,
				'The serializer to be used for the json api template'
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
		
		$ui = new ResponseUI($this);
		$ui->show();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->preCheck();
		
		$name = $input->getArgument('name');
		$model = $input->getOption('model');

		// generate responser for a specific action
		if ($name) {
			$this->generateResponder($name);
		}
		
		// generate a responder for a specific model
		else if ($model) {
			$this->generateModel($model);
		}
		
		// generate responders for all models
		else {
			foreach ($this->modelService->getModels() as $model) {
				$this->generateModel($model->getOriginCommonName());
			}
		}
		
		$this->packageService->savePackage();
	}
	
	protected function generateModel($modelName) {
		$model = $this->modelService->getModel($modelName);
		$types = $model->isReadOnly() ? ['read', 'list'] : ['read', 'list', 'create', 'update', 'delete'];
	
		// generate responders for crud actions
		foreach ($types as $type) {
			$actionName = $modelName . '-' . $type;
	
			$this->generateResponder($actionName);
		}
		
		// generate responders for relationships
		if (!$model->isReadOnly()) {
			$types = [
				'one' => ['read', 'update'],
				'many' => ['read', 'add', 'update', 'remove']
			];
			$relationships = $this->modelService->getRelationships($model);
			foreach ($relationships->getAll() as $relationship) {
				$foreignName = $relationship->getForeign()->getOriginCommonName();
				foreach ($types[$relationship->getType()] as $type) {
					$this->generateResponder($modelName . '-to-' . $foreignName . '-relationship-' . $type);
				}
			}
		}
	}
	
	protected function generateResponder($actionName) {
		$this->logger->info('Generate Responder for: ' . $actionName);
		$module = $this->packageService->getModule();
		
		if (!$module->hasAction($actionName)) {
			throw new \RuntimeException(sprintf('action (%s) not found', $actionName));
		}
		
		$input = $this->io->getInput();
		$format = $input->getOption('format');
		$template = $input->getOption('template');
		
		// check if relationship response
		if (Text::create($actionName)->contains('relationship') && $format == 'json') {
			return $this->generateRelationshipResponder($actionName);
		}

		$action = $module->getAction($actionName);
		$modelName = $this->modelService->getModelNameByAction($action);

		if (!$action->hasResponse($format)) {
			$className = str_replace('action', 'responder', $action->getClass());
			$className = preg_replace('/Action$/', ucwords($format) . 'Responder', $className);
			$action->setResponse($format, $className);
		}

		// find generator
		$generator = null;
		$type = $this->packageService->getActionType($actionName, $modelName);
		$isModel = $type && $this->modelService->isModelAction($action); 

		// model given and format is json
		if ($isModel && $format == 'json') {
			$generator = GeneratorFactory::createModelJsonResponderGenerator($type, $this->service);
		}
		
		// json + dump
		else if ($format == 'json' && $template == 'api') {
			$generator = new ApiJsonResponderGenerator($this->service);
			$generator->setSerializer($this->getSerializer());
		}
		
		// blank json
		else if ($format == 'json') {
			$generator = new SkeletonJsonResponderGenerator($this->service);
		}
		
		// html + twig
		else if ($format == 'html' && $template == 'twig') {
			$generator = new TwigHtmlResponderGenerator($this->service);
		}
		
		// blank html as default
		else if ($format == 'html') {
			$generator = new SkeletonHtmlResponderGenerator($this->service);
		}
		
		// run generation, if generator was chosen
		if ($generator !== null) {
			/* @var $class PhpClass */
			$class = $generator->generate($action);

			// write to file
			$file = $this->codegenService->getFile($class);
			$overwrite = !$file->exists() || $input->getOption('force');
			$this->codegenService->dumpStruct($class, $overwrite);
		}
	}

	protected function generateRelationshipResponder($actionName) {
		$module = $this->packageService->getModule();
		$action = $module->getAction($actionName);
		$prefix = substr($actionName, 0, strpos($actionName, 'relationship') + 12);
		$readAction = $module->getAction($prefix . '-read');
		
		// get modules names
		$matches = [];
		preg_match('/([a-z_]+)-to-([a-z_]+)-relationship.*/i', $actionName, $matches);
		$model = $this->modelService->getModel($matches[1]);
		$foreign = $this->modelService->getModel($matches[2]);
		$relationship = $this->modelService->getRelationship($model, $foreign->getOriginCommonName());

		// response class name
		$responder = sprintf('%s\\responder\\%s%sJsonResponder',
			$this->packageService->getNamespace(),
			$model->getPhpName(),
			$foreign->getPhpName()
		);
		
		$many = $module->hasAction($prefix . '-read')
			&& $module->hasAction($prefix . '-update')
			&& $module->hasAction($prefix . '-add')
			&& $module->hasAction($prefix . '-remove')
		;
		$single = $module->hasAction($prefix . '-read')
			&& $module->hasAction($prefix . '-update')
			&& !$many
		;
		
		$generator = null;
		if ($many) {
			$generator = new ToManyRelationshipJsonResponderGenerator($this->service, $relationship);
		} else if ($single) {
			$generator = new ToOneRelationshipJsonResponderGenerator($this->service, $relationship);
		}
		
		if ($generator !== null) {
			$action->setResponse('json', $responder);
			$responder = $generator->generate($readAction);
			$this->codegenService->dumpStruct($responder, true);
		}
	}
	
	private function getSerializer() {
		$input = $this->io->getInput();
		$serializer = $input->getOption('serializer');
		
		if (empty($serializer)) {
			throw new \RuntimeException('No serializer given, please pass --serializer for template');
		}
		
		// check fqcn
		$class = PhpClass::create($serializer);
		if ($class->getQualifiedName() == $serializer) {
			$class->setQualifiedName(NamespaceResolver::getNamespace('src/serializer', $this->package) . 
				'\\' . $serializer);
		}
		
		// check serializer exists
		$file = new File($this->codegenService->getFilename($class));
		if (!$file->exists()) {
			$this->io->writeln(sprintf('<error>Warning:</error> Serializer <info>%s</info> does not exists, please run `keeko generate:serializer %s`', $serializer, $class->getName()));
		}

		return $class->getQualifiedName();
	}
}
