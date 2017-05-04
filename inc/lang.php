<?php
// *** LICENSE ***
//
// This file is part of C60.
// Since 2016, by Timo Van Neerden.
// C60 is free software, under MIT/X11 Licence.

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
