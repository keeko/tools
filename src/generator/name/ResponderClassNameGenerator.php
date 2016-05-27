<?php
namespace keeko\tools\generator\name;

use keeko\tools\model\Relationship;

/**
 * Generates responder class names
 * 
 * @author gossi
 */
class ResponderClassNameGenerator extends AbstractNameGenerator {

	/**
	 * Returns a class name for a json responder
	 * 
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateJsonRelationshipResponder(Relationship $relationship) {
		$namespace = $this->service->getFactory()->getNamespaceGenerator()->getJsonRelationshipResponderNamespace();
		return sprintf('%s\\%s%sJsonResponder',
			$namespace, $relationship->getModel()->getPhpName(), $relationship->getRelatedName()
		);
	}
}