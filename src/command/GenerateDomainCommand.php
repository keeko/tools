<?php
namespace keeko\tools\command;

use keeko\tools\generator\domain\base\ModelDomainTraitGenerator;
use keeko\tools\generator\domain\base\ReadOnlyModelDomainTraitGenerator;
use keeko\tools\generator\domain\ModelDomainGenerator;
use keeko\tools\generator\domain\SkeletonDomainGenerator;
use keeko\tools\helpers\QuestionHelperTrait;
use keeko\tools\ui\DomainUI;
use keeko\tools\utils\NamespaceResolver;
use phootwork\lang\Text;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateDomainCommand extends AbstractKeekoCommand {

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
		
		$ui = new DomainUI($this);
		$ui->show();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->preCheck();
		
		// 1. find out which action(s) to generate
		// 2. generate the information in the package
		// 3. generate the code for the action
		
		$name = $input->getArgument('name');
		$model = $input->getOption('model');

		// generate a skeleton
		if ($name) {
			$this->generateSkeleton($name);
		}

		// create domain for one model
		else if ($model) {
			$this->generateModel($model);
		}

		// generate domain for all models
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
		$generator = new ModelDomainGenerator($this->service);
		$class = $generator->generate($model);
		$this->codegenService->dumpStruct($class, true);
		
		// generate trait
		$generator = $model->isReadOnly()
			? new ReadOnlyModelDomainTraitGenerator($this->service)
			: new ModelDomainTraitGenerator($this->service);
		$trait = $generator->generate($model);
		$this->codegenService->dumpStruct($trait, true);
	}
	
	private function generateSkeleton($name) {
		$this->logger->info('Generate Skeleton Domain: ' . $name);
		$input = $this->io->getInput();

		$className = NamespaceResolver::getNamespace('src/domain', $this->package) . '\\' . $name;
		
		if (!Text::create($className)->endsWith('Domain')) {
			$className .= 'Domain';
		}

		// generate code
		$generator = new SkeletonDomainGenerator($this->service);
		$class = $generator->generate($className);
		$this->codegenService->dumpStruct($class, $input->getOption('force'));
	}
	
}
