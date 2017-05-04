<?php
// *** LICENSE ***
//
// This file is part of C60.
// Since 2016, by Timo Van Neerden.
// C60 is free software, under MIT/X11 Licence.

// If no config: go to install process.
if ( !file_exists('config/user.ini') or !file_exists('config/prefs.php') ) {
	header('Location: install.php');
	die;
}

require_once 'inc/inc.php';
operate_session();

$GLOBALS['db_handle'] = open_base();
$GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);

$erreurs = array();
if (isset($_POST['verif_envoi'])) {
	$erreurs = valider_form_rss();
	if (empty($erreurs)) {
		traiter_form_rssconf();
	}
}

$tableau = array();
if (!empty($_GET['q'])) {
	$sql_where_status = '';
	$q_query = $_GET['q'];
	// search "in:read"
	if (substr($_GET['q'], -8) === ' in:read') {
		$sql_where_status = 'AND bt_statut=0 ';
		$q_query = substr($_GET['q'], 0, strlen($_GET['q'])-8);
	}
	// search "in:unread"
	if (substr($_GET['q'], -10) === ' in:unread') {
		$sql_where_status = 'AND bt_statut=1 ';
		$q_query = substr($_GET['q'], 0, strlen($_GET['q'])-10);
	}
	$arr = parse_search($q_query);


	$sql_where = implode(array_fill(0, count($arr), '( bt_content || bt_title ) LIKE ? '), 'AND '); // AND operator between words
	$query = "SELECT * FROM rss WHERE ".$sql_where.$sql_where_status."ORDER BY bt_date DESC";
	//debug($query);
	$tableau = liste_elements($query, $arr, 'rss');
} else {
	$tableau = liste_elements('SELECT * FROM rss WHERE bt_statut=1 OR bt_bookmarked=1 ORDER BY bt_date DESC', array(), 'rss');
}


afficher_html_head($GLOBALS['lang']['mesabonnements']);

echo '<div id="header">'."\n";
	echo '<div id="top">'."\n";
	afficher_msg();
	echo moteur_recherche();
	afficher_topnav($GLOBALS['lang']['mesabonnements']);
	echo '</div>'."\n";

	if (!isset($_GET['config'])) {
		echo "\t".'<div id="sub-menu">'."\n";
		echo "\t\t".'<span id="count-posts"><span id="counter"></span></span>'."\n";
		echo "\t\t".'<span id="message-return"></span>'."\n";
		echo "\t\t".'<ul class="rss-menu-buttons">'."\n";
		echo "\t\t\t".'<li><button type="button" id="refreshAll" title="'.$GLOBALS['lang']['rss_label_refresh'].'"></button></li>'."\n";
		echo "\t\t\t".'<li><button type="button" onclick="goToUrl(\'?config\')" title="'.$GLOBALS['lang']['rss_label_config'].'"></button></li>'."\n";
		echo "\t\t\t".'<li><button type="button" onclick="goToUrl(\'maintenance.php#form_import\')" title="Import/export"></button></li>'."\n";
		echo "\t\t\t".'<li><button type="button" id="deleteOld" title="'.$GLOBALS['lang']['rss_label_clean'].'"></button></li>'."\n";
		echo "\t\t".'</ul>'."\n";
		echo "\t".'</div>'."\n";
		echo '<button type="button" id="fab" class="add-feed" title="'.$GLOBALS['lang']['rss_label_config'].'">'.$GLOBALS['lang']['rss_label_addfeed'].'</button>'."\n";
	}

echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

if (isset($_GET['config'])) {
	echo afficher_form_rssconf($erreurs);
	echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
}

else {
	// get list of posts from DB
	// send to browser
	$out_html = send_rss_json($tableau, true);
	$out_html .= '<div id="rss-list">'."\n";
	$out_html .= "\t".'<div id="posts-wrapper">'."\n";
	$out_html .= "\t\t".'<ul id="feed-list">'."\n";
	$out_html .= feed_list_html();
	$out_html .= "\t\t".'</ul>'."\n";
	$out_html .= "\t\t".'<div id="post-list-wrapper">'."\n";
	$out_html .= "\t\t\t".'<div id="post-list-title">'."\n";
	$out_html .= "\t\t\t".'<ul class="rss-menu-buttons">'."\n";
	$out_html .= "\t\t\t\t".'<li><button type="button" id="markasread" title="'.$GLOBALS['lang']['rss_label_markasread'].'"></button></li>'."\n";
	$out_html .= "\t\t\t\t".'<li><button type="button" id="openallitemsbutton" title="'.$GLOBALS['lang']['rss_label_unfoldall'].'"></button></li>'."\n";
	$out_html .= "\t\t\t".'</ul>'."\n";
	$out_html .= "\t\t\t".'<p><span id="post-counter"></span> '.$GLOBALS['lang']['label_elements'].'</p>'."\n";

	$out_html .= "\t\t\t".'</div>'."\n";

	$out_html .= "\t\t\t".'<ul id="post-list"></ul>'."\n";

	if (empty($GLOBALS['liste_flux'])) {
		$out_html .= $GLOBALS['lang']['rss_nothing_here_note'].'<a href="maintenance.php#form_import">import OPML</a>.';
	}
	$out_html .= "\t\t".'</div>'."\n";
	$out_html .= "\t".'</div>'."\n";
	$out_html .= "\t".'<div class="keyshortcut">'.$GLOBALS['lang']['rss_raccourcis_clavier'].'</div>'."\n";
	$out_html .= '</div>'."\n";

	echo $out_html;

	echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
	echo "\n".'<script type="text/javascript">'."\n";
	echo 'var token = \''.new_token().'\';'."\n";
	echo 'var RssWall = new RssReader();'."\n";
	echo 'var scrollPos = 0;'."\n";
	echo 'window.addEventListener(\'scroll\', function(){ scrollingFabHideShow() });'."\n";

	echo php_lang_to_js(0);
	echo "\n".'</script>'."\n";
}

footer($begin);
