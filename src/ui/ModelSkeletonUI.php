<?php
namespace keeko\tools\ui;

use keeko\tools\ui\AbstractUI;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

abstract class ModelSkeletonUI extends AbstractUI {

	public function show() {
		$input = $this->io->getInput();
		$name = $input->getArgument('name');
		$model = $input->getOption('model');
		
		if ($model !== null) {
			return;
		} else if ($name !== null) {
			$generateModel = false;
		} else {
			$label = $this->getLabel();
			$label = (in_array($label[0], ['a', 'e', 'i', 'o', 'u']) ? 'an' : 'a') . ' ' . $label;
			$modelQuestion = new ConfirmationQuestion('Do you want to generate ' . $label . ' based off a model?');
			$generateModel = $this->askConfirmation($modelQuestion);
		}
		
		// ask questions for a model
		if ($generateModel !== false) {
			$modelService = $this->command->getService()->getModelService();
			$schema = str_replace(getcwd(), '', $modelService->getSchema());
			$allQuestion = new ConfirmationQuestion(sprintf('For all models in the schema (%s)?', $schema));
			$allModels = $this->askConfirmation($allQuestion);
		
			if (!$allModels) {
				$modelQuestion = new Question('Which model');
				$modelQuestion->setAutocompleterValues($modelService->getModelNames());
				$model = $this->askQuestion($modelQuestion);
				$input->setOption('model', $model);
			}
		} else {
			$this->showSkeleton();
		}
	}

	abstract protected function showSkeleton();

	abstract protected function getLabel();
}