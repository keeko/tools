<?php
namespace keeko\tools\services;

use phootwork\collection\CollectionUtils;
use gossi\codegen\model\AbstractPhpStruct;
use gossi\docblock\tags\AuthorTag;
use Propel\Generator\Model\Table;
use phootwork\collection\ArrayList;
use gossi\codegen\generator\CodeFileGenerator;
use keeko\tools\utils\NamespaceResolver;
use keeko\core\schema\PackageSchema;
use keeko\core\schema\AuthorSchema;
use phootwork\file\File;
use keeko\tools\helpers\IOServiceTrait;
use phootwork\file\Path;

class CodeGeneratorService extends AbstractService {

	use IOServiceTrait;
	
	private $codegen;
	
	public function getCodegenFile() {
		$basepath = dirname($this->service->getPackageService()->getComposerFile());
		return $basepath . '/codegen.json';
	}
	
	/**
	 * Loads the contents from codegen.json into a collection
	 *
	 * @throws JsonEmptyException
	 * @throws \RuntimeException
	 * @return Map
	 */
	public function getCodegen() {
		if ($this->codegen === null) {
			return $this->service->getJsonService()->read($this->getCodegenFile());
		}
		
		return $this->codegen;
	}
	
	/**
	 * Returns the codegen part for the given action name or an empty map
	 *
	 * @param string $name
	 * @return Map
	 */
	public function getCodegenAction($name) {
		$codegen = $this->getCodegen();
	
		if (isset($codegen['actions'])) {
			$actions = $codegen['actions'];
				
			if (isset($actions[$name])) {
				return $actions[$name];
			}
		}
	
		return null;
	}
	
	/**
	 * Adds authors to the docblock of the given struct
	 *
	 * @param AbstractPhpStruct $struct
	 * @param array $package
	 */
	public function addAuthors(AbstractPhpStruct $struct, PackageSchema $package) {
		$docblock = $struct->getDocblock();
		
		foreach ($package->getAuthors() as $author) {
			/* @var $author AuthorSchema */
			
			$tag = AuthorTag::create()->setName($author->getName());
			$mail = $author->getEmail();
			$homepage = $author->getHomepage();
			
			if (!empty($mail)) {
				$tag->setEmail($mail);
			} else if (!empty($homepage)) {
				$tag->setEmail($homepage);
			}
			
			$docblock->appendTag($tag);
		}
	}
	
	/**
	 * Returns code for hydrating a propel model
	 *
	 * @param string $model
	 * @return string
	 */
	public function getWriteFields($model) {
		$conversions = $this->getConversions($model, 'write');
		$filter = $this->getFilter($model, 'write');
		$computed = $this->getComputedFields($this->getModel($model));
		$filter = array_merge($filter, $computed);
	
		$fields = '';
		$cols = $this->service->getModelService()->getModel($model)->getColumns();
		foreach ($cols as $col) {
			$prop = $col->getName();
	
			if (!in_array($prop, $filter)) {
				$fields .= sprintf("'%s'", $prop);
	
				if (isset($conversions[$prop])) {
					$fields .= ' => function($v) {'."\n\t".'return ' . $conversions[$prop] . ';'."\n".'}';
				}
	
				$fields .= ', ';
			}
		}
	
		if (strlen($fields) > 0) {
			$fields = substr($fields, 0, -2);
		}
	
		return sprintf('[%s]', $fields);
	}
	
	/**
	 * Returns conversions for model columns
	 *
	 * @param string $model
	 * @param string $type
	 * @return array
	 */
	public function getConversions($model, $type) {
		return $this->getActionProp($model, $type, 'conversion');
	}
	
	/**
	 * Returns model columns that should be filtered
	 *
	 * @param string $model
	 * @param string $type
	 * @return array
	 */
	public function getFilter($model, $type) {
		return $this->getActionProp($model, $type, 'filter');
	}
	
	private function getActionProp($model, $type, $prop) {
		$action = $this->getCodegenAction($model);
		if ($action !== null && isset($action[$type]) && isset($action[$type][$prop])) {
			return $action[$type][$prop];
		}
		
		return [];
	}
	
	/**
	 * Returns computed model fields
	 *
	 * @param Table $table
	 * @return array<string>
	 */
	public function getComputedFields(Table $table) {
		$fields = [];
	
		// iterate over behaviors to get their respective columns
		foreach ($table->getBehaviors() as $behavior) {
			switch ($behavior->getName()) {
				case 'timestampable':
					$fields[] = $behavior->getParameter('create_column');
					$fields[] = $behavior->getParameter('update_column');
					break;
	
				case 'aggregate_column':
					$fields[] = $behavior->getParameter('name');
					break;
			}
		}
	
		return $fields;
	}
	
	/**
	 * Helper to represent an array as php code
	 *
	 * @param array $array
	 * @return string
	 */
	public function arrayToCode(array $array) {
		$fields = '';
		foreach ($array as $item) {
			$fields .= sprintf("'%s', ", $item);
		}
	
		if (strlen($fields) > 0) {
			$fields = substr($fields, 0, -2);
		}
	
		return sprintf('[%s]', $fields);
	}

	public function getFilename(AbstractPhpStruct $struct) {
		$package = $this->service->getPackageService()->getPackage();
		$relativeSourcePath = NamespaceResolver::getSourcePath($struct->getNamespace(), $package);
		
		if ($relativeSourcePath === null) {
			return null;
		}
		
		$jsonFile = $this->service->getProject()->getComposerFileName();
		$path = new Path(dirname($jsonFile));
		$path = $path->append($relativeSourcePath);
		$path = $path->append($struct->getName() . '.php');
		return $path;
	}
	
	public function dumpStruct(AbstractPhpStruct $struct, $overwrite = false) {
		$filename = $this->getFilename($struct);

		if ($filename !== null && file_exists($filename) ? $overwrite : true) {
			$generator = new CodeFileGenerator();
			$code = $generator->generate($struct);
	
			$file = new File($filename);
			$file->write($code);
	
			$this->writeln(sprintf('Class <info>%s</info> written at <info>%s</info>', $struct->getQualifiedName(), $filename));
		}
	}
}
