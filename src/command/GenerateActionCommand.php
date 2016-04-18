<?php
namespace keeko\tools\command;

use gossi\codegen\model\PhpClass;
use keeko\framework\schema\ActionSchema;
use keeko\framework\utils\NameUtils;
use keeko\tools\generator\action\SkeletonActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipAddActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipReadActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipRemoveActionGenerator;
use keeko\tools\generator\action\ToManyRelationshipUpdateActionGenerator;
use keeko\tools\generator\action\ToOneRelationshipReadActionGenerator;
use keeko\tools\generator\action\ToOneRelationshipUpdateActionGenerator;
use keeko\tools\generator\GeneratorFactory;
use keeko\tools\helpers\QuestionHelperTrait;
use keeko\tools\utils\NamespaceResolver;
use phootwork\lang\Text;
use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class GenerateActionCommand extends AbstractGenerateCommand {

	use QuestionHelperTrait;

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
		
		// check if the dialog can be skipped
		$name = $input->getArgument('name');
		$model = $input->getOption('model');
		
		if ($model !== null) {
			return;
		} else if ($name !== null) {
			$generateModel = false;
		} else {
			$modelQuestion = new ConfirmationQuestion('Do you want to generate an action based off a model?');
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
		} else {
			if ($name === null) {
				$nameQuestion = new Question('What\'s the name for your action (must be a unique identifier)?', '');
				$name = $this->askQuestion($nameQuestion);
				$input->setArgument('name', $name);
			}
			$action = $this->getAction($name);
			
			// ask for title
			$pkgTitle = $action->getTitle();
			$title = $input->getOption('title');
			if ($title === null && !empty($pkgTitle)) {
				$title = $pkgTitle;
			}
			$titleQuestion = new Question('What\'s the title for your action?', $title);
			$title = $this->askQuestion($titleQuestion);
			$input->setOption('title', $title);
			
			// ask for classname
			$pkgClass = $action->getClass();
			$classname = $input->getOption('classname');
			if ($classname === null) {
				if (!empty($pkgClass)) {
					$classname = $pkgClass;
				} else {
					$classname = $this->guessClassname($name);
				}
			}
			$classname = $this->askQuestion(new Question('Classname', $classname));
			$input->setOption('classname', $classname);
			
			// ask for acl
			$acls = $this->getAcl($action);
			$aclQuestion = new Question('ACL (comma separated list, with these options: guest, user, admin)', implode(', ', $acls));
			$acls = $this->askQuestion($aclQuestion);
			$input->setOption('acl', $acls);
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->preCheck();

		$name = $input->getArgument('name');
		$model = $input->getOption('model');

		// generate a skeleton action (or model, if action name belongs to a model)
		if ($name) {
			$action = $this->getAction($name);
			if ($this->modelService->isModelAction($action)) {
				$this->generateModel($this->modelService->getModelNameByAction($action));
			} else {
				$this->generateSkeleton($name);
			}
		}

		// generate an action for a specific model
		else if ($model) {
			$this->generateModel($model);
		}

		// generate actions for all models
		else {
			foreach ($this->modelService->getModels() as $model) {
				$modelName = $model->getOriginCommonName();
				$input->setOption('model', $modelName);
				$this->generateModel($modelName);
			}
		}
		
		$this->packageService->savePackage();
	}

	private function generateModel($modelName) {
		$this->logger->info('Generate Action from Model: ' . $modelName);
		$input = $this->io->getInput();
		$model = $this->modelService->getModel($modelName);

		// generate domain + serializer
		$this->generateDomain($model);
		$this->generateSerializer($model);

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
			$generator = GeneratorFactory::createModelActionGenerator($type, $this->service);
			$class = $generator->generate($action);
			$this->codegenService->dumpStruct($class, true);
		}
		
		// generate relationship actions
		if (!$model->isReadOnly()) {
			$relationships = $this->modelService->getRelationships($model);
				
			// to-one relationships
			foreach ($relationships['one'] as $one) {
				$fk = $one['fk'];
				$this->generateToOneRelationshipActions($model, $fk->getForeignTable(), $fk);
			}
			
			// to-many relationships
			foreach ($relationships['many'] as $many) {
				$fk = $many['fk'];
				$this->generateToManyRelationshipActions($model, $fk->getForeignTable());
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
	 * Generates a domain with trait for the given model
	 * 
	 * @param Table $model
	 */
	private function generateDomain(Table $model) {
		$this->runCommand('generate:domain', [
			'--model' => $model->getOriginCommonName()
		]);
	}
	
	/**
	 * Generates a serializer for the given model
	 *
	 * @param Table $model
	 */
	private function generateSerializer(Table $model) {
		$this->runCommand('generate:serializer', [
			'--model' => $model->getOriginCommonName()
		]);
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
	
	private function guessClassname($name) {
		$namespace = NamespaceResolver::getNamespace('src/action', $this->package);
		return $namespace . '\\' . NameUtils::toStudlyCase($name) . 'Action';
	}
	
	/**
	 * 
	 * @param string $actionName
	 * @return ActionSchema
	 */
	private function getAction($actionName) {
		$action = $this->packageService->getAction($actionName);
		if ($action === null) {
			$action = new ActionSchema($actionName);
			$module = $this->packageService->getModule();
			$module->addAction($action);
		}
		return $action;
	}
	
	private function getAcl(ActionSchema $action) {
		$acls = [];
		$acl = $this->io->getInput()->getOption('acl');
		if ($acl !== null && count($acl) > 0) {
			if (!is_array($acl)) {
				$acl = [$acl];
			}
			foreach ($acl as $group) {
				if (strpos($group, ',') !== false) {
					$groups = explode(',', $group);
					foreach ($groups as $g) {
						$acls[] = trim($g);
					}
				} else {
					$acls[] = $group;
				}
			}
			
			return $acls;
		}
		
		// read default from package
		if (!$action->getAcl()->isEmpty()) {
			return $action->getAcl()->toArray();
		}

		return $acls;
	}
	
	private function generateToOneRelationshipActions(Table $model, Table $foreign, ForeignKey $fk) {
		$module = $this->package->getKeeko()->getModule();
		$fkModelName = $foreign->getPhpName();
		$actionNamePrefix = sprintf('%s-to-%s-relationship', $model->getOriginCommonName(), $foreign->getOriginCommonName());
	
		$generators = [
			'read' => new ToOneRelationshipReadActionGenerator($this->service),
			'update' => new ToOneRelationshipUpdateActionGenerator($this->service)
		];
		$titles = [
			'read' => 'Reads the relationship of {model} to {foreign}',
			'update' => 'Updates the relationship of {model} to {foreign}'
		];
	
		foreach (array_keys($generators) as $type) {
			// generate fqcn
			$className = sprintf('%s%s%sAction', $model->getPhpName(), $fkModelName, ucfirst($type));
			$fqcn = $this->packageService->getNamespace() . '\\action\\' . $className;

			// generate action
			$action = new ActionSchema($actionNamePrefix . '-' . $type);
			$action->addAcl('admin');
			$action->setClass($fqcn);
			$action->setTitle(str_replace(
				['{model}', '{foreign}'],
				[$model->getOriginCommonName(), $foreign->getoriginCommonName()],
				$titles[$type])
			);
			$module->addAction($action);
	
			// generate class
			$generator = $generators[$type];
			$class = $generator->generate(new PhpClass($fqcn), $model, $foreign, $fk);
			$this->codegenService->dumpStruct($class, true);
		}
	}
	
	private function generateToManyRelationshipActions(Table $model, Table $foreign) {
		$module = $this->package->getKeeko()->getModule();
		$fkModelName = $foreign->getPhpName();
		$actionNamePrefix = sprintf('%s-to-%s-relationship', $model->getOriginCommonName(), $foreign->getOriginCommonName());
		
		$generators = [
			'read' => new ToManyRelationshipReadActionGenerator($this->service),
			'update' => new ToManyRelationshipUpdateActionGenerator($this->service),
			'add' => new ToManyRelationshipAddActionGenerator($this->service),
			'remove' => new ToManyRelationshipRemoveActionGenerator($this->service)
		];
		$titles = [
			'read' => 'Reads the relationship of {model} to {foreign}',
			'update' => 'Updates the relationship of {model} to {foreign}',
			'add' => 'Adds {foreign} as relationship to {model}',
			'remove' => 'Removes {foreign} as relationship of {model}'
		];
	
		foreach (array_keys($generators) as $type) {
			// generate fqcn
			$className = sprintf('%s%s%sAction', $model->getPhpName(), $fkModelName, ucfirst($type));
			$fqcn = $this->packageService->getNamespace() . '\\action\\' . $className;
	
			// generate action
			$action = new ActionSchema($actionNamePrefix . '-' . $type);
			$action->addAcl('admin');
			$action->setClass($fqcn);
			$action->setTitle(str_replace(
				['{model}', '{foreign}'],
				[$model->getOriginCommonName(), $foreign->getoriginCommonName()],
				$titles[$type])
			);
			$module->addAction($action);
	
			// generate class
			$generator = $generators[$type];
			$class = $generator->generate(new PhpClass($fqcn), $model, $foreign);
			$this->codegenService->dumpStruct($class, true);
		}
	}

}
