<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

require_once 'inc/boot.php';
operate_session();
setcookie('lastAccessRss', time(), time()+365*24*60*60, null, null, false, true);
$GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);


/* Returns the HTML list with the feeds (the left panel with the sites, not the posts themselves) */
function feed_list_html() {
	// counts unread feeds in DB
	$feeds_nb = rss_count_feed();
	$total_unread = $total_favs = $total_today = 0;
	foreach ($feeds_nb as $feed) {
		$total_unread += $feed['nbrun'];
		$total_favs += $feed['nbfav'];
		$total_today += $feed['nbtoday'];
	}


	// first item : special buttons (all feeds ; favs ; today)
	$html = "\t\t".'<li class="special"><ul>'."\n";

		// all feeds
		$html .= "\t\t\t".'<li class="all-feeds active-site" id="global-post-counter" data-nbrun="'.$total_unread.'"><a href="#" onclick="return RssWall.sortAll();">'.$GLOBALS['lang']['rss_label_all_feeds'].'</a></li>'."\n";

		// today items
		$html .= "\t\t\t".'<li class="today-feeds" id="today-post-counter" data-nbrun="'.$total_today.'"><a href="#" onclick="return RssWall.sortToday();">'.$GLOBALS['lang']['rss_label_today_feeds'].'</a></li>'."\n";

		// favorites items
		$html .= "\t\t\t".'<li class="fav-feeds" id="favs-post-counter" data-nbrun="'.$total_favs.'"><a href="#" onclick="return RssWall.sortFavs();">'.$GLOBALS['lang']['rss_label_favs_feeds'].'</a></li>'."\n";

	$html .= "\t\t".'</ul></li>'."\n";


	$feed_urls = array();
	foreach ($feeds_nb as $i => $feed) {
		$feed_urls[$feed['bt_feed']] = $feed;
	}

	// sort feeds by folder
	$folders = array();
	foreach ($GLOBALS['liste_flux'] as $i => $feed) {
		$feed['nbrun'] = (isset($feed_urls[$feed['link']]['nbrun']) ? $feed_urls[$feed['link']]['nbrun'] : 0);
		$folders[$feed['folder']][] = $feed;
	}

	krsort($folders);

	// creates html : lists RSS feeds without folder separately from feeds with a folder
	foreach ($folders as $i => $folder) {
		$li_html = "";
		$folder_count = 0;
		$folder_count_today = 0;
		foreach ($folder as $j => $feed) {
			$li_html .= "\t\t\t\t".'<li class="feed-site" data-nbrun="'.$feed['nbrun'].'" data-feed-hash="'.crc32($feed['link']).'" title="'.$feed['link'].'">';
			$li_html .= '<a href="#" '.(($feed['iserror'] > 2) ? 'class="feed-error" ': '' ).'onclick="return RssWall.sortItemsBySite(\''.crc32($feed['link']).'\');" style="background-image: url('.URL_ROOT.'favatar.php?w=favicon&amp;q='.parse_url($feed['link'], PHP_URL_HOST).')">'.htmlspecialchars($feed['title']).'</a>';
			$li_html .= '</li>'."\n";
			$folder_count += $feed['nbrun'];
		}

		if ($i != '') {
			$html .= "\t\t".'<li class="feed-folder" data-nbrun="'.$folder_count.'" data-folder="'.$i.'">'."\n";
			$html .= "\t\t\t".'<span class="feed-folder-title">'."\n";
			$html .= "\t\t\t\t".'<a href="#" onclick="return hideFolder(this)" class="unfold"></a>'."\n";
			$html .= "\t\t\t\t".'<a href="#" onclick="return RssWall.sortItemsByFolder(\''.$i.'\');" class="foldername">'.$i.'</a>'."\n";
			$html .= "\t\t\t".'</span>'."\n";
			$html .= "\t\t\t".'<ul>'."\n";
		}
		$html .= $li_html;
		if ($i != '') {
			$html .= "\t\t\t".'</ul>'."\n";
			$html .= "\t\t".'</li>'."\n";
		}

	}
	return $html;
}


/* form config RSS feeds: allow changing feeds (title, url) or remove a feed */
function afficher_form_rssconf($errors='') {
	if (!empty($errors)) {
		echo erreurs($errors);
	}
	$out = '';
	// form add new feed.
	/*
	$out .= '<form id="form-rss-add" method="post" action="feed.php?config">'."\n";
	$out .= '<fieldset class="pref">'."\n";
	$out .= '<legend class="legend-link">'.$GLOBALS['lang']['label_feed_ajout'].'</legend>'."\n";
	$out .= "\t\t\t".'<label for="new-feed">'.$GLOBALS['lang']['label_feed_new'].':</label>'."\n";
	$out .= "\t\t\t".'<input id="new-feed" name="new-feed" type="text" class="text" value="" placeholder="http://www.example.org/rss">'."\n";
	$out .= '<p class="submit-bttns">'."\n";
	$out .= "\t".'<button class="submit button-submit" type="submit" name="send">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
	$out .= '</p>'."\n";
	$out .= hidden_input('token', new_token());
	$out .= hidden_input('verif_envoi', 1);
	$out .= '</fieldset>'."\n";
	$out .= '</form>'."\n";
	*/

	// Form edit + list feeds.
	$out .= '<form id="form-rss-config" method="post" action="index.php?config">'."\n";
	foreach($GLOBALS['liste_flux'] as $i => $flux) {
		$out .= '<div class="feed-item">'."\n";
		$out .= "\t".'<p'.( ($flux['iserror'] > 2) ? ' class="feed-error" title="('.$flux['iserror'].' last requests were errors.)" ' : ''  ).'>'."\n";
		$out .= "\t\t".'<label for="i_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_titre_flux'].'</label>'."\n";
		$out .= "\t\t".'<input id="i_'.$flux['checksum'].'" name="i_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['title']).'">'."\n";
		$out .= "\t".'</p>'."\n";
		$out .= "\t".'<p>'."\n";
		$out .= "\t\t".'<label for="j_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_url_flux'].'</label>'."\n";
		$out .= "\t\t".'<input id="j_'.$flux['checksum'].'" name="j_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['link']).'">'."\n";
		$out .= "\t".'</p>'."\n";
		$out .= "\t".'<p>'."\n";
		$out .= "\t\t".'<label for="l_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_dossier'].'</label>'."\n";
		$out .= "\t\t".'<input id="l_'.$flux['checksum'].'" name="l_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['folder']).'">'."\n";
		$out .= "\t\t".'<input class="remove-feed" name="k_'.$flux['checksum'].'" type="hidden" value="1">'."\n";
		$out .= "\t".'</p>'."\n";
		$out .= "\t".'<p>'."\n";
		$out .= "\t\t".'<button type="button" class="submit button-cancel" onclick="unMarkAsRemove(this)">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
		$out .= "\t\t".'<button type="button" class="submit button-delete" onclick="markAsRemove(this)">'.$GLOBALS['lang']['supprimer'].'</button>'."\n";
		$out .= "\t".'</p>';
		$out .= '</div>'."\n";
	}
	$out .= '<p class="submit-bttns">'."\n";
	$out .= "\t".'<button class="submit button-submit" type="submit" name="send">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
	$out .= '</p>'."\n";
	$out .= hidden_input('token', new_token());
	$out .= hidden_input('verif_envoi', 1);
	$out .= '</form>'."\n";

	return $out;
}


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


$html_sub_menu = '';
if (!isset($_GET['config'])) {
	$html_sub_menu .= "\t".'<div id="sub-menu">'."\n";
	$html_sub_menu .= "\t\t".'<span id="count-posts"><span id="counter"></span></span>'."\n";
	$html_sub_menu .= "\t\t".'<span id="message-return"></span>'."\n";
	$html_sub_menu .= "\t\t".'<ul class="rss-menu-buttons sub-menu-buttons">'."\n";
	$html_sub_menu .= "\t\t\t".'<li><button type="button" id="refreshAll" title="'.$GLOBALS['lang']['rss_label_refresh'].'"></button></li>'."\n";
	$html_sub_menu .= "\t\t\t".'<li><button type="button" onclick="goToUrl(\'?config\')" title="'.$GLOBALS['lang']['rss_label_config'].'"></button></li>'."\n";
	$html_sub_menu .= "\t\t\t".'<li><button type="button" onclick="goToUrl(\'maintenance.php#form_import\')" title="Import/export"></button></li>'."\n";
	$html_sub_menu .= "\t\t\t".'<li><button type="button" id="deleteOld" title="'.$GLOBALS['lang']['rss_label_clean'].'"></button></li>'."\n";
	$html_sub_menu .= "\t\t".'</ul>'."\n";
	$html_sub_menu .= "\t".'</div>'."\n";
	$html_sub_menu .= "\t".'<button type="button" id="fab" class="add-feed" title="'.$GLOBALS['lang']['rss_label_config'].'">'.$GLOBALS['lang']['rss_label_addfeed'].'</button>'."\n";
}

// DEBUT PAGE
afficher_html_head($GLOBALS['lang']['mesabonnements'], "feeds");
afficher_topnav($GLOBALS['lang']['mesabonnements'], $html_sub_menu); #top

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";
$out_html = '';

if (isset($_GET['config'])) {
	$out_html .=  afficher_form_rssconf($erreurs);
	$out_html .=  "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
	$out_html .=  "\n".'<script type="text/javascript">'."\n";
	$out_html .=  'var token = \''.new_token().'\';'."\n";
	$out_html .=  'var scrollPos = 0;'."\n";
	$out_html .=  'window.addEventListener(\'scroll\', function(){ scrollingFabHideShow() });'."\n";
	$out_html .=  php_lang_to_js(0);
	$out_html .=  '

	var fabButton = document.getElementById(\'fab\');
	fabButton.addEventListener(\'click\', addNewFeed);

	function addNewFeed() {
		var newLink = window.prompt(BTlang.rssJsAlertNewLink, \'\');
		if (!newLink) return false;
		var newFolder = window.prompt(BTlang.rssJsAlertNewLinkFolder, \'\');
		var xhr = new XMLHttpRequest();
		xhr.open(\'POST\', \'_rss.ajax.php\');
		xhr.onload = function() {
			location.reload();
		};

		xhr.onerror = function(e) {
			alert(e);
		};
		// prepare and send FormData
		var formData = new FormData();
		formData.append(\'token\', token);
		formData.append(\'add-feed\', newLink);
		formData.append(\'add-feed-folder\', newFolder);
		xhr.send(formData);
		return false;
	}';

	$out_html .=  "\n".'</script>'."\n";
	echo $out_html;
}

else {
	// list of websites
	$out_html .= "\t\t".'<ul id="feed-list">'."\n";
	$out_html .= feed_list_html();
	$out_html .= "\t\t".'</ul>'."\n";

	// get list of posts from DB
	// send to browser
	$out_html .= send_rss_json($tableau, true);
	$out_html .= '<div id="rss-list">'."\n";
	$out_html .= "\t".'<div id="posts-wrapper">'."\n";
	$out_html .= "\t\t".'<div id="post-list-wrapper">'."\n";
	$out_html .= "\t\t\t".'<div id="post-list-title">'."\n";
	$out_html .= "\t\t\t".'<ul class="rss-menu-buttons sub-menu-buttons">'."\n";
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

	$out_html .=  "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
	$out_html .=  "\n".'<script type="text/javascript">'."\n";
	$out_html .=  'var token = \''.new_token().'\';'."\n";
	$out_html .=  'var RssWall = new RssReader();'."\n";
	$out_html .=  'var scrollPos = 0;'."\n";
	$out_html .=  'window.addEventListener(\'scroll\', function(){ scrollingFabHideShow() });'."\n";

	$out_html .=  php_lang_to_js(0);
	$out_html .=  "\n".'</script>'."\n";

	echo $out_html;
}

footer($begin);
