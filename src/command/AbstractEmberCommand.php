<?php
namespace keeko\tools\command;

use keeko\tools\command\AbstractKeekoCommand;
use Symfony\Component\Console\Input\InputOption;
use keeko\tools\model\Project;

class AbstractEmberCommand extends AbstractKeekoCommand {
	
	private $prj;
	
	protected function configure() {
		$this->addOption(
			'package',
			'',
			InputOption::VALUE_OPTIONAL,
			'The package from which the models should be generated'
		);
	
		$this->configureGenerateOptions();
	
		parent::configure();
	}
	
	protected function getProject($packageName = null) {
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
				$path = 'vendor/' . $packageName;
				if (!file_exists($path)) {
					throw new \RuntimeException(sprintf('Package (%s) cannot be found', $packageName));
				}
				$this->prj = new Project($path);
			}
		}
		return $this->prj;
	}

}