<?php
namespace keeko\tools\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use CG\Model\PhpClass;
use CG\Model\PhpMethod;
use CG\Model\PhpParameter;
use keeko\tools\utils\NameUtils;
use Symfony\Component\Console\Input\InputArgument;
use Propel\Generator\Model\Table;
use Symfony\Component\Console\Command\Command;

class GenerateInitActionCommand extends AbstractGenerateCommand {
	
	protected function configure() {
		$this
			->setName('generate:init-action')
			->setDescription('Initializes the project\'s actions in composer.json')
		;
		
		self::configureParameters($this);

		parent::configure();
	}
	
	public static function configureParameters(Command $command) {
		$command = GenerateInitCommand::configureParameters($command);
		return $command
			->addOption(
				'model',
				'm',
				InputOption::VALUE_OPTIONAL,
				'The model for which the action should be generated (if ommited all models will be generated)'
			)
			->addOption(
				'namespace',
				'ns',
				InputOption::VALUE_OPTIONAL,
				'The package\'s namespace for the src/ folder (If ommited, the package name is used)',
				null
			)
		;
	}
	
	public function getOptionKeys() {
		$keys = array_merge(['model', 'namespace'], parent::getOptionKeys());
		
		// get keys from dependent commands
		$command = $this->getApplication()->find('generate:init');
		
		return array_merge($keys, $command->getOptionKeys());
	}
	
	public function getArgumentKeys() {
		$keys = array_merge([], parent::getArgumentKeys());
		
		// get keys from dependent commands
		$command = $this->getApplication()->find('generate:init');
		
		return array_merge($keys, $command->getArgumentKeys());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->runCommand('generate:init', $input, $output);
		
		$propel = $this->getPropelModel($input, $output);
		$model = $this->getModel($input, $propel);
		
		// only a specific model
		if ($model) {
			if ($propel->hasTable($model)) {
				$this->initModel($propel->getTable($model), $input, $output);
			} else {
				throw \InvalidArgumentException(sprintf('Model %s not found.', $model));
			}
		}
		
		// anyway all models
		else {
			$models = [];
			
			foreach ($propel->getTables() as $table) {
				if (!$table->isCrossRef()) {
					$models[] = $table;
				}
			}

			foreach ($models as $model) {
				$this->generateAction($model, $input, $output);
			}
		}
	}
	
	protected function initModel(Table $model, InputInterface $input, OutputInterface $output) {
		$actions = $this->getKeekoActions($input);
		$package = $this->getPackage($input);
		$module = $this->getKeekoModule($input);
		
		$modelName = $model->getName();
		$rootNS = $this->getRootNamespace($input);
		$actionNS = str_replace('\\\\', '\\', $rootNS . '\\action');
		$force = $input->hasOption('force');
		
		// LIST action
		$name = $modelName . '-list';
		$title = 'List all ' . NameUtils::pluralize($modelName);
		$actions[$name] = $this->createAction($name, $actionNS, $title, $actions, $force);

		// CREATE action
		$name = $modelName . '-create';
		$title = 'Creates ' . (in_array($modelName[0], ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a') . ' ' . $modelName;
		$actions[$name] = $this->createAction($name, $actionNS, $title, $actions, $force);
		
		// READ action
		$name = $modelName . '-read';
		$title = 'Reads ' . (in_array($modelName[0], ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a') . ' ' . $modelName;
		$actions[$name] = $this->createAction($name, $actionNS, $title, $actions, $force);
		
		// UPDATE action
		$name = $modelName . '-update';
		$title = 'Updates ' . (in_array($modelName[0], ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a') . ' ' . $modelName;
		$actions[$name] = $this->createAction($name, $actionNS, $title, $actions, $force);
		
		// DELETE action
		$name = $modelName . '-delete';
		$title = 'Deletes ' . (in_array($modelName[0], ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a') . ' ' . $modelName;
		$actions[$name] = $this->createAction($name, $actionNS, $title, $actions, $force);
		
		// TODO: Not a good idea here!
		// set default action
		if ($input->getOption('model') === null) {
			if (!isset($module['default-action']) || empty($module['default-action']) || $force) {
				$module['default-action'] = $modelName . '-list';
			}
		}
		
		$module['actions'] = $actions;
		$package['extra']['keeko']['module'] = $module;
		
		$this->saveComposer($package, $input, $output);
	}
	
	protected function createAction($name, $ns, $title, $actions, $force) {
		$className = $ns . '\\' . NameUtils::toStudlyCase($name) . 'Action';
		$action = isset($actions[$name]) ? $actions[$name] : [];
		
		if (!isset($action['class']) || $force) {
			$action['class'] = $className;
		}
		
		if (!isset($action['title']) || $force) {
			$action['title'] = $title;
		}
		
		return $action;
	}

}
