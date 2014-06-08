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

class GenerateResponseCommand extends AbstractGenerateCommand {

	protected $abstracts = [];
	
	protected function configure() {
		$this
			->setName('generate:response')
			->setDescription('Generates code for a response')
		;
		
		self::configureParameters($this);

		parent::configure();
	}
	
	public static function configureParameters(Command $command) {
		$command = GenerateActionCommand::configureParameters($command);
		return $command
			->addOption(
				'format',
				'',
				InputOption::VALUE_OPTIONAL,
				'The response format to create',
				'json'
			)
		;
	}
	
	public function getOptionKeys() {
		$keys = array_merge(['format'], parent::getOptionKeys());
	
		// get keys from dependent commands
		$command = $this->getApplication()->find('generate:action');
	
		return array_merge($keys, $command->getOptionKeys());
	}
	
	public function getArgumentKeys() {
		$keys = array_merge([], parent::getArgumentKeys());
	
		// get keys from dependent commands
		$command = $this->getApplication()->find('generate:action');
	
		return array_merge($keys, $command->getArgumentKeys());
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$this->runCommand('generate:action', $input, $output);
		$this->runCommand('generate:init-response', $input, $output);
		
		$name = $input->getArgument('name');

		// only a specific action
		if ($name) {
			$this->generateResponse($name, $input, $output);
		}
		
		// anyway all actions
		else {
			$actions = $this->getKeekoActions($input);
			
			foreach (array_keys($actions) as $name) {
				$this->generateResponse($name, $input, $output);
			}
		}
	}
	
	private function generateResponse($name, InputInterface $input, OutputInterface $output) {
		$actions = $this->getKeekoActions($input);
		
		if (!array_key_exists($name, $actions)) {
			throw new \RuntimeException(sprintf('action (%s) not found', $name));
		}
		
		$package = $this->getPackage($input);
		$module = $this->getKeekoModule($input);
		$propel = $this->getPropelDatabase($input, $output);
		$action = $actions[$name];
		$model = $this->getModelByName($input, $propel, $name);
		$format = $input->getOption('format');
		
		if (!isset($action['response'])) {
			throw new \RuntimeException(sprintf('No responses defined in extra.keeko.module.actions.%s', $name));
		}
		$responses = $action['response'];

		if (!isset($responses[$format])) {
			throw new \RuntimeException(sprintf('No responses defined for format %s', $format));
		}


		// create class
		$class = new PhpClass($responses[$format]);
		$class->addUseStatement('keeko\core\action\AbstractResponse');
		$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Request');
		$class->addUseStatement('Symfony\\Component\\HttpFoundation\\Response');
		$class->setParentClassName('AbstractResponse');
		$class->setDescription(isset($action['title']) ? 'Json Response for ' . $action['title'] : '');
		$class->setLongDescription(isset($action['description']) ? $action['description'] : '');
		$this->addAuthors($class, $package);
		
		// set up templates
		$loader = new \Twig_Loader_Filesystem($this->templateRoot . '/response/' . $format . '/');
		$twig = new \Twig_Environment($loader);
		$type = $this->getType($input, $name, $model);
		
		// template given
		if ($type) {
			$modelName = $propel->getTable($model)->getPhpName();

			// add model to use statements
			$namespace = $propel->getNamespace();
			$nsModelName = $namespace . '\\' . $modelName;
			$class->addUseStatement($nsModelName);
			$class->addUseStatement('Symfony\\Component\\HttpFoundation\\JsonResponse');
			
			// create abstract response
			$abstract = $this->generateAbstractResponse($input, $output, $propel, $module, $model, $modelName, $type, $format, $responses[$format]);
			
			// set the abstract
			$class->removeUseStatement('keeko\core\action\AbstractResponse');
			$class->setParentClassName($abstract->getName());

			// json only at the moment
			switch ($type) {
				case 'create':
				case 'read':		
				case 'update':
				case 'delete':
					$body = $twig->render('dump-model.twig', ['model' => $model]);
					break;
					
				case 'list':
					$body = $twig->render('list-run.twig', [
						'model' => $model,
						'models' => NameUtils::pluralize($model)
					]);
					break;
			}
			
		}
		
		// no template given - render a blank template
		else {
			$body = $twig->render('blank-run.twig');
		}

		// add run method
		$run = new PhpMethod('run');
		$run->setDescription('Automatically generated method, will be overridden');
		$run->setType('Response');
		$run->addParameter(PhpParameter::create('request')->setType('Request'));
		$run->setBody($body);
		$class->setMethod($run);

		// write to file
		$folder = $this->getSourcePath($input, $class->getNamespace());
		
		if ($folder !== null) {
			$generator = new CodeGenerator();
			$fs = new Filesystem();
			
			$code = $generator->generateCode($class);
			$code = "<?php\n$code\n";
			
			$fileName = str_replace('//', '/', $folder . '/' . $class->getName() . '.php');
			$fs->dumpFile($fileName, $code, 0755);

			$this->writeln($output, sprintf('%s response for <info>%s</info> action created in <info>%s</info>', ucfirst($format), $name, $fileName));
		}
	}
	
	protected function generateAbstractResponse(InputInterface $input, OutputInterface $output, Database $propel, $module, $model, $modelName, $template, $format, $actionName) {
		$abstractName = str_replace([ucfirst($format), ucfirst($template)], '', $actionName);
		$abstractName = str_replace('response\\', 'response\\Abstract', $abstractName);
		
		if (isset($this->abstracts[$abstractName])) {
			return $this->abstracts[$abstractName];
		}
		
		$loader = new \Twig_Loader_Filesystem($this->templateRoot . '/response/');
		$twig = new \Twig_Environment($loader);
		
		// add model to use statements
		$namespace = $propel->getNamespace();
		$nsModelName = $namespace . '\\' . $modelName;
		
		$abstract = new PhpClass($abstractName);
		$abstract->setAbstract(true);
		$abstract->setDescription('Abstract Response for ' . $model . ', containing filter functionality.');
		$abstract->setLongDescription('This class is generated automatically, your changes may be overwritten - take care.');
		$abstract->addUseStatement($nsModelName);
		$abstract->addUseStatement('keeko\core\action\AbstractResponse');
		$abstract->addUseStatement('keeko\\core\\utils\\FilterUtils');
		$abstract->addUseStatement('Propel\\Runtime\\Map\\TableMap');
		$abstract->setParentClassName('AbstractResponse');
		$abstract->setMethod(PhpMethod::create('filter')
			->setDescription('Automatically generated method, will be overridden')
			->addParameter(PhpParameter::create($model)->setType('array'))
			->setVisibility('protected')
			->setBody($twig->render('filter.twig', [
				'model' => $model,
				'filter' => $this->arrayToCode($this->getFilter($module, $model, 'read'))
			]))
		);
		$abstract->setMethod(PhpMethod::create($model . 'ToArray')
			->setDescription('Automatically generated method, will be overridden')
			->addParameter(PhpParameter::create($model)->setType($modelName))
			->setVisibility('protected')
			->setBody($twig->render('modelToArray.twig', ['model' => $model]))
		);
		$this->addAuthors($abstract, $this->getPackage($input));
		
		// write to file
		$folder = $this->getSourcePath($input, $abstract->getNamespace());
		if ($folder !== null) {
			$generator = new CodeGenerator();
			$fs = new Filesystem();
			
			$code = $generator->generateCode($abstract);
			$code = "<?php\n$code\n";
			
			$fileName = str_replace('//', '/', $folder . '/' . $abstract->getName() . '.php');
			$fs->dumpFile($fileName, $code, 0755);

			$this->writeln($output, sprintf('Abstract response <info>%s</info> created in <info>%s</info>', $abstract->getName(), $fileName));
		}
		
		$this->abstracts[$abstractName] = $abstract;
		
		return $abstract;
	}
}