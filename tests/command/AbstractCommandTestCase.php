<?php
namespace keeko\tools\tests\command;

use keeko\framework\schema\PackageSchema;
use keeko\tools\KeekoTools;
use org\bovigo\vfs\vfsStream;
use phootwork\lang\Text;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractCommandTestCase extends \PHPUnit_Framework_TestCase {
	
	protected $root;
	
	public function setUp() {
		$this->root = vfsStream::setup('root');
		$this->root->chmod(0777);
	}

	protected function loadExample($example) {
		vfsStream::copyFromFileSystem(__DIR__ . '/../examples/' . $example, $this->root);
	}
	
	protected function getCoreSchema() {
		return __DIR__ . '/../../vendor/keeko/core/database/schema.xml';
	}

	protected function getFile($folder, $filename) {
		$filename = __DIR__ . '/../' . $folder .'/' . $filename;

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
	
	protected function runInit($input = []) {
		return $this->runCommand('init', $input);
	}
	
	protected function runGenerateAction($input = []) {
		return $this->runCommand('generate:action', $input);
	}
	
	protected function runGenerateResponse($input = []) {
		return $this->runCommand('generate:response', $input);
	}
	
	protected function runGenerateApi($input = []) {
		return $this->runCommand('generate:api', $input);
	}
	
	protected function runCommand($command, $input = []) {
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