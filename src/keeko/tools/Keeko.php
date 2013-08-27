<?php
namespace keeko\tools;

use Symfony\Component\Console\Application;
use keeko\tools\command\GenerateActionCommand;

class Keeko extends Application {

	protected function getDefaultCommands() {
		$cmds = parent::getDefaultCommands();
		$cmds[] = new GenerateActionCommand();
		
		return $cmds;
	}
}