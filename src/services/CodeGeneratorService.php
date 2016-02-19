<?php
namespace keeko\tools\services;

use gossi\codegen\model\AbstractPhpStruct;
use gossi\docblock\tags\AuthorTag;
use Propel\Generator\Model\Table;
use gossi\codegen\generator\CodeFileGenerator;
use keeko\tools\utils\NamespaceResolver;
use keeko\core\schema\PackageSchema;
use keeko\core\schema\AuthorSchema;
use phootwork\file\File;
use phootwork\file\Path;
use keeko\core\schema\CodegenSchema;

class CodeGeneratorService extends AbstractService {
	
	private $codegen;
	
	public function getCodegenFile() {
		$basepath = dirname($this->project->getComposerFileName());
		return $basepath . '/codegen.json';
	}
	
	/**
	 * Loads the contents from codegen.json into a collection
	 *
	 * @throws JsonEmptyException
	 * @throws \RuntimeException
	 * @return CodegenSchema
	 */
	public function getCodegen() {
		if ($this->codegen === null) {
			$file = new File($this->getCodegenFile());
			$this->codegen = $file->exists() 
				? CodegenSchema::fromFile($this->getCodegenFile())
				: new CodegenSchema();
		}
		
		return $this->codegen;
	}
	
// 	/**
// 	 * Returns the codegen part for the given action name or an empty map
// 	 *
// 	 * @param string $name
// 	 * @return Map
// 	 */
// 	public function getCodegenAction($name) {
// 		$codegen = $this->getCodegen();
	
// 		if (isset($codegen['actions'])) {
// 			$actions = $codegen['actions'];
				
// 			if (isset($actions[$name])) {
// 				return $actions[$name];
// 			}
// 		}
	
// 		return null;
// 	}
	
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
		$conversions = $codegen->getWriteConversion($modelName);
		$filter = $codegen->getWriteFilter($modelName);
		$model = $this->modelService->getModel($modelName);
		$computed = $this->getComputedFields($model);
		$filter = array_merge($filter, $computed);

		$fields = '';
		$cols = $model->getColumns();
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
		
			if (!in_array($prop, $filter)) {
				$fields[] = $prop;
			}
		}
		
		return $fields;
	}
	
// 	/**
// 	 * Returns conversions for model columns
// 	 *
// 	 * @param string $model
// 	 * @param string $type
// 	 * @return array
// 	 */
// 	public function getConversions($model, $type) {
// 		return $this->getActionProp($model, $type, 'conversion');
// 	}
	
// 	/**
// 	 * Returns model columns that should be filtered
// 	 *
// 	 * @param string $model
// 	 * @param string $type
// 	 * @return array
// 	 */
// 	public function getFilter($model, $type) {
// 		return $this->getActionProp($model, $type, 'filter');
// 	}
	
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
	
	public function mapToCode(array $array) {
		$fields = '';
		foreach ($array as $k => $item) {
			$fields .= sprintf("\t'%s' => %s,\n", $k, $this->arrayToCode($item));
		}

		if (strlen($fields) > 0) {
			$fields = substr($fields, 0, -2);
		}
		
		return sprintf("[\n%s\n]", $fields);
	}

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
	
	public function dumpStruct(AbstractPhpStruct $struct, $overwrite = false) {
		$filename = $this->getFilename($struct);
		$file = new File($filename);

		if ($filename !== null && $file->exists() ? $overwrite : true) {
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
