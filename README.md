# StampyCSS

Add support for CrushCSS using the Silverstripe Requirements Backend.
This module comes with CSSCrush decoupled from the Requirements_Backend so you can integrate it with your
own custom solutions.

![stampy](https://cloud.githubusercontent.com/assets/3859574/21466336/9ce0c92c-ca1b-11e6-8eca-4f62a6c9e8bb.jpg)

# Quick Install

1) Put module in root folder
2) Put the following in your _config.php
```php
Requirements::set_backend(new Stampy\Requirements_Backend());
```

# Enable Source Maps

1) Update your YML so source map files will be generated
```yml
Stampy\CSSCrush:
  options:
  	source_map: true
```

2) Update your assets/.htaccess to allow the "map" file extension.
Change from something like this:
```htaccess
Deny from all
<FilesMatch "\.(?i:html|htm|xhtml|js|css|bmp|png|gif|jpg|jpeg|ico|pcx|tif|tiff|au|mid|midi|mpa|mp3|ogg|m4a|ra|wma|wav|cda|avi|mpg|mpeg|asf|wmv|m4v|mov|mkv|mp4|ogv|webm|swf|flv|ram|rm|doc|docx|dotx|dotm|txt|rtf|xls|xlsx|xltx|xltm|pages|ppt|pptx|potx|potm|pps|csv|cab|arj|tar|zip|zipx|sit|sitx|svg|gz|tgz|bz2|ace|arc|pkg|dmg|hqx|jar|xml|pdf|gpx|kml)$">
	Allow from all
</FilesMatch>
```
To this:
```htaccess
Deny from all
<FilesMatch "\.(?i:html|htm|xhtml|js|css|bmp|png|gif|jpg|jpeg|ico|pcx|tif|tiff|au|mid|midi|mpa|mp3|ogg|m4a|ra|wma|wav|cda|avi|mpg|mpeg|asf|wmv|m4v|mov|mkv|mp4|ogv|webm|swf|flv|ram|rm|doc|docx|dotx|dotm|txt|rtf|xls|xlsx|xltx|xltm|pages|ppt|pptx|potx|potm|pps|csv|cab|arj|tar|zip|zipx|sit|sitx|svg|gz|tgz|bz2|ace|arc|pkg|dmg|hqx|jar|xml|pdf|gpx|kml|map)$">
	Allow from all
</FilesMatch>
```

# Extending CSS Crush

With CSSCrush, you're able to add custom functions to the preprocessor.

```yml
Stampy\CSSCrush:
  extensions:
    [CSSCrushExtension]
```

```php
<?php 

class CSSCrushExtension extends \Extension {
	public function onInit() {
		csscrush_add_function('px2vw', array($this, 'px2vw'));
		csscrush_add_function('px2vh', array($this, 'px2vh'));
	}

	public function px2vw(array $arguments, $context) {
		if (!isset($arguments[1])) {
			throw InvalidArgumentException(__FUNCTION__.' requires 2 parameters.');
		}
		$dimen = (int)$arguments[0];
		$screenDimen = (int)$arguments[1];
		$result = ($dimen / $screenDimen) * 100;
		return $result.'vw';
	}

	public function px2vh(array $arguments, $context) {
		if (!isset($arguments[1])) {
			throw InvalidArgumentException(__FUNCTION__.' requires 2 parameters.');
		}
		$dimen = (int)$arguments[0];
		$screenDimen = (int)$arguments[1];
		$result = ($dimen / $screenDimen) * 100;
		return $result.'vh';
	}
}
```

# Configuration

```yml
Stampy\CSSCrush:
  options:
    plugins:
      - 'px2em'
```

# Roadmap
- Add 'updateOptions' extend to CSSCrush. Mainly so you can potentially use variables from SiteConfig or similar. This will require changing the option system to only store options on /dev/build or post-flush to ensure compatibility with caching.

## Credits

CSS Crush 2.4.x by Peteboere
Version: https://github.com/peteboere/css-crush/commit/9dc087f64371c772a2aa36284623185e76ed8c50

