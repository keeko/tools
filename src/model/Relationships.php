<?php
namespace keeko\tools\model;

use phootwork\collection\Map;
use Propel\Generator\Model\Table;

class Relationships {
	
	/** @var Map */
	private $all;
	
	/** @var Map */
	private $one;
	
	/** @var Map */
	private $many;
	
	/** @var Table */
	private $model;
	
	public function __construct(Table $model) {
		$this->model = $model;
		$this->all = new Map();
		$this->one = new Map();
		$this->many = new Map();
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
		if ($relationship instanceof ManyRelationship) {
			$this->many->set($relationship->getForeign()->getOriginCommonName(), $relationship);
		} else {
			$this->one->set($relationship->getForeign()->getOriginCommonName(), $relationship);
		}
		
		$this->all->set($relationship->getForeign()->getOriginCommonName(), $relationship);
	}
	
	/**
	 * @return Map
	 */
	public function getAll() {
		return $this->all;
	}
	
	/**
	 * @return Map
	 */
	public function getOne() {
		return $this->one;
	}
	
	/**
	 * @return Map
	 */
	public function getMany() {
		return $this->many;
	}
	
	/**
	 * The number of relationships
	 * 
	 * @return int
	 */
	public function size() {
		return $this->all->size();
	}
	
	/**
	 * Checks whether a relationship with the given foreign name exists
	 * 
	 * @param string $foreignName
	 * @return boolean
	 */
	public function has($foreignName) {
		return $this->all->has($foreignName);
	}
	
	/**
	 * Returns a relationship with the given foreign name
	 * 
	 * @param string $foreignName
	 * @return Relationship
	 */
	public function get($foreignName) {
		return $this->all->get($foreignName);
	}
}