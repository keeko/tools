<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpProperty;
use keeko\tools\generator\serializer\base\ModelSerializerTraitGenerator;
use keeko\tools\helpers\QuestionHelperTrait;
use phootwork\file\File;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class GenerateSerializerCommand extends AbstractGenerateCommand {

	use QuestionHelperTrait;
	
	private $twig;

	protected function configure() {
		$this
			->setName('generate:serializer')
			->setDescription('Generates a serializer')
			->addArgument(
				'name',
				InputArgument::OPTIONAL,
				'The name of the action for which the serializer should be generated.'
			)
			->addOption(
				'model',
				'm',
				InputOption::VALUE_OPTIONAL,
				'The model for which the serializer should be generated, when there is no name argument (if ommited all models will be generated)'
			)
		;
		
		$this->configureGenerateOptions();
		
		parent::configure();
	}

	protected function initialize(InputInterface $input, OutputInterface $output) {
		parent::initialize($input, $output);

		$loader = new \Twig_Loader_Filesystem($this->service->getConfig()->getTemplateRoot() . '/serializer');
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
			$modelQuestion = new ConfirmationQuestion('Do you want to generate a serializer based off a model?');
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
		} 
		
		// ask for which action
		else {
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
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->preCheck();
		
		$name = $input->getArgument('name');
		$model = $input->getOption('model');

		// only a specific action
		if ($name) {
			$this->generateAction($name);
		}

		// create action(s) from a model
		else if ($model) {
			$this->generateModel($model);
		}

		// anyway, generate all models
		else {
			foreach ($this->modelService->getModels() as $model) {
				$modelName = $model->getOriginCommonName();
				$input->setOption('model', $modelName);
				$this->generateModel($modelName);
			}
		}
	}

	private function generateModel($modelName) {
		$this->logger->info('Generate Serializer from Model: ' . $modelName);
		$model = $this->modelService->getModel($modelName);

		// trait
		$generator = new ModelSerializerTraitGenerator($this->service);
		$trait = $generator->generate($model);
		$this->codegenService->dumpStruct($trait, true);
		
		// class
		$serializer = new PhpClass(sprintf('%s\\serializer\\%sSerializer', $this->packageService->getNamespace(), $model->getPhpName()));
		$file = new File($this->codegenService->getFilename($serializer));

		// load from file if already exists
		if ($file->exists()) {
			$serializer = PhpClass::fromFile($file->getPathname());
		}
		
		// generate stub if not
		else {
			$serializer->setParentClassName('AbstractSerializer');
			$serializer->addUseStatement('keeko\\framework\\model\\AbstractSerializer');
		}
		
		// add serializer trait and write
		$serializer->addTrait($trait);
		$this->codegenService->dumpStruct($serializer, true);
		
		// add serializer + APIModelInterface on the model
		$class = new PhpClass(str_replace('\\\\', '\\', $model->getNamespace() . '\\' . $model->getPhpName()));
		$file = new File($this->codegenService->getFilename($class));
		if ($file->exists()) {
			$class = PhpClass::fromFile($this->codegenService->getFilename($class));
			$class
				->addUseStatement($serializer->getQualifiedName())
				->addUseStatement('keeko\\framework\\model\\ApiModelInterface')
				->addInterface('ApiModelInterface')
				->setProperty(PhpProperty::create('serializer')
					->setStatic(true)
					->setVisibility('private')
				)
				->setMethod(PhpMethod::create('getSerializer')
					->setStatic(true)
					->setBody($this->twig->render('get-serializer.twig', [
						'class' => $serializer->getName()
					]))
				)
			;
		
			$this->codegenService->dumpStruct($class, true);
		}	
	}
	
	/**
	 * Generates an action.
	 *  
	 * @param string $actionName
	 */
	private function generateAction($actionName) {
		$this->logger->info('Generate Serializer for action: ' . $actionName);
		
		
	}
	
	

}
