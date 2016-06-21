<?php
namespace keeko\tools\generator\name;

use keeko\tools\model\Relationship;
use keeko\framework\utils\NameUtils;

class RelationshipMethodNameGenerator extends AbstractNameGenerator {

	/**
	 * Returns the method name in studly case to prefix with add/get/set/remove
	 * and call it on a model class
	 *
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateMethodName(Relationship $relationship) {
		// for many-to-many where model and foreign are the same
		if ($relationship->getType() == Relationship::MANY_TO_MANY && $relationship->isReflexive()) {
			$lk = $relationship->getLocalKey();
			$foreign = $relationship->getForeign();
			return $foreign->getPhpName() . 'RelatedBy' . $lk->getLocalColumn()->getPhpName();
		}

		// else
		return $relationship->getRelatedName();
	}

	/**
	 * Returns the plural method name in studly case to prefix with add/get/set/remove
	 * and call it on a model class
	 *
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generatePluralMethodName(Relationship $relationship) {
		// for many-to-many where model and foreign are the same
		if ($relationship->getType() == Relationship::MANY_TO_MANY && $relationship->isReflexive()) {
			$lk = $relationship->getLocalKey();
			$foreign = $relationship->getForeign();
			return NameUtils::pluralize($foreign->getPhpName()) . 'RelatedBy' . $lk->getLocalColumn()->getPhpName();
		}

		// else
		return $relationship->getRelatedPluralName();
	}

	/**
	 * Returns the reverse method name in studly case to prefix with add/get/set/remove
	 * and call it on a model class
	 *
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateReverseMethodName(Relationship $relationship) {
		// for many-to-many where model and foreign are the same
		if ($relationship->getType() == Relationship::MANY_TO_MANY && $relationship->isReflexive()) {
			$fk = $relationship->getForeignKey();
			$foreign = $relationship->getForeign();
			return $foreign->getPhpName() . 'RelatedBy' . $fk->getLocalColumn()->getPhpName();
		}

		// else
		return $relationship->getReverseRelatedName();
	}

	/**
	 * Returns the reverse plural method name in studly case to prefix with add/get/set/remove
	 * and call it on a model class
	 *
	 * @param Relationship $relationship
	 * @return string
	 */
	public function generateReversePluralMethodName(Relationship $relationship) {
		// for many-to-many where model and foreign are the same
		if ($relationship->getType() == Relationship::MANY_TO_MANY && $relationship->isReflexive()) {
			$fk = $relationship->getForeignKey();
			$foreign = $relationship->getForeign();
			return NameUtils::pluralize($foreign->getPhpName()) . 'RelatedBy' . $fk->getLocalColumn()->getPhpName();
		}

		// else
		return $relationship->getReverseRelatedPluralName();
	}
}