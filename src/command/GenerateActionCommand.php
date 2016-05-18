<?php
namespace keeko\tools\command;

use keeko\framework\schema\ActionSchema;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\action\SkeletonActionGenerator;
use keeko\tools\helpers\ActionCommandHelperTrait;
use keeko\tools\model\Relationship;
use keeko\tools\ui\ActionUI;
use phootwork\lang\Text;
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
				'The main class name (If ommited, class name will be guessed from action name)',
				null
			)
			->addOption(
				'model',
				'm',
				InputOption::VALUE_OPTIONAL,
				'The model for which the actions should be generated, when there is no name argument (if ommited all models will be generated)'
			)
			->addOption(
				'title',
				'',
				InputOption::VALUE_OPTIONAL,
				'The title for the generated option'
			)
			->addOption(
				'type',
				'',
				InputOption::VALUE_OPTIONAL,
				'The type of this action (list|create|read|update|delete) (if ommited template is guessed from action name)'
			)->addOption(
				'acl',
				'',
				InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
				'The acl\s for this action (guest, user and/or admin)'
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
	private function preCheck() {
		$module = $this->packageService->getModule();
		if ($module === null) {
			throw new \DomainException('No module definition found in composer.json - please run `keeko init`.');
		}
	}
	
	protected function interact(InputInterface $input, OutputInterface $output) {
		$this->preCheck();
		
		$ui = new ActionUI($this);
		$ui->show();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->preCheck();

		$name = $input->getArgument('name');
		$model = $input->getOption('model');

		// generate a skeleton action (or model, if action name belongs to a model)
		if ($name) {
// 			$action = $this->getAction($name);
// 			if ($this->modelService->isModelAction($action)) {
// 				$this->generateModel($this->modelService->getModelNameByAction($action));
// 			} else {
				$this->generateSkeleton($name);
// 			}
		}

		// generate an action for a specific model
		else if ($model) {
			$this->generateModel($model);
		}

		// generate actions for all models
		else {
			foreach ($this->modelService->getModelNames() as $modelName) {
				$this->generateModel($modelName);
			}
		}
		
		$this->packageService->savePackage();
	}

	private function generateModel($modelName) {
		$this->logger->info('Generate Actions from Model: ' . $modelName);
		$input = $this->io->getInput();
		$model = $this->modelService->getModel($modelName);

		// generate action type(s)
		$typeDump = $input->getOption('type');
		if ($typeDump !== null) {
			$types = [$typeDump];
		} else {
			$types = ['create', 'read', 'list', 'update', 'delete'];
		}
		
		foreach ($types as $type) {
			$input->setOption('acl', ['admin']);
			$input->setOption('type', $type);
			$actionName = $modelName . '-' . $type;
			
			if ($model->isReadOnly() && in_array($type, ['create', 'update', 'delete'])) {
				$this->logger->info(sprintf('Skip generate Action (%s), because Model (%s) is read-only', $actionName, $modelName));
				continue;
			}
			
			$action = $this->getAction($actionName);
			if (Text::create($action->getTitle())->isEmpty()) {
				$action->setTitle($this->getActionTitle($modelName, $type));
			}
			$action = $this->generateAction($actionName);
			
			// generate code
			$generator = $this->factory->createModelActionGenerator($type);
			$class = $generator->generate($action);
			$this->codegenService->dumpStruct($class, true);
		}
		
		// generate relationship actions
		if (!$model->isReadOnly()) {
			$types = [
				Relationship::ONE_TO_ONE => ['read', 'update'],
				Relationship::ONE_TO_MANY => ['read', 'add', 'update', 'remove'],
				Relationship::MANY_TO_MANY => ['read', 'add', 'update', 'remove']
			];
			$relationships = $this->modelService->getRelationships($model);
			foreach ($relationships->getAll() as $relationship) {
				foreach ($types[$relationship->getType()] as $type) {
					$this->generateRelationshipAction($relationship, $type);
				}
			}
		}
		
		$input->setOption('type', $typeDump);
	}

	private function getActionTitle($modelName, $type) {
		$name = NameUtils::dasherize($modelName);
		switch ($type) {
			case 'list':
				return 'List all ' . NameUtils::pluralize($name);

			case 'create':
			case 'read':
			case 'update':
			case 'delete':
				return ucfirst($type) . 's ' . (in_array($name[0], ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a') . ' ' . $name;
		}
	}
	
	/**
	 * Generates an action.
	 *  
	 * @param string $actionName
	 */
	private function generateSkeleton($actionName) {
		$this->logger->info('Generate Skeleton Action: ' . $actionName);
		$input = $this->io->getInput();
		
		// generate action
		$action = $this->generateAction($actionName);
		
		// generate code
		$generator = new SkeletonActionGenerator($this->service);
		$class = $generator->generate($action);
		$this->codegenService->dumpStruct($class, $input->getOption('force'));
	}
	
	/**
	 * Generates the action for the package
	 * 
	 * @param string $actionName
	 * @throws \RuntimeException
	 * @return ActionSchema
	 */
	private function generateAction($actionName) {
		$input = $this->io->getInput();
		
		// get action and create it if it doesn't exist
		$action = $this->getAction($actionName);
		
		if (($title = $input->getOption('title')) !== null) {
			$action->setTitle($title);
		}
		
		if (Text::create($action->getTitle())->isEmpty()) {
			throw new \RuntimeException(sprintf('Cannot create action %s, because I am missing a title for it', $actionName));
		}
		
		if (($classname = $input->getOption('classname')) !== null) {
			$action->setClass($classname);
		}
		
		// guess classname if there is none set yet
		if (Text::create($action->getClass())->isEmpty()) {
			$action->setClass($this->guessClassname($actionName));
		}
		
		// guess title if there is none set yet
		if (Text::create($action->getTitle())->isEmpty()
				&& $this->modelService->isModelAction($action)
				&& $this->modelService->isCrudAction($action)) {
			$modelName = $this->modelService->getModelNameByAction($action);
			$type = $this->modelService->getOperationByAction($action);
			$action->setTitle($this->getActionTitle($modelName, $type));
		}

		// set acl
		$action->setAcl($this->getAcl($action));
		
		return $action;
	}
	
	private function generateRelationshipAction(Relationship $relationship, $type) {
		$model = $relationship->getModel();
		$module = $this->package->getKeeko()->getModule();
		$relatedName = $relationship->getRelatedName();
		$relatedActionName = NameUtils::toSnakeCase($relationship->getRelatedName());
		$actionNamePrefix = sprintf('%s-to-%s-relationship', $model->getOriginCommonName(), $relatedActionName);
		
		$titles = [
			'read' => 'Reads the relationship of {model} to {related}',
			'update' => 'Updates the relationship of {model} to {related}',
			'add' => 'Adds {related} as relationship to {model}',
			'remove' => 'Removes {related} as relationship of {model}'
		];
		
		// generate fqcn
		$className = sprintf('%s%s%sAction', $model->getPhpName(), $relatedName, ucfirst($type));
		$fqcn = $this->packageService->getNamespace() . '\\action\\' . $className;
		
		// generate action
		$action = new ActionSchema($actionNamePrefix . '-' . $type);
		$action->addAcl('admin');
		$action->setClass($fqcn);
		$action->setTitle(str_replace(
			['{model}', '{related}'],
			[$model->getOriginCommonName(), $relatedActionName],
			$titles[$type]
		));
		$module->addAction($action);
		
		// generate class
		$generator = $this->factory->createActionRelationshipGenerator($type, $relationship);
		$class = $generator->generate($action, $relationship);
		$this->codegenService->dumpStruct($class, true);
	}

}
