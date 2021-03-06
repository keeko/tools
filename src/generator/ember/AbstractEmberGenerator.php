<?php
namespace keeko\tools\generator\ember;

use keeko\framework\schema\PackageSchema;
use keeko\tools\generator\AbstractGenerator;
use keeko\tools\model\Project;
use keeko\tools\services\CommandService;
use Propel\Generator\Model\Table;
use keeko\framework\schema\GeneratorDefinitionSchema;

class AbstractEmberGenerator extends AbstractGenerator {

	protected $prj;

	public function __construct(CommandService $service, Project $project) {
		parent::__construct($service);
		$this->prj = $project;
	}

	protected function getProject() {
		return $this->prj;
	}

	/**
	 * @return PackageSchema
	 */
	protected function getPackage() {
		return $this->prj->getPackage();
	}

	/**
	 *
	 * @param Table $model
	 * @return string
	 */
	protected function getSlug(Table $model) {
		$namespace = $model->getNamespace();
		$parts = explode('\\', $namespace);

		if ($parts[0] == 'keeko') {
			return $parts[1];
		}

		return $parts[0] . '.' . $parts[1];
	}

	/**
	 * @return GeneratorDefinitionSchema
	 */
	protected function getGenerator() {
		$this->prj->getGeneratorDefinition();
	}
}