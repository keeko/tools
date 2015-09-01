<?php
namespace keeko\tools\tests;

use keeko\tools\KeekoTools;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use phootwork\file\File;
use keeko\core\schema\PackageSchema;

class GenerateActionCommandTest extends AbstractCommandTestCase {
	
	public function testUserModule() {
		$this->loadFixture('module-init');
		
	}
}
