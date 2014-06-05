<?php

namespace keeko\tools\utils;

class NameUtils {
	
	private static $pluralizer = null;
	
	/**
	 * Transforms a given input into StudlyCase
	 *
	 * @param string $input
	 * @return string
	 */
	public static function toStudlyCase($input) {
		$input = trim($input, '-_');
		return ucfirst(preg_replace_callback('/([A-Z-_][a-z]+)/', function($matches) {
			return ucfirst(str_replace(['-','_'], '',$matches[0]));
		}, $input));
	}

	/**
	 * Returns the plural form of the input
	 * 
	 * @param string $input
	 * @return string
	 */
	public static function pluralize($input) {
		if (self::$pluralizer === null) {
			self::$pluralizer = new StandardEnglishSingularizer();
		}

		return self::$pluralizer->getPluralForm($input);
	}
	
	/**
	 * Returns the singular form of the input
	 *
	 * @param string $input
	 * @return string
	 */
	public static function singularize($input) {
		if (self::$pluralizer === null) {
			self::$pluralizer = new StandardEnglishSingularizer();
		}
	
		return self::$pluralizer->getSingularForm($input);
	}
}