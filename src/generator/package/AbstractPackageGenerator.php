<?php
namespace keeko\tools\generator\package;

use gossi\codegen\model\PhpClass;
use gossi\docblock\tags\LicenseTag;
use keeko\framework\schema\KeekoPackageSchema;
use keeko\tools\generator\AbstractGenerator;

abstract class AbstractPackageGenerator extends AbstractGenerator {

	protected function getTemplateFolder() {
		return 'package';
	}

	/**
	 * Generates the class based on the package
	 *
	 * @param KeekoPackageSchema $pkg
	 * @return PhpClass
	 */
	public function generate(KeekoPackageSchema $pkg) {
		$class = $this->generateClass($pkg);
		$class = $this->loadClass($class);

		$this->ensureBasicSetup($class);
		$this->addMethods($class);

		return $class;
	}

	protected function generateClass(KeekoPackageSchema $pkg) {
		$class = PhpClass::create($pkg->getClass());
		$class->setDescription($pkg->getTitle());

		$package = $this->packageService->getPackage();
		$docblock = $class->getDocblock();
		$docblock->appendTag(new LicenseTag($package->getLicense()));
		$this->codeService->addAuthors($class, $package);

		return $class;
	}

	abstract protected function ensureBasicSetup(PhpClass $class);

	abstract protected function addMethods(PhpClass $class);

}