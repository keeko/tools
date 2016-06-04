<?php
namespace keeko\tools\command;

use keeko\tools\ui\EmberUI;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateEmberCommand extends AbstractEmberCommand {

	protected function configure() {
		$this
			->setName('generate:ember')
			->setDescription('Run all ember generators');

		parent::configure();
	}

	protected function interact(InputInterface $input, OutputInterface $output) {
		$ui = new EmberUI($this);
		$ui->show();
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$args = [
			'--package' => $input->getOption('package')
		];

		$this->runCommand('generate:ember:model', $args);
		$this->runCommand('generate:ember:abilities', $args);
		$this->runCommand('generate:ember:serializer', $args);
	}

}