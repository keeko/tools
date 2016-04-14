<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use keeko\tools\generator\GeneratorFactory;
use keeko\tools\helpers\QuestionHelperTrait;
use phootwork\collection\Set;
use phootwork\file\File;
use phootwork\lang\Text;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use keeko\tools\generator\responder\DumpJsonResponderGenerator;
use keeko\tools\generator\responder\BlankJsonResponderGenerator;
use keeko\tools\generator\responder\TwigHtmlResponderGenerator;
use keeko\tools\generator\responder\BlankHtmlResponderGenerator;
use keeko\tools\generator\responder\ToManyRelationshipJsonResponderGenerator;
use keeko\tools\generator\responder\ToOneRelationshipJsonResponderGenerator;

class GenerateResponseCommand extends AbstractGenerateCommand {

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
			$specificQuestion = new ConfirmationQuestion('Do you want to generate a response for a specific action?');
			$specificAction = $this->askConfirmation($specificQuestion);
		}
		
		// ask which action
		if ($specificAction) {
			$names = [];
			$module = $this->packageService->getModule();
			foreach ($module->getActionNames() as $name) {
				$names[] = $name;
			}
			
			$actionQuestion = new Question('Which action');
			$actionQuestion->setAutocompleterValues($names);
			$name = $this->askQuestion($actionQuestion);
			$input->setArgument('name', $name);
		} 
		
		
		// ask which format
		$formatQuestion = new Question('Which format', 'json');
		$formatQuestion->setAutocompleterValues(['json', 'html']);
		$format = $this->askQuestion($formatQuestion);
		$input->setOption('format', $format);
		
		// ask which template
		$templates = [
			'html' => ['twig', 'blank'],
			'json' => ['dump', 'blank']
		];
		
		$suggestions = isset($templates[$format]) ? $templates[$format] : [];
		$default = count($suggestions) ? $suggestions[0] : '';
		$templateQuestion = new Question('Which template', $default);
		$templateQuestion->setAutocompleterValues($suggestions);
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
	
	protected function generateResponse($actionName) {
		$this->logger->info('Generate Response: ' . $actionName);
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
		$overwrite = false;
		$generator = null;
		$type = $this->packageService->getActionType($actionName, $modelName);
		$isModel = $type && $this->modelService->isModelAction($action); 

		// model given and format is json
		if ($isModel && $format == 'json') {
			$generator = GeneratorFactory::createModelJsonResponderGenerator($type, $this->service);
		}
		
		// json + dump
		else if ($format == 'json' && $template == 'dump') {
			$generator = new DumpJsonResponderGenerator($this->service);
		}
		
		// blank json
		else if ($format == 'json') {
			$generator = new BlankJsonResponderGenerator($this->service);
		}
		
		// html + twig
		else if ($format == 'html' && $template == 'twig') {
			$generator = new TwigHtmlResponderGenerator($this->service);
		}
		
		// blank html as default
		else if ($format == 'html') {
			$generator = new BlankHtmlResponderGenerator($this->service);
		}
		
		// run generation, if generator was chosen
		if ($generator !== null) {
			/* @var $class PhpClass */
			$class = $generator->generate($action);

			// write to file
			$file = new File($this->codegenService->getFilename($class));
			if (!$file->exists()) {
				$overwrite = true;
			}
			$overwrite = $overwrite || $input->getOption('force');
			$this->codegenService->dumpStruct($class, $overwrite);
		}
	}
	
	protected function generateRelationshipResponder($actionName) {
		$module = $this->packageService->getModule();
		$action = $module->getAction($actionName);
		$prefix = substr($actionName, 0, strpos($actionName, 'relationship') + 12);
		$readAction = $module->getAction($prefix.'-read');
		
		// get modules names
		$matches = [];
		preg_match('/([a-z_]+)-to-([a-z_]+)-relationship.*/i', $actionName, $matches);
		$model = $this->modelService->getModel($matches[1]);
		$foreign = $this->modelService->getModel($matches[2]);

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
			$generator = new ToManyRelationshipJsonResponderGenerator($this->service, $model, $foreign);
		} else if ($single) {
			$generator = new ToOneRelationshipJsonResponderGenerator($this->service, $model, $foreign);
		}
		
		if ($generator !== null) {
			$action->setResponse('json', $responder);
			$responder = $generator->generate($readAction);
			$this->codegenService->dumpStruct($responder, true);
		}
	}

}
