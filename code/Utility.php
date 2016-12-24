<?php

namespace Stampy;

class Utility {
	const MODULE_DIR = 'stampycss';

	/**
	 * Strip a file name of special characters
	 *
	 * @param string $name
	 * @return string
	 */
	public static function sanitise_filepath($name) {
		return str_replace(array('~', '.', '/', '!', ' ', "\n", "\r", "\t", '\\', ':', '"', '\'', ';'), '_', $name);
	}
}
