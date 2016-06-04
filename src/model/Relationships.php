<?php
namespace keeko\tools\model;

use phootwork\collection\Map;
use Propel\Generator\Model\Table;

class Relationships {

	/** @var Map */
	private $all;

	/** @var Map */
	private $oneToOne;

	/** @var Map */
	private $oneToMany;

	/** @var Map */
	private $manyToMany;

	/** @var Table */
	private $model;

	public function __construct(Table $model) {
		$this->model = $model;
		$this->all = new Map();
		$this->oneToOne = new Map();
		$this->oneToMany = new Map();
		$this->manyToMany = new Map();
	}

	/**
	 * @return Table
	 */
	public function getModel() {
		return $this->model;
	}

	/**
	 * Adds a relationships to this collection
	 *
	 * @param Relationship $relationship
	 */
	public function add(Relationship $relationship) {
		switch ($relationship->getType()) {
			case Relationship::ONE_TO_ONE:
				$this->oneToOne->set($relationship->getRelatedTypeName(), $relationship);
				break;

			case Relationship::ONE_TO_MANY:
				$this->oneToMany->set($relationship->getRelatedTypeName(), $relationship);
				break;

			case Relationship::MANY_TO_MANY:
				$this->manyToMany->set($relationship->getRelatedTypeName(), $relationship);
				break;
		}

		$this->all->set($relationship->getRelatedTypeName(), $relationship);
	}

	/**
	 * Return all relationships
	 *
	 * @return Relationship[]
	 */
	public function getAll() {
		return $this->all;
	}

	/**
	 * Return one-to-one relationships
	 *
	 * @return Relationship[]
	 */
	public function getOneToOne() {
		return $this->oneToOne;
	}

	/**
	 * Return one-to-many relationships
	 *
	 * @return Relationship[]
	 */
	public function getOneToMany() {
		return $this->oneToMany;
	}

	/**
	 * Return many-to-many relationships
	 *
	 * @return Relationship[]
	 */
	public function getManyToMany() {
		return $this->manyToMany;
	}

	/**
	 * The number of all relationships
	 *
	 * @return int
	 */
	public function size() {
		return $this->all->size();
	}

	/**
	 * Checks whether a relationship with the given related type name exists
	 *
	 * @param string $relatedTypeName
	 * @return boolean
	 */
	public function has($relatedTypeName) {
		return $this->all->has($relatedTypeName);
	}

	/**
	 * Returns a relationship with the given related type name
	 *
	 * @param string $relatedTypeName
	 * @return Relationship
	 */
	public function get($relatedTypeName) {
		return $this->all->get($relatedTypeName);
	}
}