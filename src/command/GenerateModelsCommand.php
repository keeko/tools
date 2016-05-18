<?php
namespace keeko\tools\command;

use keeko\tools\command\AbstractKeekoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Application;
use Propel\Generator\Command\ModelBuildCommand;
use keeko\tools\utils\NamespaceResolver;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use gossi\codegen\model\PhpClass;
use phootwork\lang\Text;
use phootwork\file\File;

class GenerateModelsCommand extends AbstractKeekoCommand {
	
	protected function configure() {
		$this
			->setName('generate:models')
			->setDescription('Generates propel models');
	
		$this->configureGenerateOptions();
	
		parent::configure();
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		// run propel command
		$app = new Application();
		$cmd = new ModelBuildCommand();
		$app->add($cmd);
		
		$this->runCommand('model:build', [], $app);
		
		// cleanup
		$namespace = $this->modelService->getDatabase()->getNamespace();
		$path = NamespaceResolver::getSourcePath($namespace, $this->package);
		
		$directory = new \RecursiveDirectoryIterator($this->project->getRootPath() . '/' . $path);
		$iterator = new \RecursiveIteratorIterator($directory);
		foreach ($iterator as $fileinfo) {
			if ($fileinfo->getExtension() == 'php') {
				$class = PhpClass::fromFile($fileinfo->getPathname());
				$ns = $class->getNamespace();
				if (!Text::create($ns)->startsWith($namespace)) {
					$file = new File($fileinfo->getPathname());
					$file->delete();
				}
			}
		}
	}
}