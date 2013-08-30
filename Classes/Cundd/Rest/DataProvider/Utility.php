<?php
namespace Cundd\Rest\DataProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A utility class with static methods for Data Providers
 *
 * @package Cundd\Rest\DataProvider
 */
class Utility {
	/**
	 * Separator between vendor, extension and model in the API path
	 */
	const API_PATH_PART_SEPARATOR = '-';

	/**
	 * Returns an array of class name parts including vendor, extension
	 * and domain model
	 *
	 * Example:
	 *   array(
	 *     Vendor
	 *     MyExt
	 *     MyModel
	 *   )
	 *
	 * @param string $path
	 * @param bool $convertPlural Indicates if plural resource names should be converted
	 * @return array
	 */
	static public function getClassNamePartsForPath($path, $convertPlural = TRUE) {
		if (strpos($path, '_') !== FALSE) {
			$path = GeneralUtility::underscoredToUpperCamelCase($path);
		}
		$parts = explode(self::API_PATH_PART_SEPARATOR, $path);
		if (count($parts) < 3) {
			array_unshift($parts, '');
		}

		if ($convertPlural && $parts) {
			$lastPartIndex = count($parts) - 1;
			$parts[$lastPartIndex] = static::singularize($parts[$lastPartIndex]);
		}

		$parts = array_map(function ($part) {
			return ucfirst($part);
		}, $parts);
		return $parts;
	}

	/**
	 * Tries to generate the API path for the given class name
	 *
	 * @param string $className
	 * @return string|bool Returns the path or FALSE if it couldn't be determined
	 */
	static public function getPathForClassName($className) {
		$path = FALSE;
		if (strpos($className, '\\')) {
			if ($className[0] !== '\\') {
				$className = '\\' . $className;
			}
			// \Iresults\Result\Domain\Model\Team
			$classNameParts = explode('\\', $className);
			if (count($classNameParts) > 5) {
				array_shift($classNameParts);
			}
		} else {
			$classNameParts = explode('_', $className);
		}
		array_shift($classNameParts);

		$classNameParts = array_map(
			array('TYPO3\\CMS\\Core\\Utility\\GeneralUtility', 'camelCaseToLowerCaseUnderscored'),
			$classNameParts
		);

		$path = $classNameParts[0] . self::API_PATH_PART_SEPARATOR . $classNameParts[3]; //  . self::API_PATH_PART_SEPARATOR . $classNameParts[4];
		return $path;
	}

	/**
	 * Tries to convert an english plural into it's singular.
	 *
	 * @param string $word
	 * @return string
	 */
	static public function singularize($word) {
		// Here is the list of rules. To add a scenario,
		// Add the plural ending as the key and the singular
		// ending as the value for that key. This could be
		// turned into a preg_replace and probably will be
		// eventually, but for now, this is what it is.
		//
		// Note: The first rule has a value of false since
		// we don't want to mess with words that end with
		// double 's'. We normally wouldn't have to create
		// rules for words we don't want to mess with, but
		// the last rule (s) would catch double (ss) words
		// if we didn't stop before it got to that rule.
		$rules = array(
			'ss' => false,
			'os' => 'o',
			'ies' => 'y',
			'xes' => 'x',
			'oes' => 'o',
			'ves' => 'f',
			's' => '');
		// Loop through all the rules and do the replacement.
		foreach (array_keys($rules) as $key) {
			// If the end of the word doesn't match the key,
			// it's not a candidate for replacement. Move on
			// to the next plural ending.
			if (substr($word, (strlen($key) * -1)) != $key)
				continue;
			// If the value of the key is false, stop looping
			// and return the original version of the word.
			if ($key === false)
				return $word;
			// We've made it this far, so we can do the
			// replacement.
			return substr($word, 0, strlen($word) - strlen($key)) . $rules[$key];
		}
		return $word;
	}
}