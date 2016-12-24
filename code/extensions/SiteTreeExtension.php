<?php

namespace Stampy;
use Config;
use Debug;

/**
 * Rebuild CSS files on dev/build.
 */
class SiteTreeExtension extends \SiteTreeExtension {
	public function requireDefaultRecords() {
		if ($this->owner->class !== 'SiteTree') {
			// Avoid each subclass of SiteTree executing this logic.
			return;
		}
		$cssCrush = singleton('Stampy\CSSCrush');
		$cssCrush->requireDefaultRecords();
    }
}
