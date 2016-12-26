<?php

namespace Stampy;
use Object;
use Config;
use Debug;
use Requirements;
use SS_Cache;
use Flushable;

class CSSCrush extends Object implements Flushable {
	const CACHE_KEY_PREFIX = 'csscrush';

	/**
	 * Change settings on CSSCrush
	 *
	 *	'plugins' => array(
	 *		'px2em',
	 *	),
	 * 
	 * @see https://github.com/peteboere/css-crush/blob/master/docs/api/options.md
	 */
	private static $options = array(
	);

	/**
	 * Track built CSS files.
	 *
	 * @var array
	 */
	protected $css_tracked = array();

	/**
	 * @var array
	 */
	protected $_options = null;

	/**
	 * Check if $this->init() has been called
	 *
	 * @var boolean
	 */
	private $_has_init = false;

	/**
	 * Stops recompilation of files twice in one request
	 *
	 * @var boolean
	 */
	private $_has_recompiled_css = false;

	/**
	 * Tracked CSS files from cache (from a previous request)
	 *
	 * @var array
	 */
	private $_cache_css_tracked = array();

	/**
	 * To be used with the provided or your own alternate Requirements_Backend
	 *
	 * @return array|false
	 */
	public function css($filename, $media = null) {
		$theme = $this->getTheme();
		// NOTE(Jake): Perhaps disable in the CMS by default?
		//if (!$theme) {
		//	return false;
		//}
		$outputFilepath = $this->processCSS($filename);
		$this->css_tracked[$theme][$filename] = array(
			'output_file' => BASE_PATH.'/'.$outputFilepath,
			'media' => $media
		);
		return array('filepath' => $outputFilepath, 'media' => $media);
	}

	/**
	 * 
	 */
	public function __construct() {
		parent::__construct();

		// Avoid inclusion if it's been included with Composer
		if (!class_exists('CssCrush\Version')) {
			include(BASE_PATH.'/'.Utility::MODULE_DIR.'/thirdparty/css-crush/CssCrush.php');
		}
	}

	/**
	 * Called during /dev/build
	 */
	public function requireDefaultRecords() {
		// todo(Jake): Properly implement and test this.
		//			   Make options update on flush. This will
		//			   give variable access from data without slowing
		//			   down caching support.
		//
		//$cssCrush->flushOptions();
		//$cssCrush->extend('updateOptions', $options);
		$this->recompileTrackedCSS();

		// NOTE(Jake): Having this might be nice?
		//$this->extend('requireDefaultRecords');
	}

	/**
	 * Called during ?flush=all
	 */
	public static function flush() {
		$self = singleton(__CLASS__);
		$self->recompileTrackedCSS();

		// NOTE(Jake): Having this might be nice?
		//$self->extend('flush');
	}

	/**
	 * Initialize additional configurations for CSSCrush.
	 */
	public function init() {
		if ($this->_has_init === true) {
			user_error("$this->class should only call init() once.", E_USER_ERROR);
			return;
		}
		$this->extend('onInit');
		$this->_has_init = true;
	}

	/**
	 * Process a CSS file with CSSCrush.
	 *
	 * @var string
	 */
	public function processCSS($filename, $options = array()) {
		$filepath = BASE_PATH.'/'.$filename;
		$outputFilename = Utility::sanitise_filepath(dirname($filename).'/').basename($filename);
		$outputFilepath = Requirements::backend()->getCombinedFilesFolder().'/'.$outputFilename;
		$combinedOptions = array_merge($this->getOptions(), array(
			'output_file' => $outputFilename,
		), $options);
		$url = csscrush_file($filepath, $combinedOptions);
		return $outputFilepath;
	}

	/**
	 * @return array
	 */
	public function getOptions() {
		if ($this->_options !== null) {
			return $this->_options;
		}
		$options = Config::inst()->get(__CLASS__, 'options');
		$options = array_merge(array(
			'output_dir' => BASE_PATH.'/'.Requirements::backend()->getCombinedFilesFolder(),
		), $options);
		$this->_options = $options;
		return $this->_options;
	}

	/**
	 * Get cache object
	 *
	 * @return Zend_Cache_Core
	 */ 
	protected function getCache() {
		// todo(Jake): Allow a custom backend (ie. Redis?)
		$backend = 'CSSCrush';
		// todo(Jake): Allow cacheDir to be configurable
		$cacheDir = 'csscrush';
		$cacheDir = TEMP_FOLDER . DIRECTORY_SEPARATOR . $cacheDir;
		if (!is_dir($cacheDir)) {
			mkdir($cacheDir);
		}
		SS_Cache::add_backend('CSSCrushStore', 'File', array('cache_dir' => $cacheDir));
		SS_Cache::pick_backend('CSSCrushStore', $backend, 1000);

		// Set lifetime, allowing for 0 (infinite) lifetime
		$lifetime = self::config()->cacheDuration;
		if ($lifetime !== null) {
			SS_Cache::set_cache_lifetime($backend, $lifetime);
		}
		return SS_Cache::factory($backend);
	}

	/**
	 * Load built CSS files. This function exists so cached pages
	 * can rebuild the CSS without re-running all the page logic.
	 */
	public function recompileTrackedCSS() {
		if ($this->_has_recompiled_css) {
			return;
		}
		$this->_has_recompiled_css = true;
		$cssTracked = $this->loadFromCache();
		if ($cssTracked === false) {
			return;
		}
		if ($cssTracked) {
			// Recompile each file
			foreach ($cssTracked as $filename => $css) {
				$this->processCSS($filename, array('cache' => 0));
			}
		}
	}

	/**
	 * @return array|false
	 */
	protected function loadFromCache() {
		$theme = $this->getTheme();
		if (isset($this->_cache_css_tracked[$theme])) {
			return $this->_cache_css_tracked[$theme];
		}
		$cache = $this->getCache();
		$cssTracked = $cache->load(self::CACHE_KEY_PREFIX.'_'.$theme.'_tracked');
		if (!$cssTracked) {
			return $this->_cache_css_tracked[$theme] = false;
		}
		$cssTracked = json_decode($cssTracked, true);
		if ($cssTracked === false || $cssTracked === null || $cssTracked === true) {
			return $this->_cache_css_tracked[$theme] = false;
		}
		return $this->_cache_css_tracked[$theme] = $cssTracked;
	}

	/**
	 * At the end of the request, store all the built CSS files.
	 *
	 * @see CSSCrush::recompileCSS()
	 */
	public function writeToCache() {
		if (!$this->css_tracked) {
			return;
		}
		// Compare against cache file to avoid unnecessary file writing operations
		$cssTracked = $this->loadFromCache();
		$isDifferent = true;
		if ($cssTracked !== false) {
			$theme = $this->getTheme();
			if (isset($this->css_tracked[$theme])) {
				$isDifferent = $this->isCSSArrayDiff($cssTracked, $this->css_tracked[$theme]);
			}
		}
		if (!$isDifferent) {
			return;
		}
		foreach ($this->css_tracked as $theme => $cssTrackedFiles) {
			$cache = $this->getCache();
			$cache->save(json_encode($cssTrackedFiles), self::CACHE_KEY_PREFIX.'_'.$theme.'_tracked');
		}
	}

	/**
	 * Get theme (or blank if in CMS / no theme)
	 *
	 * @return string
	 */
	protected function getTheme() {
		if (!Config::inst()->get('SSViewer', 'theme_enabled')) {
			return '';
		}
		$result = (string)Config::inst()->get('SSViewer', 'theme');
		return $result;
	}

	/**
	 * Check if two CSS file lists are identical or not.
	 *
	 * @return boolean
	 */
	public function isCSSArrayDiff($arr1, $arr2) {
		$arr1Count = count($arr1);
		$arr2Count = count($arr2);
		if ($arr1Count !== $arr2Count) {
			return true;
		}
		foreach ($arr1 as $key => $value) {
			if (!array_key_exists($key, $arr2)) {
				return true;
			}
			$cssProperties = $arr1[$key];
			$otherCssProperties = $arr2[$key];
			foreach ($cssProperties as $property => $value) {
				if (!array_key_exists($property, $otherCssProperties)) {
					return true;
				}
				if ($cssProperties[$property] != $otherCssProperties[$property]) {
					return true;
				}
			}
		}
		return false;
	}
}
