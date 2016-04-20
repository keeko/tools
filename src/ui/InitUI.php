<?php
namespace keeko\tools\ui;

use keeko\tools\helpers\InitCommandHelperTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;

class InitUI extends AbstractUI {
	
	use InitCommandHelperTrait;
	
	public function show() {
		$input = $this->io->getInput();
		$output = $this->io->getOutput();
		$formatter = $this->command->getHelperSet()->get('formatter');
		
		// send welcome
		$output->writeln([
			'',
			$formatter->formatBlock('Welcome to the Keeko initializer', 'bg=blue;fg=white', true),
			''
		]);
		$output->writeln([
			'',
			'This command will guide you through creating your Keeko composer package.',
			'',
		]);
		
		// asking for a options
		$type = $this->getType();
		$name = $this->getName();
		$package = $this->getService()->getPackageService()->getPackage();
		$package->setFullName($name);
		
		$input->setOption('name', $name);
		$input->setOption('description', $this->getDescription());
		$input->setOption('author', $this->getAuthor());
		$input->setOption('type', $type);
		$input->setOption('license', $this->getLicense());

		// KEEKO values
		$output->writeln([
			'',
			'Information for Keeko ' . ucfirst($type),
			''
		]);
		
		$input->setOption('title', $this->getTitle());
		$input->setOption('classname', $this->getClass());
	}
	
	private function getName() {
		$input = $this->io->getInput();
		$force = $input->getOption('force');
		$package = $this->getService()->getPackageService()->getPackage();
		
		$name = $this->getPackageName();
		$askName = $name === null;
		if ($name === null) {
			$git = $this->getGitConfig();
			$cwd = realpath('.');
			$name = basename($cwd);
			$name = preg_replace('{(?:([a-z])([A-Z])|([A-Z])([A-Z][a-z]))}', '\\1\\3-\\2\\4', $name);
			$name = strtolower($name);
			$localName = $package->getFullName();
			if (!empty($localName)) {
				$name = $package->getFullName();
			} else if (isset($git['github.user'])) {
				$name = $git['github.user'] . '/' . $name;
			} elseif (!empty($_SERVER['USERNAME'])) {
				$name = $_SERVER['USERNAME'] . '/' . $name;
			} elseif (get_current_user()) {
				$name = get_current_user() . '/' . $name;
			} else {
				// package names must be in the format foo/bar
				$name = $name . '/' . $name;
			}
		} else {
			$this->validateName($name);
		}
		
		// asking for the name
		if ($askName || $force) {
			$name = $this->askQuestion(new Question('Package name (<vendor>/<name>)', $name));
			$this->validateName($name);
		}
		
		return $name;
	}
	
	private function getDescription() {
		$force = $this->io->getInput()->getOption('force');
		$desc = $this->getPackageDescription();
		if ($desc === null || $force) {
			$desc = $this->askQuestion(new Question('Description', $desc));
		}
		return $desc;
	}
	
	private function getAuthor() {
		$input = $this->io->getInput();
		$force = $input->getOption('force');
		$package = $this->getService()->getPackageService()->getPackage();
		$git = $this->getGitConfig();
		$author = null;
		if ($package->getAuthors()->isEmpty() || $force) {
			$author = $input->getOption('author');
			if ($author === null && isset($git['user.name'])) {
				$author = $git['user.name'];
		
				if (isset($git['user.email'])) {
					$author = sprintf('%s <%s>', $git['user.name'], $git['user.email']);
				}
			}
		
			$author = $this->askQuestion(new Question('Author', $author));
		}
		return $author;
	}
	
	private function getType() {
		$force = $this->io->getInput()->getOption('force');
		$type = $this->getPackageType();
		if ($type === null || $force) {
			$types = ['module', 'app'];
			$question = new Question('Package type (module|app)', $type);
			$question->setAutocompleterValues($types);
			$question->setValidator(function($answer) use ($types) {
				if (!in_array($answer, $types)) {
					throw new \RuntimeException('The name of the type should be one of: ' .
						implode(',', $types));
				}
				return $answer;
			});
			$question->setMaxAttempts(2);
			$type = $this->askQuestion($question);
		}
		return $type;
	}
	
	private function getLicense() {
		$force = $this->io->getInput()->getOption('force');
		$license = $this->getPackageLicense();
		if ($license === null || $force) {
			$license = $this->askQuestion(new Question('License', $license));
		}
		return $license;
	}
	
	private function getTitle() {
		$force = $this->io->getInput()->getOption('force');
		$title = $this->getPackageTitle();
		if ($title === null || $force) {
			$title = $this->askQuestion(new Question('Title', $title));		
		}
		return $title;
	}
	
	private function getClass() {
		$force = $this->io->getInput()->getOption('force');
		$classname = $this->getPackageClass();
		if ($classname === null || $force) {
			$classname = $this->askQuestion(new Question('Class', $classname));
		}
		return $classname;
	}
}