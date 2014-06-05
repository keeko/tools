<?php

namespace keeko\tools\utils;

use Propel\Common\Pluralizer\StandardEnglishPluralizer;

class StandardEnglishSingularizer extends StandardEnglishPluralizer {
	
	public function getSingularForm($plural) {
		// save some time in the case that singular and plural are the same
		if (in_array(strtolower($plural), $this->uncountable)) {
			return $plural;
		}
		
		// check for irregular singular words
		$irregular = array_flip($this->irregular);
		
		// some additions
		$irregular['indices'] = 'index';
		$irregular['vertices'] = 'vertex';
		$irregular['matrices'] = 'matrix';
		
		foreach ($irregular as $pattern => $result) {
			$searchPattern = '/' . $pattern . '$/i';
			if (preg_match($searchPattern, $plural)) {
				return preg_replace($searchPattern, $result, $plural);
			}
		}
		
		// check for irregular singular suffixes
		$plurals = array_flip($this->plural);
		
		// some modifications
		unset($plurals['\1ices']);
		unset($plurals['\1i']);
		unset($plurals['\1oes']);
		
		$plurals['(alumn|bacill|cact|foc|fung|nucle|radi|stimul|syllab|termin|vir)i'] = '\1us';
		$plurals['(buffal|tomat)oes'] = '\1o';
		
		foreach ($plurals as $pattern => $result) {
			$searchPattern = '/' . $pattern . '$/i';
			if (preg_match($searchPattern, $plural)) {
				return preg_replace($searchPattern, $result, $plural);
			}
		}
		
		// fallback to naive singularization
		return substr($plural, 0, -1);
	}
}