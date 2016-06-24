<?php
namespace keeko\tools\command;

use keeko\framework\schema\ActionSchema;
use keeko\tools\generator\action\SkeletonActionGenerator;
use keeko\tools\generator\Types;
use keeko\tools\helpers\ActionCommandHelperTrait;
use keeko\tools\model\Relationship;
use keeko\tools\ui\ActionUI;
use phootwork\lang\Text;
use Propel\Generator\Model\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateActionCommand extends AbstractKeekoCommand {

	use ActionCommandHelperTrait;

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
				'The class name (If ommited, class name will be guessed from action name)',
				null
			)
			->addOption(
				'model',
				'm',
				InputOption::VALUE_OPTIONAL,
				'The model for which the actions should be generated (if ommited all models will be generated)'
			)
			->addOption(
				'title',
				'',
				InputOption::VALUE_OPTIONAL,
				'The title for the generated option'
			)
			->addOption(
				'acl',
				'',
				InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
				'The acl\'s for this action (Options are: guest, user, admin)'
			)
		;

		$this->configureGenerateOptions();

		parent::configure();
	}

	protected function initialize(InputInterface $input, OutputInterface $output) {
		parent::initialize($input, $output);
	}

	/**
	 * Checks whether actions can be generated at all by reading composer.json and verify
	 * all required information are available
	 */
	private function check() {
		$module = $this->packageService->getModule();
		if ($module === null) {
			throw new \DomainException('No module definition found in composer.json - please run `keeko init`.');
		}
	}

	protected function interact(InputInterface $input, OutputInterface $output) {
		$this->check();

		$ui = new ActionUI($this);
		$ui->show();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->check();

		$name = $input->getArgument('name');
		$modelName = $input->getOption('model');

		// generate a skeleton action (or model, if action name belongs to a model)
		if ($name) {
			// stop if action belongs to a model ...
			$action = $this->getAction($name);
			if ($this->modelService->isModelAction($action)) {
				throw new \RuntimeException(sprintf('The action (%s) belongs to a model', $name));
			}

			// ... anyway generate a skeleton action
			$this->generateSkeleton($name);
		}

		// generate an action for a specific model
		else if ($modelName) {
			if (!$this->modelService->hasModel($modelName)) {
				throw new \RuntimeException(sprintf('Model (%s) does not exist.', $modelName));
			}
			$this->generateModel($this->modelService->getModel($modelName));
		}

		// generate actions for all models
		else {
			foreach ($this->modelService->getModels() as $model) {
				$this->generateModel($model);
			}
		}

		$this->packageService->savePackage();
	}

	/**
	 * Generates a skeleton action
	 *
	 * @param string $actionName
	 */
	private function generateSkeleton($actionName) {
		$this->logger->info('Generate Skeleton Action: ' . $actionName);
		$input = $this->io->getInput();

		// generate action
		$action = $this->getAction($actionName);

		// title
		if (($title = $input->getOption('title')) !== null) {
			$action->setTitle($title);
		}

		if (Text::create($action->getTitle())->isEmpty()) {
			throw new \RuntimeException(sprintf('Cannot create action %s, because I am missing a title for it', $actionName));
		}

		// classname
		if (($classname = $input->getOption('classname')) !== null) {
			$action->setClass($classname);
		}

		if (Text::create($action->getClass())->isEmpty()) {
			$action->setClass($this->guessClassname($actionName));
		}

// 		// guess title if there is none set yet
// 		if (Text::create($action->getTitle())->isEmpty()
// 				&& $this->modelService->isModelAction($action)
// 				&& $this->modelService->isCrudAction($action)) {
// 			$modelName = $this->modelService->getModelNameByAction($action);
// 			$type = $this->modelService->getOperationByAction($action);
// 			$action->setTitle($this->getActionTitle($modelName, $type));
// 		}

		// acl
		$action->setAcl($this->getAcl($action));

		// generate code
		$generator = new SkeletonActionGenerator($this->service);
		$class = $generator->generate($action);
		$this->codegenService->dumpStruct($class, $input->getOption('force'));
	}

	/**
	 * Generates actions for a model
	 *
	 * @param Table $model
	 */
	private function generateModel(Table $model) {
		$this->logger->info('Generate Actions from Model: ' . $model->getOriginCommonName());

		// generate action type(s)
		foreach (Types::getModelTypes($model) as $type) {
			$this->generateModelAction($model, $type);
		}

		// generate relationship actions
		if (!$model->isReadOnly()) {
			$relationships = $this->modelService->getRelationships($model);
			foreach ($relationships->getAll() as $relationship) {
				foreach (Types::getRelationshipTypes($relationship) as $type) {
					$this->generateRelationshipAction($relationship, $type);
				}
			}
		}
	}

	/**
	 * Generates a model action
	 *
	 * @param Table $model
	 * @param string $type
	 */
	private function generateModelAction(Table $model, $type) {
		// generate action
		$action = $this->generateAction($model, $type);

		// generate class
		$generator = $this->factory->createModelActionGenerator($type);
		$class = $generator->generate($action);
		$this->codegenService->dumpStruct($class, true);
	}

	/**
	 * Generates a relationship action
	 *
	 * @param Relationship $relationship
	 * @param string $type
	 */
	private function generateRelationshipAction(Relationship $relationship, $type) {
		// generate action
		$action = $this->generateAction($relationship, $type);

		// generate class
		$generator = $this->factory->createRelationshipActionGenerator($type, $relationship);
		$class = $generator->generate($action, $relationship);
		$this->codegenService->dumpStruct($class, true);
	}

	/**
	 * Generates an action
	 *
	 * @param Table|Relationship $object
	 * @param string $type
	 * @return ActionSchema
	 */
	private function generateAction($object, $type) {
		// generators
		$nameGenerator = $this->factory->getActionNameGenerator();
		$classNameGenerator = $this->factory->getActionClassNameGenerator();
		$titleGenerator = $this->factory->getActionTitleGenerator();

		// generate action
		$action = $this->getAction($nameGenerator->generate($type, $object));
		$action->setClass($classNameGenerator->generate($type, $object));
		$action->setTitle($titleGenerator->generate($type, $object));
		$action->addAcl('admin');

		return $action;
	}
}
