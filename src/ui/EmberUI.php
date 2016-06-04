<?php
namespace keeko\tools\ui;

use keeko\tools\ui\AbstractUI;
use Symfony\Component\Console\Question\Question;

class EmberUI extends AbstractUI {

	public function show() {
		$input = $this->io->getInput();
		$package = $input->getOption('package');
		if (empty($package)) {
			$package = $this->getService()->getPackageService()->getPackage()->getFullName();
			$package = $this->askQuestion(new Question('Models from which package', $package));
			$input->setOption('package', $package);
		}
	}
}