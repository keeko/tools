<?php
namespace keeko\tools\command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class GenerateActionCommand extends AbstractCodeGenerateCommand {

	protected function configure() {
		$this
			->setName('generate:action')
			->setDescription('Generates code for an action')
			->addArgument(
				'name',
				InputArgument::OPTIONAL,
				'For which action the code should be generated?'
			)
			->addOption(
				'template',
				't',
				InputOption::VALUE_OPTIONAL,
				'The template used to generate this action (if ommited template is guessed)'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$name = $input->getArgument('name');
		
		// only a specific action
		if ($name) {
			$this->generateAction($name, $input, $output);
		}
		
		// anyway all actions
		else {
			$actions = $this->getKeekoModuleActions();
			
			foreach ($actions as $name => $options) {
				$this->generateAction($name, $input, $output);
			}
		}
	}
	
	private function generateAction($name, InputInterface $input, OutputInterface $output) {
		$actions = $this->getKeekoModuleActions();
		
		if (!array_key_exists($name, $actions)) {
			throw new \RuntimeException(sprintf('action (%s) not found'));
		}
		
		$package = $this->getJsonContents();
		
		if (!array_key_exists('name', $package)) {
			throw new \Exception('No package name set - aborting.');
		}
		
		$fs = new Filesystem();
		$loader = new \Twig_Loader_Filesystem($this->templateRoot . '/actions');
		$twig = new \Twig_Environment($loader);
		
		$api = array_key_exists('api', $actions[$name]) && $actions[$name]['api'];
		$classNamePrefix = $name;
		if (array_key_exists('class-prefix', $actions[$name])) {
			$classNamePrefix = $actions[$name]['class-prefix'];
		}

		$commonPath = sprintf('%s/common', $package['name']);
		$actionPath = sprintf('%s/action', $package['name']);
		$commonNamespace = rtrim(str_replace('/', '\\', $commonPath . '/'. dirname(str_replace('\\', '/', $classNamePrefix))), '\\');
		$actionNamespace = rtrim(str_replace('/', '\\', $actionPath . '/'.dirname(str_replace('\\', '/', $classNamePrefix))), '\\');
		
		// action
		$actionName = str_replace('\\', '/', sprintf('%s/%sAction', $actionPath, $classNamePrefix));
		$actionFileName = 'src/'. $actionName . '.php';
		
		$dir = dirname($actionFileName);
		$fs->mkdir($dir, 0755);
		
// 		$template = $this->getTemplate($name, $input->getOption('template'));
		
		$actionContent = $twig->render('action.twig', [
			'class' => basename($actionName),
			'namespace' => $actionNamespace,
			'common' => $commonNamespace,
			'api' => $api
		]);
		
		$fs->dumpFile($actionFileName, $actionContent, 0755);
		
		// trait
		$traitName = str_replace('\\', '/', sprintf('%s/%sActionTrait', $commonPath, $classNamePrefix));
		$traitFileName = 'src/'. $traitName . '.php';
		
		$dir = dirname($actionFileName);
		$fs->mkdir($dir, 0755);
		
		$traitContent = $twig->render('action_trait.twig', [
			'trait' => basename($traitName),
			'namespace' => $commonNamespace
		]);
		
		$fs->dumpFile($traitFileName, $traitContent, 0755);
	}
	
	private function getTemplate($name, $option) {
		if ($option !== null) {
			return $option;
		}
		
		$keywords = ['create', 'delete', 'update'];
		
		foreach ($keywords as $keyword) {
			if (preg_match('/'.$keyword.'/', $name)) {
				return $keyword;
			}
		}
		
		// simple plural detection
		if (substr($name, -1) === 's') {
			return 'list';
		}
		
		return 'read';
	}

}