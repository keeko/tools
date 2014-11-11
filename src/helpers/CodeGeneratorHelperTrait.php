<?php
namespace keeko\tools\helpers;

use gossi\codegen\model\AbstractPhpStruct;
use gossi\docblock\Docblock;
use gossi\docblock\tags\AuthorTag;
use Propel\Generator\Model\Table;
use Propel\Generator\Model\Database;
use Symfony\Component\Console\Input\InputInterface;
use gossi\codegen\model\PhpClass;
use gossi\codegen\generator\CodeGenerator;
use Symfony\Component\Filesystem\Filesystem;
use gossi\codegen\generator\CodeFileGenerator;

trait CodeGeneratorHelperTrait {
	
	/**
	 * Adds authors to the docblock of the given struct
	 * 
	 * @param AbstractPhpStruct $struct
	 * @param array $package
	 */
	protected function addAuthors(AbstractPhpStruct $struct, $package) {
		$docblock = $struct->getDocblock();

		if (isset($package['authors'])) {
			foreach ($package['authors'] as $author) {
				$tag = AuthorTag::create()->setName($author['name']);

				if (isset($author['email'])) {
					$tag->setEmail($author['email']);
				} else if (isset($author['homepage'])) {
					$tag->setEmail($author['homepage']);
				}

				$docblock->appendTag($tag);
			}
		}
	}
	
	/**
	 * @return Database
	 */
	abstract protected function getDatabase();
	
	/**
	 * Returns code for hydrating a propel model
	 * 
	 * @param array $module
	 * @param string $model
	 * @return string
	 */
	protected function getWriteFields(array $module, $model) {
		$database = $this->getDatabase();
		$conversions = $this->getConversions($module, $model, 'write');
		$filter = $this->getFilter($model, 'write');
		$computed = $this->getComputedFields($database->getTable($model));
		$filter = array_merge($filter, $computed);
	
		$fields = '';
		$cols = $database->getTable($model)->getColumns();
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
	 * @param array $module
	 * @param string $model
	 * @param string $type
	 * @return string[]
	 */
	protected function getConversions(array $module, $model, $type) {
		if (isset($module['codegen'])
				&& isset($module['codegen'][$model])
				&& isset($module['codegen'][$model][$type])
				&& isset($module['codegen'][$model][$type]['conversion'])) {
			return $module['codegen'][$model][$type]['conversion'];
		}
	
		return [];
	}
	
	/**
	 * @return array
	 */
	abstract protected function getKeekoModule();
	
	/**
	 * Returns model columns that should be filtered
	 * 
	 * @param array $module
	 * @param string $model
	 * @param string $type
	 * @return string[]
	 */
	protected function getFilter($model, $type) {
		$module = $this->getKeekoModule();
		if (isset($module['codegen'])
				&& isset($module['codegen'][$model])
				&& isset($module['codegen'][$model][$type])
				&& isset($module['codegen'][$model][$type]['filter'])) {
			return $module['codegen'][$model][$type]['filter'];
		}

		return [];
	}

	/**
	 * Returns computed model fields
	 * 
	 * @param Table $table
	 * @return string[]
	 */
	protected function getComputedFields(Table $table) {
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
	protected function arrayToCode(array $array) {
		$fields = '';
		foreach ($array as $item) {
			$fields .= sprintf("'%s', ", $item);
		}
	
		if (strlen($fields) > 0) {
			$fields = substr($fields, 0, -2);
		}
	
		return sprintf('[%s]', $fields);
	}
	
	protected function getFilename(AbstractPhpStruct $struct) {
		$folder = $this->getSourcePath($struct->getNamespace());
		if ($folder !== null) {
			return str_replace('//', '/', $folder . '/' . $struct->getName() . '.php');
		}
		return null;
	}
	
	protected function dumpStruct(AbstractPhpStruct $struct, $overwrite = false) {
		$fileName = $this->getFilename($struct);
		
		if ($fileName !== null && file_exists($fileName) ? $overwrite : true) {
			$generator = new CodeFileGenerator();
			$code = $generator->generate($struct);

			$fs = new Filesystem();
			$fs->dumpFile($fileName, $code, 0755);

			$this->writeln(sprintf('Class <info>%s</info> written at <info>%s</info>', $struct->getQualifiedName(), $fileName));
		}
	}

	protected function getSourcePath($namespace) {
		$relativeSourcePath = NamespaceResolver::getSourcePath($namespace, $this->getPackage());
		
		if ($relativeSourcePath !== null) {
			$jsonFile = $this->getComposerFile();
			$projectDir = dirname($jsonFile);
			$sourcePath = str_replace('//', '/', $projectDir . '/' . $relativeSourcePath);
		
			return $sourcePath;
		}

		return null;
	}
}