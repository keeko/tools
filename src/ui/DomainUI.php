<?php
namespace keeko\tools\ui;

use keeko\tools\ui\ModelSkeletonUI;
use Symfony\Component\Console\Question\Question;

class DomainUI extends ModelSkeletonUI {

	protected function getLabel() {
		return 'domain';
	}
	
	protected function showSkeleton() {
		$input = $this->io->getInput();
		$name = $input->getArgument('name');
		
		// ask for classname
		$name = $this->askQuestion(new Question('Classname', $name));
		$input->setArgument('name', $name);
	}
}