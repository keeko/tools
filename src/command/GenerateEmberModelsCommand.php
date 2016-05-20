<?php
namespace keeko\tools\command;

use keeko\framework\utils\NameUtils;
use keeko\tools\generator\ember\EmberModelGenerator;
use keeko\tools\model\Project;
use keeko\tools\ui\EmberUI;
use phootwork\file\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEmberModelsCommand extends AbstractEmberCommand {

	protected function configure() {
		$this
			->setName('generate:ember:models')
			->setDescription('Generates ember models');
	
		parent::configure();
	}
	
	protected function interact(InputInterface $input, OutputInterface $output) {
		$ui = new EmberUI($this);
		$ui->show();
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$project = $this->getProject();
		$package = $project->getPackage();
		if ($package->getType() != 'keeko-module') {
			throw new \RuntimeException('Package must be of type `keeko-module`');
		}
		$module = $project->getPackage()->getKeeko()->getModule();
		$this->modelService->read($project);
		$models = $this->modelService->getModels();
		$generator = new EmberModelGenerator($this->service, $project);
		$output = $this->io->getOutput();
		
		foreach ($models as $model) {
			$contents = $generator->generate($model);
			$filename = sprintf('%s/ember/app/models/%s/%s.js', $this->project->getRootPath(), 
				str_replace('.', '/', $module->getSlug()), NameUtils::dasherize($model->getPhpName()));
			$file = new File($filename);
			$file->write($contents);
			$output->writeln(sprintf('Model <info>%s</info> written at <info>%s</info>', 
				$model->getOriginCommonName(), $filename));
		}
	}
}