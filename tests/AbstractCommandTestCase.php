<?php
namespace keeko\tools\tests;

use org\bovigo\vfs\vfsStream;
use keeko\tools\KeekoTools;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use keeko\core\schema\PackageSchema;
use Symfony\Component\Console\Input\ArrayInput;
use phootwork\lang\Text;

class AbstractCommandTestCase extends \PHPUnit_Framework_TestCase {
	
	protected $root;
	
	public function setUp() {
		$this->root = vfsStream::setup('root');
		$this->root->chmod(0777);
// 		$this->root->addChild(new vfsStreamDirectory('example'));
// 		$this->root->getChild('example')->chmod(0777);
	}

	protected function loadExample($fixture) {
		vfsStream::copyFromFileSystem(__DIR__ . '/examples/' . $fixture, $this->root);
	}

	protected function getFile($folder, $filename) {
		$filename = __DIR__ . '/' . $folder .'/' . $filename;
		
		if (file_exists($filename)) {
			return file_get_contents($filename);
		}
		
		return '';
	}
	
	protected function getExampleFile($filename) {
		return $this->getFile('examples', $filename);
	}
	
	protected function getFixtureFile($filename) {
		return $this->getFile('fixtures', $filename);
	}
	
	private function assertEqualsFile($expected, $actual) {
		$actual = file_get_contents($actual);
		
		$this->assertEquals($expected, $actual);
	}
	
	public function assertEqualsExample($example, $actual) {
		$this->assertEqualsFile($this->getExampleFile($example), $actual);
	}
	
	public function assertEqualsFixture($fixture, $actual) {
		$this->assertEqualsFile($this->getFixtureFile($fixture), $actual);
	}
	
	protected function runInit($input) {
		return $this->runCommand('init', $input);
	}
	
	protected function runGenerateAction($input) {
		return $this->runCommand('generate:action', $input);
	}
	
	private function runCommand($command, $input) {
		$app = new KeekoTools();
		$cmd = $app->find($command);
		
		$output = new NullOutput();
		$output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
		
		$input = new ArrayInput($this->sanitizeInput($input));
		$input->setInteractive(false);
		
		$cmd->run($input, $output);
		
		$this->assertTrue($this->root->hasChild('composer.json'));
		
		return PackageSchema::fromFile($this->root->getChild('composer.json')->url());
	}
	
	private function sanitizeInput($input) {
		// set workdir to vfs
		if (!isset($input['--workdir'])) {
			$input['--workdir'] = $this->root->url();
		}
		
		// check if at least one argument is present and if not add a blank one
		$hasArgs = false;
		foreach (array_keys($input) as $key) {
			if (!Text::create($key)->startsWith('--')) {
				$hasArgs = true;
			}
		}
		if (!$hasArgs) {
			$input[] = '';
		}
		
		return $input;
	}
	
}