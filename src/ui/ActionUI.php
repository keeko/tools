<?php
namespace keeko\tools\ui;

use keeko\tools\ui\ModelSkeletonUI;
use Symfony\Component\Console\Question\Question;
use keeko\tools\helpers\ActionCommandHelperTrait;

class ActionUI extends ModelSkeletonUI {

	use ActionCommandHelperTrait;
	
	protected function getLabel() {
		return 'action';
	}
	
	protected function showSkeleton() {
		$input = $this->io->getInput();
		$name = $input->getArgument('name');
		
		if ($name === null) {
			$nameQuestion = new Question('What\'s the name for your action (must be a unique identifier)?', '');
			$name = $this->askQuestion($nameQuestion);
			$input->setArgument('name', $name);
		}
		$action = $this->getAction($name);
			
		// ask for title
		$pkgTitle = $action->getTitle();
		$title = $input->getOption('title');
		if ($title === null && !empty($pkgTitle)) {
			$title = $pkgTitle;
		}
		$titleQuestion = new Question('What\'s the title for your action?', $title);
		$title = $this->askQuestion($titleQuestion);
		$input->setOption('title', $title);
			
		// ask for classname
		$pkgClass = $action->getClass();
		$classname = $input->getOption('classname');
		if ($classname === null) {
			if (!empty($pkgClass)) {
				$classname = $pkgClass;
			} else {
				$classname = $this->guessClassname($name);
			}
		}
		$classname = $this->askQuestion(new Question('Classname', $classname));
		$input->setOption('classname', $classname);
			
		// ask for acl
		$acls = $this->getAcl($action);
		$aclQuestion = new Question('ACL (comma separated list, with these options: guest, user, admin)', implode(', ', $acls));
		$acls = $this->askQuestion($aclQuestion);
		$input->setOption('acl', $acls);
	}
}