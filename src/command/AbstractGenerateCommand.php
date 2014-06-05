<?php
namespace keeko\tools\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Propel\Generator\Manager\ModelManager;
use Symfony\Component\Console\Output\OutputInterface;
use Propel\Generator\Config\GeneratorConfig;
use Propel\Generator\Model\Database;
use Symfony\Component\Filesystem\Filesystem;
use CG\Model\PhpClass;
use CG\Core\CodeGenerator;
use Symfony\Component\Console\Input\ArrayInput;
use keeko\tools\utils\NameUtils;
use Propel\Generator\Model\Table;
use CG\Model\AbstractPhpStruct;
use gossi\docblock\DocBlock;
use gossi\docblock\tags\AuthorTag;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;

abstract class AbstractGenerateCommand extends Command {

	private $json = null;
	private $schema = null;
	private $propel = null;
	
	protected $templateRoot;
	

	public function __construct($name = null) {
		parent::__construct($name);
		
		$this->templateRoot = __DIR__ . '/../../templates';
	}
	
	protected function configure() {
		$this
			->addOption(
				'schema',
				's',
				InputOption::VALUE_OPTIONAL,
				'Path to the database schema (if ommited, database/schema.xml is used)',
				null
			)
			->addOption(
				'composer-json',
				'',
				InputOption::VALUE_OPTIONAL,
				'Path to the composer.json (if ommited, composer.json from the current directory is used)',
				null
			)
			->addOption(
				'force',
				'f',
				InputOption::VALUE_OPTIONAL,
				'Forces to owerwrite',
				false
			)
		;
	}
	
	public function getOptionKeys() {
		return ['schema', 'composer-json', 'force'];
	}
	
	public function getArgumentKeys() {
		return [];
	}
	
	
	protected function runCommand($name, InputInterface $input, OutputInterface $output) {
		// return whether command has already executed
		$app = $this->getApplication();
		if ($app->commandRan($name)) {
			return;
		}
		
		$command = $app->find($name);
		$options = $command->getOptionKeys();
		$arguments = $command->getArgumentKeys();

		$args = ['command' => $name];
		foreach ($arguments as $key) {
			if ($input->hasArgument($key)) {
				$args[$key] = $input->getArgument($key);
			}
		}
		foreach ($options as $key) {
			if ($input->hasOption($key)) {
				$args['--'.$key] = $input->getOption($key);
			}
		}
		
		
		try {
			$exitCode = $command->run(new ArrayInput($args), $output);
			
			$event = new ConsoleTerminateEvent($command, $input, $output, $exitCode);
			$app->getDispatcher()->dispatch(ConsoleEvents::TERMINATE, $event);
		} catch (\Exception $e) {
			$event = new ConsoleExceptionEvent($command, $input, $output, $e, $event->getExitCode());
			$app->getDispatcher()->dispatch(ConsoleEvents::EXCEPTION, $event);
		
			throw $event->getException();
		}
		
		return $exitCode;
	}
	
	/**
	 * Returns the keeko node from the composer.json extra
	 */
	protected function getKeeko(InputInterface $input) {
		$json = $this->getPackage($input);

		if (!(isset($json['extra']) && isset($json['extra']['keeko']))) {
			throw new \RuntimeException('no extra.keeko node found in composer.json');
		}
		
		return $json['extra']['keeko'];
	}
	
	protected function getKeekoModule(InputInterface $input) {
		$keeko = $this->getKeeko($input);
		
		if (!isset($keeko['module'])) {
			throw new \RuntimeException('no extra.keeko.module node found in composer.json');
		}
		
		return $keeko['module'];
	}
	

	protected function getKeekoActions(InputInterface $input) {
		$module = $this->getKeekoModule($input);
	
		if (!isset($module['actions'])) {
			$module['actions'] = [];
		}
	
		return $module['actions'];
	}
	
	protected function getPackage(InputInterface $input) {
		if ($this->json === null) {
			
			$jsonFile = $this->getComposerFile($input);
			
			if (!file_exists($jsonFile)) {
				throw new \RuntimeException('composer.json not found');
			}
			
			$this->json = json_decode(file_get_contents($jsonFile), true);
			
			if (json_last_error() !== JSON_ERROR_NONE) {
				$error = '';
				switch (json_last_error()) {
					case JSON_ERROR_DEPTH:
						$error = 'Maximum stack depth exceeded';
						break;
					case JSON_ERROR_STATE_MISMATCH:
						$error = 'Underflow or the modes mismatch';
						break;
					case JSON_ERROR_CTRL_CHAR:
						$error = 'Unexpected control character found';
						break;
					case JSON_ERROR_SYNTAX:
						$error = 'Syntax error, malformed JSON';
						break;
					case JSON_ERROR_UTF8:
						$error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
						break;
					default:
						$error = 'Unknown error';
						break;
				}
				throw new \RuntimeException(sprintf('Problem occured while decoding %s: %s', $jsonFile, $error));
			}
		}
		
		return $this->json;
	}
	
	protected function getComposerFile(InputInterface $input) {
		$jsonOpt = $input->getOption('composer-json');
		return $jsonOpt !== null ? $jsonOpt : getcwd() . '/composer.json';
	}
	
	protected function saveComposer($package, InputInterface $input, OutputInterface $output) {
		$jsonFile = $this->getComposerFile($input);
		$contents = json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		$fs = new Filesystem();
		$fs->dumpFile($jsonFile, $contents, 0755);

		$this->writeln($output, sprintf('Package <info>%s</info> updated at <info>%s</info>', $package['name'], $jsonFile));
	}
	
	protected function writeln(OutputInterface $output, $message) {
		$formatter = $this->getHelperSet()->get('formatter');
		$line = $formatter->formatSection($this->getName(), $message);
		$output->writeln($line);
	}
	
	protected function getRootNamespace(InputInterface $input) {
		$ns = $input->getOption('namespace');
		if ($ns === null) {
			$package = $this->getPackage($input);
			
			if (!isset($package['autoload'])) {
				throw new \DomainException(sprintf('No namespace for %s.', $package['name']));
			}
			
			if (!isset($package['autoload']['psr-4'])) {
				throw new \DomainException(sprintf('No psr-4 autoload for %s.', $package['name']));
			}
			
			foreach ($package['autoload']['psr-4'] as $namespace => $path) {
				if ($path === 'src' || $path === 'src/') {
					$ns = $namespace;
					break;
				}
			}
		}
		
		return $ns;
	}
	
	protected function dumpClass(PhpClass $class, InputInterface $input) {
		$generator = new CodeGenerator();
		$code = $generator->generateCode($class);
		
		// write to file
		$folder = $this->getSourcePath($input, $class->getNamespace());
		
		if ($folder !== null) {
			$fileName = str_replace('//', '/', $folder . '/' . $class->getName() . '.php');
			
			if (file_exists($fileName) ? $input->hasOption('force') : true) {
				$code = "<?php\n$code\n";
				$fs = new Filesystem();
				$fs->dumpFile($fileName, $code, 0755);
			}
		
			return $fileName;
		}
		
		return null;
	}

	protected function getSourcePath(InputInterface $input, $namespace) {
		$package = $this->getPackage($input);
		$relativeSourcePath = null;
		
		if (isset($package['autoload'])) {

			// check psr-4 first
			if (isset($package['autoload']['psr-4'])) {
				$relativeSourcePath = $this->getSourcePathFromPsr($namespace . '\\', $package['autoload']['psr-4']);
			}
			
			// anyway check psr-0
			else if ($relativeSourcePath === null && isset($package['autoload']['psr-0'])) {
				$relativeSourcePath = $this->getSourcePathFromPsr($namespace, $package['autoload']['psr-0']);
			}
		}
		
		if ($relativeSourcePath !== null) {
			$jsonFile = $this->getComposerFile($input);
			$projectDir = dirname($jsonFile);
			$sourcePath = str_replace('//', '/', $projectDir . '/' . $relativeSourcePath);
	
			return $sourcePath;
		}
		
		return null;
	}
	
	private function getSourcePathFromPsr($namespace, $psr) {
		// get longest match first
		$match = '';
		foreach (array_keys($psr) as $ns) {
			if (strpos($namespace, $ns) !== false && strlen($ns) > strlen($match)) {
				$match = $ns;
			}
		}
		
		// add tail
		if ($match !== '') {
			$path = $psr[$match];
			
			$tail = str_replace($match, '', $namespace);
			$path .= '/' . str_replace('\\', '/', $tail);
			
			return str_replace('//', '/', $path);
		}
		
		return null;
	}
	
	protected function getSchema(InputInterface $input) {
		if ($this->schema === null) {
			$schema = null;
			$schemas = [
				$input->getOption('schema'),
				getcwd() . '/database/schema.xml',
				getcwd() . '/core/database/schema.xml'
			];
			foreach ($schemas as $path) {
				if (file_exists($path)) {
					$schema = $path;
					break;
				}
			}
			$this->schema = $schema;
		}
		
		return $this->schema;
	}
	
	/**
	 * @return Database
	 */
	protected function getPropelModel(InputInterface $input, OutputInterface $output) {
		if ($this->propel === null) {
			$schema = $this->getSchema($input);
			
			if ($schema === null) {
				throw new \RuntimeException(sprintf('Can\'t find schema at %s', $schema));
			}
			
			$manager = new ModelManager();
			$manager->setSchemas([new \SplFileInfo($schema)]);
			$manager->setGeneratorConfig(new GeneratorConfig([
				'propel.platform.class' => 'MysqlPlatform'
			]));
			$manager->setLoggerClosure(function ($message) use ($input, $output) {
				if ($input->getOption('verbose')) {
					$output->writeln($message);
				}
			});
			$models = $manager->getDataModels();
			
			if (count($models)) {
				$model = $models[0];
				if ($model->hasDatabase('keeko')) {
					$this->propel = $model->getDatabase('keeko', true);
				}
			}
		}
		
		return $this->propel;
	}
	
	protected function getModel(InputInterface $input, Database $propel) {
		$model = $input->getOption('model');
		if ($model == null) {
			$schema = $this->getSchema($input);
			if (strpos($schema, 'core') !== false) {
				$package = $this->getPackage($input);
				$name = substr($package['name'], strpos($package['name'], '/') + 1);
				
				if ($propel->hasTable($name)) {
					$model = $name;
				}
			}
		}
		return $model;
	}

	protected function getModelByName(InputInterface $input, Database $propel, $name) {
		$model = $this->getModel($input, $propel);
		if ($model == null) {
			if (($pos = strpos($name, '-')) !== false) {
				$model = substr($name, 0, $pos);
			}
		}
		return $model;
	}
	
	protected function addAuthors(AbstractPhpStruct $struct, $package) {
		$classDoc = $struct->getDocblock();
		
		if ($classDoc === null) {
			$classDoc = new DocBlock();
		}
		
		// add authors from composer.json
		if (isset($package['authors'])) {
			foreach ($package['authors'] as $author) {
				$authorTag = new AuthorTag();
		
				if (!isset($author['name'])) {
					continue;
				}
		
				$authorTag->setName($author['name']);
		
				if (isset($author['email'])) {
					$authorTag->setEmail($author['email']);
				} else if (isset($author['homepage'])) {
					$authorTag->setEmail($author['homepage']);
				}
		
				$classDoc->appendTag($authorTag);
			}
		}
		
		$struct->setDocblock($classDoc);
	}
	
	protected function getWriteFields($module, $propel, $model) {
		$conversions = $this->getConversions($module, $model, 'write');
		$filter = $this->getFilter($module, $model, 'write');
		$computed = $this->getComputedFields($propel->getTable($model));
		$filter = array_merge($filter, $computed);

		$fields = '';
		$cols = $propel->getTable($model)->getColumns();
		foreach ($cols as $col) {
			$prop = $col->getName();
	
			if (!in_array($prop, $filter)) {
				$fields .= sprintf("'%s'", $prop);
	
				if (isset($conversions[$prop])) {
					$fields .= ' => function($v) {'."\n\t".'return ' . $conversions[$prop] . ';'."\n".'}';
				}

				$fields .= ', ';
			}
		}

		if (strlen($fields) > 0) {
			$fields = substr($fields, 0, -2);
		}
	
		return sprintf('[%s]', $fields);
	}
	
	protected function getReadFields($module, $propel, $model) {
		return $this->getFields($module, $propel, $model, 'read');
	}
	
	protected function getConversions($module, $model, $type) {
		if (isset($module['codegen'])
				&& isset($module['codegen'][$model])
				&& isset($module['codegen'][$model][$type])
				&& isset($module['codegen'][$model][$type]['conversion'])) {
			return $module['codegen'][$model][$type]['conversion'];
		}

		return [];
	}
	
	protected function getFilter($module, $model, $type) {
		if (isset($module['codegen'])
				&& isset($module['codegen'][$model])
				&& isset($module['codegen'][$model][$type])
				&& isset($module['codegen'][$model][$type]['filter'])) {
			return $module['codegen'][$model][$type]['filter'];
		}

		return [];
	}
	
	protected function getComputedFields(Table $table) {
		$fields = [];

		// timestampable
		foreach ($table->getBehaviors() as $behavior) {
			switch ($behavior->getName()) {
				case 'timestampable':
					$fields[] = $behavior->getParameter('create_column');
					$fields[] = $behavior->getParameter('update_column');
					break;

				case 'aggregate_column':
					$fields[] = $behavior->getParameter('name');
					break;
			}
		}

		return $fields;
	}
	
	protected function arrayToCode($array) {
		$fields = '';
		foreach ($array as $item) {
			$fields .= sprintf("'%s', ", $item);
		}
		
		if (strlen($fields) > 0) {
			$fields = substr($fields, 0, -2);
		}
		
		return sprintf('[%s]', $fields);
	}
	
	protected function getType(InputInterface $input, $name, $model) {
		$template = $input->getOption('type');
		if ($template == null) {
			if (($pos = strpos($name, '-')) !== false) {
				$template = substr($name, $pos + 1);
			} 
// 			else if ($model == $name) {
// 				$template = 'read';
// 			}
		}
		return $template;
	}
}