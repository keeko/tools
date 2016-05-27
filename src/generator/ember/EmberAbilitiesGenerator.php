<?php
namespace keeko\tools\generator\ember;

use keeko\tools\model\Relationship;
use Propel\Generator\Model\Table;
use keeko\tools\generator\Types;

class EmberAbilitiesGenerator extends AbstractEmberGenerator {
	
	private $template = 'Ember.computed(function() {
	return this.get(\'session\').hasPermission(\'%s\', \'%s\');
})';
	
	public function generate(Table $model) {
		$class = new EmberClassGenerator('Ability');
		$class->addImport('Ember', 'ember');
		$class->addImport('{ Ability }', 'ember-can');

		// actions
		$this->generateActions($class, $model);
		
		// relationships
		$this->generateRelationships($class, $model);
		
		return $class->generate();
	}
	
	protected function generateActions(EmberClassGenerator $class, Table $model) {
		$types = Types::getModelTypes($model);
		$packageName = $this->getPackage()->getFullName();
		$nameGenerator = $this->factory->getActionNameGenerator();
		
		foreach ($types as $type) {
			$actionName = $nameGenerator->generate($type, $model);
			$prop = sprintf('can%s', ucfirst($type));
			$value = sprintf($this->template, $packageName, $actionName);
			$class->setProperty($prop, $value);
		}
	}
	
	protected function generateRelationships(EmberClassGenerator $class, Table $model) {
		$relationships = $this->modelService->getRelationships($model);
		$packageName = $this->getPackage()->getFullName();
		$nameGenerator = $this->factory->getActionNameGenerator();
		
		foreach ($relationships->getAll() as $relationship) {
			$types = Types::getRelationshipTypes($relationship);
			
			foreach ($types as $type) {
				$prop = sprintf('can%s%s', ucfirst($type), $relationship->getRelatedName());
				$actionName = $nameGenerator->generateRelationship($type, $relationship);
				$value = sprintf($this->template, $packageName, $actionName);
				$class->setProperty($prop, $value);
			}
		}
	}

}