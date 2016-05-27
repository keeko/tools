<?php
namespace keeko\tools\services;

use gossi\codegen\generator\CodeFileGenerator;
use gossi\codegen\model\AbstractPhpStruct;
use gossi\docblock\tags\AuthorTag;
use keeko\framework\schema\CodegenSchema;
use keeko\framework\schema\PackageSchema;
use keeko\tools\utils\NamespaceResolver;
use phootwork\file\File;
use phootwork\file\Path;
use Propel\Generator\Model\Table;

class CodeGeneratorService extends AbstractService {
	
	private $codegen;
	
	public function getCodegenFile() {
		return $this->project->getCodegenFileName();
	}
	
	/**
	 * Returns codegen from project
	 *
	 * @throws JsonEmptyException
	 * @throws \RuntimeException
	 * @return CodegenSchema
	 */
	public function getCodegen() {
		return $this->project->getCodegen();
	}
	
	/**
	 * Adds authors to the docblock of the given struct
	 *
	 * @param AbstractPhpStruct $struct
	 * @param PackageSchema $package
	 */
	public function addAuthors(AbstractPhpStruct $struct, PackageSchema $package = null) {
		if ($package === null) {
			$package = $this->packageService->getPackage();
		}
		$docblock = $struct->getDocblock();
		
		foreach ($package->getAuthors() as $author) {
			/* @var $author AuthorSchema */
			$tag = AuthorTag::create()->setName($author->getName());
			$mail = $author->getEmail();

			if (!empty($mail)) {
				$tag->setEmail($mail);
			}

			$docblock->appendTag($tag);
		}
	}

	/**
	 * Returns code for hydrating a propel model
	 *
	 * @param string $modelName
	 * @return string
	 */
	public function getWriteFields($modelName) {
		$codegen = $this->getCodegen();
		$filter = $codegen->getWriteFilter($modelName);
		$model = $this->modelService->getModel($modelName);
		$computed = $this->getComputedFields($model);
		$filter = array_merge($filter, $computed);

		$fields = [];
		$cols = $model->getColumns();
		foreach ($cols as $col) {
			$prop = $col->getName();
	
			if (!in_array($prop, $filter)) {
				$fields[] = $prop;
			}
		}
	
		return $fields;
	}
	
	/**
	 * Returns the fields for a model
	 * 
	 * @param string $modelName
	 * @return array
	 */
	public function getReadFields($modelName) {
		$codegen = $this->getCodegen();
		$model = $this->modelService->getModel($modelName);
// 		$computed = $this->getComputedFields($model);
		$filter = $codegen->getReadFilter($modelName);
// 		$filter = array_merge($filter, $computed);
		
		$fields = [];
		$cols = $model->getColumns();
		foreach ($cols as $col) {
			$prop = $col->getName();
		
			if (!in_array($prop, $filter) && !$col->isForeignKey() && !$col->isPrimaryKey()) {
				$fields[] = $prop;
			}
		}
		
		return $fields;
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

	/**
	 * Returns the filename for a given struct
	 * 
	 * @param AbstractPhpStruct $struct
	 * @return string
	 */
	public function getFilename(AbstractPhpStruct $struct) {
		$package = $this->packageService->getPackage();
		$relativeSourcePath = NamespaceResolver::getSourcePath($struct->getNamespace(), $package);
		
		if ($relativeSourcePath === null) {
			return null;
		}

		$jsonFile = $this->project->getComposerFileName();
		$path = new Path(dirname($jsonFile));
		$path = $path->append($relativeSourcePath);
		$path = $path->append($struct->getName() . '.php');
		return $path->toString();
	}
	
	/**
	 * Returns a file object for a given struct
	 * 
	 * @param AbstractPhpStruct $struct
	 * @return File
	 */
	public function getFile(AbstractPhpStruct $struct) {
		return new File($this->getFilename($struct));
	}
	
	public function dumpStruct(AbstractPhpStruct $struct, $overwrite = false) {
		$filename = $this->getFilename($struct);
		$file = new File($filename);

		if ($filename !== null && ($file->exists() ? $overwrite : true)) {
			// generate code
			$generator = new CodeFileGenerator();
			$code = $generator->generate($struct);

			// write code to file
			$file->write($code);
			
			// tell user about
			$this->io->writeln(sprintf('Class <info>%s</info> written at <info>%s</info>', $struct->getQualifiedName(), $filename));
		}
	}
}
