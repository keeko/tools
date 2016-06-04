<?php
namespace keeko\tools\model;

use Propel\Generator\Model\ForeignKey;
use Propel\Generator\Model\Table;
use keeko\framework\utils\NameUtils;

abstract class Relationship {

	const ONE_TO_ONE = 'one-to-one';
	const ONE_TO_MANY = 'one-to-many';
	const MANY_TO_MANY = 'many-to-many';

	/** @var Table */
	protected $model;

	/** @var Table */
	protected $foreign;

	/** @var ForeignKey */
	protected $fk;

	/**
	 * Returns the type of this relationship
	 *
	 * @return string
	 */
	abstract public function getType();

	public function isOneToOne() {
		return $this->getType() == Relationship::ONE_TO_ONE;
	}

	/**
	 * Returns the model
	 *
	 * @return Table
	 */
	public function getModel() {
		return $this->model;
	}

	/**
	 * Returns the foreign model
	 *
	 * @return Table
	 */
	public function getForeign() {
		return $this->foreign;
	}

	/**
	 * Returns the foreign key
	 *
	 * @return ForeignKey
	 */
	public function getForeignKey() {
		return $this->fk;
	}

	/**
	 * Returns the related name in studly case
	 *
	 * @return string
	 */
	abstract public function getRelatedName();

	/**
	 * Returns the pluralized related name in studly case
	 *
	 * @return string
	 */
	public function getRelatedPluralName() {
		return NameUtils::pluralize($this->getRelatedName());
	}

	/**
	 * Returns the related type name for usage in api environment (slug, type-name, etc)
	 *
	 * @return string
	 */
	public function getRelatedTypeName() {
		return NameUtils::dasherize($this->getRelatedName());
	}

	/**
	 * Returns the pluralized related type name for usage in api environment (slug, type-name, etc)
	 *
	 * @return string
	 */
	public function getRelatedPluralTypeName() {
		return NameUtils::pluralize($this->getRelatedTypeName());
	}

	/**
	 * Returns the reverse related name in studly case
	 */
	abstract public function getReverseRelatedName();

	/**
	 * Returns the pluralized reverse related name in studly case
	 *
	 * @return string
	 */
	public function getReverseRelatedPluralName() {
		return NameUtils::pluralize($this->getReverseRelatedName());
	}

	/**
	 * Returns the reverse related type name for usage in api environment (slug, type-name, etc)
	 *
	 * @return string
	 */
	public function getReverseRelatedTypeName() {
		return NameUtils::dasherize($this->getReverseRelatedName());
	}

	/**
	 * Returns the pluralized reverse related type name for usage in api environment (slug, type-name, etc)
	 *
	 * @return string
	 */
	public function getReverseRelatedPluralTypeName() {
		return NameUtils::pluralize($this->getReverseRelatedTypeName());
	}

	/**
	 * Returns whether the relationship refers to itself
	 *
	 * @return bool
	 */
	public function isReflexive() {
		return $this->model == $this->foreign;
	}
}