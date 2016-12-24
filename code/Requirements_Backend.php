<?php

namespace Stampy;
use Config;
use Debug;
use Controller;

class Requirements_Backend extends \Requirements_Backend {
	/**
	 * @var Stampy\CSSCrush
	 */
	protected $cssCrush;

	public function __construct() {
		$this->cssCrush = singleton('Stampy\CSSCrush');
		$this->cssCrush->init();
	}

	/**
	 * {@inheritdoc}
	 */
	public function css($filename, $media = null) {
		$ret = $this->cssCrush->css($filename, $media);
		if ($ret === false) {
			// If false, fallback to regular Requirements_Backend behaviour
			return parent::css($filename, $media);
		}
		$this->css[$ret['filepath']] = array(
			"media" => $ret['media'],
		);
	}
}
