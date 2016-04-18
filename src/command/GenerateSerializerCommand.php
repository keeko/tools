<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpProperty;
use keeko\tools\generator\serializer\base\ModelSerializerTraitGenerator;
use keeko\tools\generator\serializer\SkeletonSerializerGenerator;
use keeko\tools\helpers\QuestionHelperTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use keeko\tools\utils\NamespaceResolver;
use phootwork\lang\Text;
use keeko\tools\generator\serializer\ModelSerializerGenerator;

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
		if ($generateModel !== false) {
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
		
		// ask question for a skeleton
		else if (empty($name)) {
			// ask for classname
			$name = $this->askQuestion(new Question('Classname', $name));
			$input->setArgument('name', $name);
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->preCheck();
		
		$name = $input->getArgument('name');
		$model = $input->getOption('model');

		// only a specific action
		if ($name) {
			$this->generateSkeleton($name);
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
		
		// generate class
		$generator = new ModelSerializerGenerator($this->service);
		$serializer = $generator->generate($model);
		$this->codegenService->dumpStruct($serializer, true);
		
		// generate trait
		$generator = new ModelSerializerTraitGenerator($this->service);
		$trait = $generator->generate($model);
		$this->codegenService->dumpStruct($trait, true);
		
		// add serializer + ApiModelInterface on the model
		$class = new PhpClass(str_replace('\\\\', '\\', $model->getNamespace() . '\\' . $model->getPhpName()));
		$file = $this->codegenService->getFile($class);
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
	 * Generates a skeleton serializer
	 *  
	 * @param string $name
	 */
	private function generateSkeleton($name) {
		$this->logger->info('Generate Skeleton Serializer: ' . $name);
		$input = $this->io->getInput();

		$className = NamespaceResolver::getNamespace('src/serializer', $this->package) . '\\' . $name;
		
		if (!Text::create($className)->endsWith('Serializer')) {
			$className .= 'Serializer';
		}

		// generate code
		$generator = new SkeletonSerializerGenerator($this->service);
		$class = $generator->generate($className);
		$this->codegenService->dumpStruct($class, $input->getOption('force'));
	}
	
	

}
