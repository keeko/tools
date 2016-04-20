<?php
namespace keeko\tools\ui;

use keeko\tools\ui\ModelSkeletonUI;
use Symfony\Component\Console\Question\Question;
use keeko\framework\utils\NameUtils;

class ResponseUI extends ModelSkeletonUI {

	protected function getLabel() {
		return 'responder';
	}
	
	protected function showSkeleton() {
		$packageService = $this->getService()->getPackageService();
		$modelService = $this->getService()->getModelService();
		$input = $this->getService()->getIOService()->getInput();
		$name = $input->getArgument('name');
		
		$names = [];
		$module = $packageService->getModule();
		foreach ($module->getActionNames() as $actionName) {
			$names[] = $actionName;
		}
			
		$actionQuestion = new Question('Which action');
		$actionQuestion->setAutocompleterValues($names);
		$name = $this->askQuestion($actionQuestion);
		$input->setArgument('name', $name);
		
		// ask which format
		$formatQuestion = new Question('Which format', 'json');
		$formatQuestion->setAutocompleterValues(['json', 'html']);
		$format = $this->askQuestion($formatQuestion);
		$input->setOption('format', $format);
		
		// ask which template
		$action = $packageService->getAction($name);
		if (!($format == 'json' && $modelService->isModelAction($action))) {
			$templates = [
				'html' => ['twig', 'blank'],
				'json' => ['api', 'blank']
			];
			
			$suggestions = isset($templates[$format]) ? $templates[$format] : [];
			$default = count($suggestions) ? $suggestions[0] : '';
			$templateQuestion = new Question('Which template', $default);
			$templateQuestion->setAutocompleterValues($suggestions);
			$template = $this->askQuestion($templateQuestion);
			$input->setOption('template', $template);
			
			// aks for serializer
			if ($format == 'json' && $template == 'api') {
				$guessedSerializer = NameUtils::toStudlyCase($name) . 'Serializer';
				$serializerQuestion = new Question('Which format', $guessedSerializer);
				$serializer = $this->askQuestion($serializerQuestion);
				$input->setOption('serializer', $serializer);
			}
		}
	}
}