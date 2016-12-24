<?php

namespace Stampy;
use Config;
use Debug;

/**
 * If in development mode, rebuild CSS files, even on cached pages.
 */
class DevDynamicCacheExtension extends \DynamicCacheExtension {
	public function updateEnabled(&$enabled) {
		$cssCrush = singleton('Stampy\CSSCrush');
		$cssCrush->recompileTrackedCSS();
    }
}
