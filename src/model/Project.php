<?php
namespace keeko\tools\model;

use keeko\framework\schema\GeneratorDefinitionSchema;
use keeko\framework\schema\PackageSchema;
use phootwork\file\File;

class Project {

	/** @var string */
	private $root;

	/** @var PackageSchema */
	private $package;

	/** @var GeneratorDefinitionSchema */
	private $generatorDefinition;

	public function __construct($workdir) {
		$this->root = $workdir;
	}

	/**
	 * @return string
	 */
	public function getRootPath() {
		return $this->root;
	}

	/**
	 * @return string
	 */
	public function getComposerFileName() {
		return $this->root . '/composer.json';
	}

	/**
	 *
	 * @return boolean
	 */
	public function hasComposerFile() {
		$file = new File($this->getComposerFileName());
		return $file->exists();
	}

	/**
	 * @return string
	 */
	public function getApiFileName() {
		return $this->root . '/api.json';
	}

	/**
	 * @return boolean
	 */
	public function hasApiFile() {
		$file = new File($this->getApiFileName());
		return $file->exists();
	}

	/**
	 * @return string
	 */
	public function getSchemaFileName() {
		return $this->root . '/res/database/schema.xml';
	}

	/**
	 * @return boolean
	 */
	public function hasSchemaFile() {
		$file = new File($this->getSchemaFileName());
		return $file->exists();
	}

	/**
	 * @return string
	 */
	public function getGeneratorDefinitionFileName() {
		return $this->root . '/generator.json';
	}

	/**
	 * @return boolean
	 */
	public function hasGeneratorDefinitionFile() {
		$file = new File($this->getGeneratorDefinitionFileName());
		return $file->exists();
	}

	/**
	 * Returns the package from 'composer.json' or creates a blank one, if composer.json
	 * doesn't exist.
	 *
	 * @return PackageSchema
	 */
	public function getPackage() {
		if ($this->package === null) {
			$this->package = $this->hasComposerFile()
				? PackageSchema::fromFile($this->getComposerFileName())
				: new PackageSchema();
		}
		return $this->package;
	}

	/**
	 * Returns a generator definition from 'generator.json' or creates a blank one, if
	 * the file doesn't exist
	 *
	 * @throws JsonEmptyException
	 * @throws \RuntimeException
	 * @return GeneratorDefinitionSchema
	 */
	public function getGeneratorDefinition() {
		if ($this->generatorDefinition === null) {
			$this->generatorDefinition = $this->hasGeneratorDefinitionFile()
				? GeneratorDefinitionSchema::fromFile($this->getGeneratorDefinitionFileName())
				: new GeneratorDefinitionSchema();
		}

		return $this->generatorDefinition;
	}
}
