<?php
namespace keeko\tools\command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use keeko\tools\builder\ActionTraitBuilder;
use TwigGenerator\Builder\Generator;
use keeko\tools\builder\ActionBuilder;
use CG\Model\PhpClass;
use keeko\tools\utils\NameUtils;
use CG\Model\PhpMethod;
use CG\Model\PhpParameter;
use CG\Core\CodeGenerator;
use gossi\docblock\tags\AuthorTag;
use gossi\docblock\DocBlock;
use Symfony\Component\Console\Command\Command;
use Propel\Generator\Model\Database;

class GenerateActionCommand extends AbstractGenerateCommand {

	protected function configure() {
		$this
			->setName('generate:action')
			->setDescription('Generates code for an action')
		;
		
		self::configureParameters($this);
		
		parent::configure();
	}
	
	public static function configureParameters(Command $command) {
		$command = GenerateInitActionCommand::configureParameters($command);
		return $command
			->addArgument(
				'name',
				InputArgument::OPTIONAL,
				'For which action the code should be generated'
			)
			->addOption(
				'type',
				'',
				InputOption::VALUE_OPTIONAL,
				'The type of this action (list|create|read|update|delete) (if ommited template is guessed from action name)'
			)
		;
	}
	
	public function getOptionKeys() {
		$keys = array_merge(['type'], parent::getOptionKeys());
	
		// get keys from dependent commands
		$command = $this->getApplication()->find('generate:init-action');
	
		return array_merge($keys, $command->getOptionKeys());
	}
	
	public function getArgumentKeys() {
		$keys = array_merge(['name'], parent::getArgumentKeys());
	
		// get keys from dependent commands
		$command = $this->getApplication()->find('generate:init-action');
	
		return array_merge($keys, $command->getArgumentKeys());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->runCommand('generate:init-action', $input, $output);
		
		$name = $input->getArgument('name');

		// only a specific action
		if ($name) {
			$this->generateAction($name, $input, $output);
		}
		
		// anyway all actions
		else {
			$actions = $this->getKeekoActions($input);
			
			foreach (array_keys($actions) as $name) {
				$this->generateAction($name, $input, $output);
			}
		}
	}
	
	private function generateAction($name, InputInterface $input, OutputInterface $output) {
		$actions = $this->getKeekoActions($input);
		
		if (!array_key_exists($name, $actions)) {
			throw new \RuntimeException(sprintf('action (%s) not found', $name));
		}
		
		$package = $this->getPackage($input);
		$module = $this->getKeekoModule($input);
		$propel = $this->getPropelModel($input, $output);
		$action = $actions[$name];
		$model = $this->getModelByName($input, $propel, $name);
		

		// create class
		$class = new PhpClass($action['class']);
		$class->addUseStatement('keeko\\core\\action\\AbstractAction');
		$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
		$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Response');
		$class->setParentClassName('AbstractAction');
		$class->setDescription(isset($action['title']) ? $action['title'] : '');
		$class->setLongDescription(isset($action['description']) ? $action['description'] : '');
		$this->addAuthors($class, $package);
		
		// set up templates
		$loader = new \Twig_Loader_Filesystem($this->templateRoot . '/actions');
		$twig = new \Twig_Environment($loader);
		$type = $this->getType($input, $name, $model);
		
		// template given
		if ($type) {
			$modelName = $propel->getTable($model)->getPhpName();

			// add model to use statements
			$namespace = $propel->getNamespace();
			$nsModelName = $namespace . '\\' . $modelName;
			$class->addUseStatement($nsModelName);

			switch ($type) {
				case 'create':
					$class->addUseStatement('keeko\\core\\exceptions\\ValidationException');
					$class->addUseStatement('keeko\\core\\utils\\HydrateUtils');
						
					$body = $twig->render('create-run.twig', [
						'model' => $model,
						'class' => $modelName,
						'fields' => $this->getWriteFields($module, $propel, $model)
					]);
					break;
				
				case 'read':
					$class->addUseStatement($nsModelName . 'Query');
					$class->addUseStatement('Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException');
					$class->addUseStatement('Symfony\\Component\\OptionsResolver\\OptionsResolverInterface');
					$class->setMethod(PhpMethod::create('setDefaultParams')
						->addParameter(PhpParameter::create('resolver')->setType('OptionsResolverInterface'))
						->setBody($twig->render('read-setDefaultParams.twig'))
					);
				
					$body = $twig->render('read-run.twig', [
						'model' => $model,
						'class' => $modelName
					]);
					break;
						
				case 'update':
					$class->addUseStatement($nsModelName . 'Query');
					$class->addUseStatement('keeko\\core\\exceptions\\ValidationException');
					$class->addUseStatement('keeko\\core\\utils\\HydrateUtils');
					$class->addUseStatement('Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException');
					$class->addUseStatement('Symfony\\Component\\OptionsResolver\\OptionsResolverInterface');
					$class->setMethod(PhpMethod::create('setDefaultParams')
						->addParameter(PhpParameter::create('resolver')->setType('OptionsResolverInterface'))
						->setBody($twig->render('update-setDefaultParams.twig'))
					);
					
					$body = $twig->render('read-run.twig', [
						'model' => $model,
						'class' => $modelName,
						'fields' => $this->getWriteFields($module, $propel, $model)
					]);
					break;
					
				case 'delete':
					$class->addUseStatement($nsModelName . 'Query');
					$class->addUseStatement('Symfony\\Component\\Routing\\Exception\\ResourceNotFoundException');
					$class->addUseStatement('Symfony\\Component\\OptionsResolver\\OptionsResolverInterface');
					$class->setMethod(PhpMethod::create('setDefaultParams')
						->addParameter(PhpParameter::create('resolver')->setType('OptionsResolverInterface'))
						->setBody($twig->render('delete-setDefaultParams.twig'))
					);
					
					$body = $twig->render('delete-run.twig', [
						'model' => $model,
						'class' => $modelName
					]);
					break;
					
				case 'list':
					$class->addUseStatement('Symfony\\Component\\OptionsResolver\\OptionsResolverInterface');
					$class->setMethod(PhpMethod::create('setDefaultParams')
						->addParameter(PhpParameter::create('resolver')->setType('OptionsResolverInterface'))
						->setBody($twig->render('list-setDefaultParams.twig'))
					);
						
					$body = $twig->render('list-run.twig', [
						'model' => $model,
						'class' => $modelName
					]);
					break;
			}
			
		}
		
		// no template given - render a blank template
		else {
			$body = $twig->render('blank-run.twig');
		}

		$run = new PhpMethod('run');
		$run->setDescription('Automatically generated method, will be overridden');
		$run->setType('Response');
		$run->addParameter(PhpParameter::create('request')->setType('Request'));
		$run->setBody($body);
		$class->setMethod($run);
		
		$generator = new CodeGenerator();
		$code = $generator->generateCode($class);

		// write to file
		$folder = $this->getSourcePath($input, $class->getNamespace());
		
		if ($folder !== null) {
			$fileName = str_replace('//', '/', $folder . '/' . $class->getName() . '.php');
			$code = "<?php\n$code\n";
			$fs = new Filesystem();
			$fs->dumpFile($fileName, $code, 0755);

			$this->writeln($output, sprintf('Action <info>%s</info> created in <info>%s</info>', $name, $fileName));
		}
	}
}