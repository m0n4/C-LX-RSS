<?php
// *** LICENSE ***
//
// This file is part of C60.
// Since 2016, by Timo Van Neerden.
// C60 is free software, under MIT/X11 Licence.

// Set encoding to UTF-8

// it's not for 0.00000002 sec ...
$begin = microtime(true);

// Use UTF-8 for all
mb_internal_encoding('UTF-8');

define('BT_ROOT', dirname(__dir__).'/');

// if dev mod
error_reporting(-1);

/**
 * Import several .ini config files with this function
 * and make ini var as a php constant
 */
function import_ini_file($file_path) {
	if (is_file($file_path) and is_readable($file_path)) {
		$options = parse_ini_file($file_path);
		foreach ($options as $option => $value) {
			if (!defined($option)) {
				define($option, $value);
			}
		}
		return true;
	}
	return false;
}



// Constants: folders
define('DIR_DATABASES', BT_ROOT.'databases/');
define('DIR_CONFIG', BT_ROOT.'config/');
define('DIR_BACKUP', BT_ROOT.'backup/');
define('DIR_CACHE', BT_ROOT.'cache/');
// Constants: databases
define('FEEDS_DB', DIR_DATABASES.'rss.php');
define('SQL_DB', DIR_DATABASES.'database.sqlite'); // RSS-feeds list info storage.

// Constants: installation configurations
define('FILE_USER', DIR_CONFIG.'user.ini');
define('FILE_SETTINGS', DIR_CONFIG.'prefs.php');
define('FILE_SETTINGS_ADV', DIR_CONFIG.'config-advanced.ini');
define('FILE_MYSQL', DIR_CONFIG.'mysql.ini');

// Constants: general
define('BLOGOTEXT_NAME', 'CLX');
define('BLOGOTEXT_SITE', 'https://github.com/timovn/C-LX-RSS');
define('BLOGOTEXT_VERSION', '0.0.1');
define('MINIMAL_PHP_REQUIRED_VERSION', '5.5');
define('BLOGOTEXT_UA', 'Mozilla/5.0 (Windows NT 10; WOW64; rv:47.0) Gecko/20100101 Firefox/55.0');



// ADVANCED CONFIG OPTIONS
import_ini_file(FILE_SETTINGS_ADV);

// DATABASE OPTIONS + MySQL DB PARAMS
import_ini_file(FILE_MYSQL);

// USER LOGIN + PW HASH
import_ini_file(FILE_USER);

// USER PREFS
if (file_exists(FILE_SETTINGS)) { require_once FILE_SETTINGS; }

/**
 * main dependancys
 */

require_once BT_ROOT.'inc/lang.php';
require_once BT_ROOT.'inc/util.php';
require_once BT_ROOT.'inc/fich.php';
require_once BT_ROOT.'inc/html.php' ;
require_once BT_ROOT.'inc/form.php';
require_once BT_ROOT.'inc/conv.php';
require_once BT_ROOT.'inc/veri.php';
require_once BT_ROOT.'inc/sqli.php';

