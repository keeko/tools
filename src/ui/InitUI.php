<?php
namespace keeko\tools\ui;

use keeko\framework\schema\PackageSchema;
use keeko\tools\helpers\InitCommandHelperTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;

class InitUI extends AbstractUI {
	
	use InitCommandHelperTrait;
	
	/** @var PackageSchema */
	private $package;

	
	public function show() {
		$input = $this->io->getInput();
		$output = $this->io->getOutput();
		$force = $input->getOption('force');
		$formatter = $this->command->getHelperSet()->get('formatter');
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
		
		$package = $this->getService()->getPackageService()->getPackage();
		
		$name = $this->getPackageName();
		$askName = $name === null;
		if ($name === null) {
			$git = $this->getGitConfig();
			$cwd = realpath(".");
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
			$input->setOption('name', $name);
			$package->setFullName($name);
		}
		
		// asking for a description
		$desc = $this->getPackageDescription();
		if ($desc === null || $force) {
			$desc = $this->askQuestion(new Question('Description', $desc));
			$input->setOption('description', $desc);
		}
		
		// asking for the author
		if ($package->getAuthors()->isEmpty() || $force) {
			$author = $input->getOption('author');
			if ($author === null && isset($git['user.name'])) {
				$author = $git['user.name'];
		
				if (isset($git['user.email'])) {
					$author = sprintf('%s <%s>', $git['user.name'], $git['user.email']);
				}
			}
		
			$author = $this->askQuestion(new Question('Author', $author));
			$input->setOption('author', $author);
		}
		
		// asking for the package type
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
		$input->setOption('type', $type);
		
		// asking for the license
		$license = $this->getPackageLicense();
		if ($license === null || $force) {
			$license = $this->askQuestion(new Question('License', $license));
			$input->setOption('license', $license);
		}

		// KEEKO values
		$output->writeln([
			'',
			'Information for Keeko ' . ucfirst($type),
			''
		]);
		
		// ask for the title
		$title = $this->getPackageTitle();
		if ($title === null || $force) {
			$title = $this->askQuestion(new Question('Title', $title));
			$input->setOption('title', $title);
		}
		
		// ask for the class
		$classname = $this->getPackageClass();
		if ($classname === null || $force) {
			$classname = $this->askQuestion(new Question('Class', $classname));
			$input->setOption('classname', $classname);
		}
	}
}