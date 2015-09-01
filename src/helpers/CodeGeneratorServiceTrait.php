<?php
namespace keeko\tools\helpers;

use gossi\codegen\model\AbstractPhpStruct;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use keeko\tools\exceptions\JsonEmptyException;
use Propel\Generator\Model\Table;
use keeko\tools\services\CommandService;

trait CodeGeneratorServiceTrait {
	
	/**
	 * @return CommandService
	 */
	abstract protected function getService();
	
	protected function getCodegenFile() {
		return $this->getService()->getCodeGeneratorService()->getCodegenFile();
	}
	
	/**
	 * Loads the contents from codegen.json into a collection
	 * 
	 * @throws FileNotFoundException
	 * @throws JsonEmptyException
	 * @throws \RuntimeException
	 * @return array
	 */
	protected function getCodegen() {
		return $this->getService()->getCodeGeneratorService()->getCodegen();
	}
	
	/**
	 * Returns the codegen part for the given action name or an empty map
	 * 
	 * @param string $name
	 * @return array
	 */
	protected function getCodegenAction($name) {
		return $this->getService()->getCodeGeneratorService()->getCodegenAction($name);
	}
	
	/**
	 * Adds authors to the docblock of the given struct
	 * 
	 * @param AbstractPhpStruct $struct
	 * @param array $package
	 */
	protected function addAuthors(AbstractPhpStruct $struct, $package) {
		return $this->getService()->getCodeGeneratorService()->addAuthors($struct, $package);
	}

	/**
	 * Returns code for hydrating a propel model
	 * 
	 * @param string $model
	 * @return string
	 */
	protected function getWriteFields($model) {
		return $this->getService()->getCodeGeneratorService()->getWriteFields($model);
	}
	
	/**
	 * Returns conversions for model columns
	 * 
	 * @param string $model
	 * @param string $type read or write
	 * @return array
	 */
	protected function getConversions($model, $type) {
		return $this->getService()->getCodeGeneratorService()->getConversions($model, $type);
	}
	
	/**
	 * Returns model columns that should be filtered
	 * 
	 * @param string $model
	 * @param string $type read or write
	 * @return array
	 */
	protected function getFilter($model, $type) {
		return $this->getService()->getCodeGeneratorService()->getFilter($model, $type);
	}

	/**
	 * Returns computed model fields
	 * 
	 * @param Table $table
	 * @return ArrayList<String>
	 */
	protected function getComputedFields(Table $table) {
		return $this->getService()->getCodeGeneratorService()->getComputedFields($table);
	}
	
	/**
	 * Helper to represent an array as php code
	 * 
	 * @param array $array
	 * @return string
	 */
	protected function arrayToCode(array $array) {
		return $this->getService()->getCodeGeneratorService()->arrayToCode($array);
	}
	
	protected function getFilename(AbstractPhpStruct $struct) {
		return $this->getService()->getCodeGeneratorService()->getFilename($struct);
	}
	
	protected function dumpStruct(AbstractPhpStruct $struct, $overwrite = false) {
		return $this->getService()->getCodeGeneratorService()->dumpStruct($struct, $overwrite);
	}

// 	protected function getSourcePath($namespace) {
// 		return $this->getService()->getCodeGeneratorService()->getSourcePath($namespace);
// 	}
}