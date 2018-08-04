<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

$GLOBALS['langs'] = array("fr" => 'Français', "en" => 'English');

if (empty($GLOBALS['lang'])) $GLOBALS['lang'] = '';

switch ($GLOBALS['lang']) {
	case 'fr':
		include_once('lang/fr_FR.php');
		break;
	case 'en':
		include_once('lang/en_EN.php');
		break;
	default:
		include_once('lang/fr_FR.php');
}
