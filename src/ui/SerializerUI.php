<?php
namespace keeko\tools\ui;

use keeko\tools\ui\ModelSkeletonUI;
use Symfony\Component\Console\Question\Question;

class SerializerUI extends ModelSkeletonUI {

	protected function showSkeleton() {
		$input = $this->io->getInput();
		$name = $input->getArgument('name');
		
		// ask for classname
		$name = $this->askQuestion(new Question('Classname', $name));
		$input->setArgument('name', $name);
	}
}