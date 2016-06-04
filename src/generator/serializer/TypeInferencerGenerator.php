<?php
namespace keeko\tools\generator\serializer;

use gossi\codegen\model\PhpClass;
use gossi\codegen\model\PhpMethod;
use gossi\codegen\model\PhpParameter;
use keeko\framework\utils\NameUtils;
use gossi\codegen\model\PhpProperty;

class TypeInferencerGenerator extends AbstractSerializerGenerator {

	public function generate() {
		$namespace = $this->factory->getNamespaceGenerator()->getSerializerNamespace();
		$className = $namespace . '\\TypeInferencer';
		$class = new PhpClass($className);
		$class->addInterface('TypeInferencerInterface');
		$class->addUseStatement('keeko\framework\model\TypeInferencerInterface');

		$this->generateSingleton($class);
		$this->generateTypes($class);
		$this->generateGetModelClass($class);
		$this->generateGetQueryClass($class);

		return $class;
	}

	protected function generateSingleton(PhpClass $class) {
		$class->setProperty(PhpProperty::create('instance')
			->setStatic(true)
			->setVisibility(PhpProperty::VISIBILITY_PRIVATE)
		);

		$class->setMethod(PhpMethod::create('getInstance')
			->setStatic(true)
			->setBody($this->twig->render('getInstance.twig'))
		);

		$class->setMethod(PhpMethod::create('__construct')
			->setVisibility(PhpMethod::VISIBILITY_PRIVATE)
		);
	}

	protected function generateTypes(PhpClass $class) {
		$package = $this->packageService->getPackage();
		$types = [];

		foreach ($this->modelService->getModels() as $model) {
			$type = sprintf('%s/%s', $package->getKeeko()->getModule()->getSlug(), NameUtils::dasherize($model->getOriginCommonName()));
			$types[$type] = [
				'modelClass' => $model->getNamespace() . '\\' . $model->getPhpName(),
				'queryClass' => $model->getNamespace() . '\\' . $model->getPhpName() . 'Query',
			];
			$types[NameUtils::pluralize($type)] = [
				'modelClass' => $model->getNamespace() . '\\' . $model->getPhpName(),
				'queryClass' => $model->getNamespace() . '\\' . $model->getPhpName() . 'Query',
			];
		}

		$out = '';
		foreach ($types as $type => $data) {
			$out .= sprintf("\t'$type' => [\n\t\t'modelClass' => '%s',\n\t\t'queryClass' => '%s'\n\t],\n",
				$data['modelClass'], $data['queryClass']);
		}
		$out = rtrim($out, ",\n");
		$out = "[\n$out\n]";

		$class->setProperty(PhpProperty::create('types')
			->setExpression($out)
			->setVisibility(PhpProperty::VISIBILITY_PRIVATE)
		);
	}

	protected function generateGetModelClass(PhpClass $class) {
		$class->setMethod(PhpMethod::create('getModelClass')
			->addParameter(PhpParameter::create('type'))
			->setBody($this->twig->render('getModelClass.twig'))
		);
	}

	protected function generateGetQueryClass(PhpClass $class) {
		$class->setMethod(PhpMethod::create('getQueryClass')
			->addParameter(PhpParameter::create('type'))
			->setBody($this->twig->render('getQueryClass.twig'))
		);
	}
}