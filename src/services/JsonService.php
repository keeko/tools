<?php
namespace keeko\tools\services;

use keeko\tools\exceptions\JsonEmptyException;
use phootwork\json\Json;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use phootwork\file\File;

class JsonService extends AbstractService {

	/**
	 * Reads a file and decodes json
	 * 
	 * @param string $filename
	 * @throws FileNotFoundException
	 * @throws JsonEmptyException
	 * @throws \RuntimeException
	 * @return array
	 */
	public function read($filename) {
		if (!file_exists($filename)) {
			throw new FileNotFoundException(sprintf('%s not found', $filename));
		}

		try {
			$file = new File($filename);
			$json = Json::decode($file->read());
		} catch (JsonException $e) {
			if ($json === null) {
				throw new JsonEmptyException(sprintf('%s is empty', $filename));
			} else {
				throw new \RuntimeException(sprintf('Problem occured while decoding %s: %s', $filename, $e->getMessage()));
			}
		}
		
		return $json;
	}

	/**
	 * Encodes contents to json and writes them into the given filename
	 * 
	 * @param string $filename
	 * @param array $contents
	 */
	public function write($filename, $contents) {
		$json = Json::encode($contents, Json::PRETTY_PRINT | Json::UNESCAPED_SLASHES);
		$json = str_replace('    ', "\t", $json);

		$file = new File($filename);
		$file->setMode(0755);
		$file->write($json);
	}
}