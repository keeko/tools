<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use keeko\tools\generator\BlankHtmlResponseGenerator;
use keeko\tools\generator\BlankJsonResponseGenerator;
use keeko\tools\generator\GeneratorFactory;
use keeko\tools\generator\ModelResponseTraitGenerator;
use keeko\tools\generator\TwigHtmlResponseGenerator;
use keeko\tools\helpers\QuestionHelperTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class GenerateResponseCommand extends AbstractGenerateCommand {

	use QuestionHelperTrait;
	
	protected $traits = [];
	
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
	
	private function generateResponse($actionName) {
		$module = $this->packageService->getModule();
		
		if (!$module->hasAction($actionName)) {
			throw new \RuntimeException(sprintf('action (%s) not found', $actionName));
		}
		
		$input = $this->io->getInput();
		$action = $module->getAction($actionName);
		$modelName = $this->modelService->getModelNameByAction($action);
		$format = $input->getOption('format');
		$template = $input->getOption('template');

		if (!$action->hasResponse($format)) {
			$action->setResponse($format, str_replace(['Action', 'action'], [ucwords($format) . 'Response', 'response'], $action->getClass()));
		}

		// find generator
		$generator = null;
		$type = $this->packageService->getActionType($actionName, $modelName);
		$isModel = $type && $this->modelService->isModelAction($action); 

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
		else if ($format == 'html') {
			$generator = new BlankHtmlResponseGenerator($this->service);
		}
		
		// run generation, if generator was chosen
		if ($generator !== null) {
			/* @var $class PhpClass */
			$class = $generator->generate($action);
			
			// generate json trait
			if ($isModel && $format === 'json') {
				$generator = new ModelResponseTraitGenerator($this->service);
				$trait = $generator->generate($action);
				$class->addTrait($trait);
				
				if (!in_array($trait->getName(), $this->traits)) {
					$this->codegenService->dumpStruct($trait, true);
					$this->traits[] = $trait->getName();
				}
			}

			// write to file
			$this->codegenService->dumpStruct($class, $input->getOption('force'));
		}
	}

}
