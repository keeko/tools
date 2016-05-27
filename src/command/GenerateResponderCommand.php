<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\responder\ApiJsonResponderGenerator;
use keeko\tools\generator\responder\SkeletonHtmlResponderGenerator;
use keeko\tools\generator\responder\SkeletonJsonResponderGenerator;
use keeko\tools\generator\responder\TwigHtmlResponderGenerator;
use keeko\tools\generator\Types;
use keeko\tools\helpers\QuestionHelperTrait;
use keeko\tools\model\Relationship;
use keeko\tools\ui\ResponseUI;
use keeko\tools\utils\NamespaceResolver;
use phootwork\collection\Set;
use phootwork\file\File;
use phootwork\lang\Text;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Propel\Generator\Model\Table;

class GenerateResponderCommand extends AbstractKeekoCommand {

	use QuestionHelperTrait;
	
	protected $generated;
	
	protected function configure() {
		$this->generated = new Set();
		
		$this
			->setName('generate:responder')
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
	private function check() {
		$module = $this->packageService->getModule();
		if ($module === null || count($module->getActionNames()) == 0) {
			throw new \DomainException('No action definition found in composer.json - please run `keeko generate:action`.');
		}
	}

	protected function interact(InputInterface $input, OutputInterface $output) {
		$this->check();
		
		$ui = new ResponseUI($this);
		$ui->show();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->check();
		
		$name = $input->getArgument('name');
		$modelName = $input->getOption('model');

		// generate responser for a specific action
		if ($name) {
			$this->generateResponder($name);
		}
		
		// generate a responder for a specific model
		else if ($modelName) {
			if (!$this->modelService->hasModel($modelName)) {
				throw new \RuntimeException(sprintf('Model (%s) does not exist.', $modelName));
			}
			$this->generateModel($this->modelService->getModel($modelName));
		}
		
		// generate responders for all models
		else {
			foreach ($this->modelService->getModels() as $model) {
				$this->generateModel($model);
			}
		}
		
		$this->packageService->savePackage();
	}
	
	private function generateModel(Table $model) {
		// generate responders for crud actions
		foreach (Types::getModelTypes($model) as $type) {
			$actionName = $this->factory->getActionNameGenerator()->generate($type, $model);
			$this->generateResponder($actionName);
		}
		
		// generate responders for relationships
		if (!$model->isReadOnly()) {
			$relationships = $this->modelService->getRelationships($model);
			foreach ($relationships->getAll() as $relationship) {
				foreach (Types::getRelationshipTypes($relationship) as $type) {
					$actionName = $this->factory->getActionNameGenerator()->generate($type, $relationship);
					$this->generateResponder($actionName);
				}
			}
		}
	}
	
	private function generateResponder($actionName) {
		$this->logger->info('Generate Responder for: ' . $actionName);
		$module = $this->packageService->getModule();
		
		if (!$module->hasAction($actionName)) {
			throw new \RuntimeException(sprintf('action (%s) not found', $actionName));
		}
		
		$input = $this->io->getInput();
		$force = $input->getOption('force');
		$format = $input->getOption('format');
		$template = $input->getOption('template');
		$action = $module->getAction($actionName);
		
		// check if relationship response
		if (Text::create($actionName)->contains('relationship') && $format == 'json') {
			return $this->generateRelationshipResponder($action);
		}
		
		// responder class name
		if (!$action->hasResponder($format)) {
			$namespaceGenerator = $this->factory->getNamespaceGenerator();
			$actionNamespace = $namespaceGenerator->getActionNamespace();
			$className = Text::create($action->getClass())
				->replace($actionNamespace, '')
				->prepend($namespaceGenerator->getResponderNamespaceByFormat($format))
				->toString();
			$className = preg_replace('/Action$/', ucwords($format) . 'Responder', $className);
			$action->setResponder($format, $className);
		}

		// action information
		$parsed = $this->factory->getActionNameGenerator()->parseName($actionName);
		$type = $parsed['type'];
		$isModel = $type && $this->modelService->hasModel($parsed['modelName']);
		
		// find generator
		$generator = null;

		// model given and format is json
		if ($isModel && $format == 'json') {
			$force = true;
			$generator = $this->factory->createModelJsonResponderGenerator($type);
		}
		
		// payload
		else if ($template == 'payload') {
			$generator = $this->factory->createPayloadGenerator($format);
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
			$overwrite = !$file->exists() || $force;
			$this->codegenService->dumpStruct($class, $overwrite);
		}
	}

	private function generateRelationshipResponder(ActionSchema $action) {
		// find relationship
		$parsed = $this->factory->getActionNameGenerator()->parseRelationship($action->getName());
		$model = $this->modelService->getModel($parsed['modelName']);
		$relatedName = NameUtils::dasherize($parsed['relatedName']);
		$relationship = $this->modelService->getRelationship($model, $relatedName);

		if ($relationship === null) {
			return;
		}
		
		// class name
		$className = $this->factory->getResponderClassNameGenerator()->generateJsonRelationshipResponder($relationship);
		$action->setResponder('json', $className);

		// return if already generated
		if ($this->generated->contains($className)) {
			return;
		}
		
		// generate code
		$generator = $this->factory->createRelationshipJsonResponderGenerator($relationship);
		$responder = $generator->generate($action);
		$this->codegenService->dumpStruct($responder, true);
		$this->generated->add($className);
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
