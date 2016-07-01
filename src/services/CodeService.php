<?php
namespace keeko\tools\services;

use gossi\codegen\generator\CodeFileGenerator;
use gossi\codegen\model\AbstractPhpStruct;
use gossi\docblock\tags\AuthorTag;
use keeko\framework\schema\PackageSchema;
use keeko\tools\utils\NamespaceResolver;
use phootwork\file\File;
use phootwork\file\Path;
use Propel\Generator\Model\Table;

class CodeService extends AbstractService {

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
		foreach ($array as $key => $value) {
			$fields .= sprintf("\t'%s' => '%s',\n", $key, $value);
		}

		if (strlen($fields) > 0) {
			$fields = substr($fields, 0, -2);
		}

		return sprintf("[\n%s\n]", $fields);
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
