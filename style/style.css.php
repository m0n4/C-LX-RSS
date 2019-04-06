<?php
// gzip compression
if (extension_loaded('zlib') and ob_get_length() > 0) {
	ob_end_clean();
	ob_start("ob_gzhandler");
}
else {
	ob_start("ob_gzhandler");
}

header("Content-type: text/css; charset: UTF-8");

/* FOR MAINTENANCE: CSS FILES ARE SPLITED IN MULTIPLE FILES
-------------------------------------------------------------*/

echo '/* General styles (layout, forms, multi-pages elements…) */'."\n";
readfile('style-style.css');

echo '/* Auth page */'."\n";
readfile('style-auth.css');

echo '/* RSS page: listing + forms */'."\n";
readfile('style-rss.css');

echo '/* Prefs + maintainance pages */'."\n";
readfile('style-preferences.css');

echo '/* Media-queries < 1100px */'."\n";
readfile('style-mobile-lt1100px.css');

echo '/* Media-queries < 850px */'."\n";
readfile('style-mobile-lt850px.css');

echo '/* Media-queries < 700px */'."\n";
readfile('style-mobile-lt700px.css');

if (is_file('custom-styles.css')) {
	echo '/* User-Custom CSS */'."\n";
	readfile('custom-styles.css');
}
