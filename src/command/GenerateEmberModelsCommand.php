<?php
namespace keeko\tools\command;

use keeko\tools\command\AbstractKeekoCommand;
use keeko\tools\model\Project;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use keeko\tools\generator\ember\EmberModelGenerator;
use keeko\framework\utils\NameUtils;
use keeko\tools\ui\EmberModelsUI;
use phootwork\file\File;

class GenerateEmberModelsCommand extends AbstractKeekoCommand {
	
	private $prj;
	
	protected function configure() {
		$this
			->setName('generate:ember:models')
			->setDescription('Generates ember models')
			->addOption(
				'package',
				'',
				InputOption::VALUE_OPTIONAL,
				'The package from which the models should be generated'
			);
	
		$this->configureGenerateOptions();
	
		parent::configure();
	}
	
	protected function interact(InputInterface $input, OutputInterface $output) {
		$ui = new EmberModelsUI($this);
		$ui->show();
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$project = $this->getProject();
		$package = $project->getPackage();
		if ($package->getType() != 'keeko-module') {
			throw new \RuntimeException('Package must be of type `keeko-module`');
		}
		$module = $project->getPackage()->getKeeko()->getModule();
		$models = $this->getModels();
		$generator = new EmberModelGenerator($this->service, $project);
		$output = $this->io->getOutput();
		
		foreach ($models as $model) {
			$contents = $generator->generate($model);
			$filename = sprintf('%s/ember/app/models/%s/%s.js', $this->project->getRootPath(), 
				$module->getSlug(), NameUtils::dasherize($model->getPhpName()));
			$file = new File($filename);
			$file->write($contents);
			$output->writeln(sprintf('Model <info>%s</info> written at <info>%s</info>', 
				$model->getOriginCommonName(), $filename));
		}
	}
	
	private function getModels() {
		$input = $this->io->getInput();
		$project = $this->getProject();
		if ($project->hasSchemaFile()) {
			$input->setOption('schema', $project->getSchemaFileName());
		}
		
		$models = [];
		$database = $this->modelService->getDatabase();
		foreach ($database->getTables() as $table) {
			if ($table->getNamespace() == $database->getNamespace() && !$table->isCrossRef()) {
				$models[] = $table;
			}
		}
		
		return $models;
	}
	
	private function getProject($packageName = null) {
		if ($this->prj === null) {
			if ($packageName === null) {
				$input = $this->io->getInput();
				$packageName = $input->getOption('package');
				if (empty($packageName)) {
					$packageName = $this->package->getFullName();
				}
			}
			
			if ($this->package->getFullName() == $packageName) {
				$this->prj = $this->project;
			} else {
				$path = $this->getPackagePath($packageName);
				$this->prj = new Project($path);
			}
		}
		return $this->prj;
	}
	
	private function getPackagePath($packageName) {
		if ($this->package->getFullName() == $packageName) {
			return dirname($this->project->getComposerFileName());
		} 
		
		return 'vendor/' . $packageName;
	}
}