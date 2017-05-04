<?php
// *** LICENSE ***
//
// This file is part of C60.
// Since 2016, by Timo Van Neerden.
// C60 is free software, under MIT/X11 Licence.


function afficher_html_head($titre) {
	$html = '<!DOCTYPE html>'."\n";
	$html .= '<html>'."\n";
	$html .= '<head>'."\n";
	$html .= "\t".'<meta charset="UTF-8" />'."\n";
	$html .= "\t".'<link type="text/css" rel="stylesheet" href="style/style.css.php" />'."\n";
	$html .= "\t".'<meta name="viewport" content="initial-scale=1.0, user-scalable=yes" />'."\n";
	$html .= "\t".'<title>'.$titre.' | '.BLOGOTEXT_NAME.'</title>'."\n";
	$html .= '</head>'."\n";
	$html .= '<body id="body">'."\n\n";
	echo $html;
}

function footer($begin_time='') {
	$msg = '';
	if ($begin_time != '') {
		$dt = round((microtime(TRUE) - $begin_time),6);
		$msg = ' - '.$GLOBALS['lang']['rendered'].' '.$dt.' s '.$GLOBALS['lang']['using'].' '.DBMS;
	}

	$html = '</div>'."\n";
	$html .= '</div>'."\n";
	$html .= '<p id="footer"><a href="'.BLOGOTEXT_SITE.'">'.BLOGOTEXT_NAME.' '.BLOGOTEXT_VERSION.'</a>'.$msg.'</p>'."\n";
	$html .= '</body>'."\n";
	$html .= '</html>'."\n";
	echo $html;
}

/// menu haut panneau admin /////////
function afficher_topnav($titre) {
	$tab = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME);
	if (strlen($titre) == 0) $titre = BLOGOTEXT_NAME;
	$html = '';
	$html .= '<div id="nav">'."\n";
	$html .=  "\t".'<ul>'."\n";
	$html .=  "\t\t".'<li><a href="'.$GLOBALS['racine'].'" id="lien-rss">'.$GLOBALS['lang']['mesabonnements'].'</a></li>'."\n";
	$html .=  "\t".'</ul>'."\n";
	$html .=  '</div>'."\n";

	$html .=  '<h1>'.$titre.'</h1>'."\n";

	$html .=  '<div id="nav-acc">'."\n";
	$html .=  "\t".'<ul>'."\n";
	$html .=  "\t\t".'<li><a href="preferences.php" id="lien-preferences">'.$GLOBALS['lang']['preferences'].'</a></li>'."\n";
	$html .=  "\t\t".'<li><a href="'.$GLOBALS['racine'].'" id="lien-rss">'.$GLOBALS['lang']['mesabonnements'].'</a></li>'."\n";
	$html .=  "\t\t".'<li><a href="logout.php" id="lien-deconnexion">'.$GLOBALS['lang']['deconnexion'].'</a></li>'."\n";
	$html .=  "\t".'</ul>'."\n";
	$html .=  '</div>'."\n";
	echo $html;
}

function confirmation($message) {
	echo '<div class="confirmation">'.$message.'</div>'."\n";
}

function no_confirmation($message) {
	echo '<div class="no_confirmation">'.$message.'</div>'."\n";
}

function label($for, $txt) {
	return '<label for="'.$for.'">'.$txt.'</label>'."\n";
}

function info($message) {
	return '<p class="info">'.$message.'</p>'."\n";
}

function erreurs($erreurs) {
	$html = '';
	if ($erreurs) {
		$html .= '<div id="erreurs">'.'<strong>'.$GLOBALS['lang']['erreurs'].'</strong> :' ;
		$html .= '<ul><li>';
		$html .= implode('</li><li>', $erreurs);
		$html .= '</li></ul></div>'."\n";
	}
	return $html;
}

function erreur($message) {
	  echo '<p class="erreurs">'.$message.'</p>'."\n";
}

function question($message) {
	  echo '<p id="question">'.$message.'</p>';
}

function afficher_msg() {
	// message vert
	if (isset($_GET['msg'])) {
		if (array_key_exists(htmlspecialchars($_GET['msg']), $GLOBALS['lang'])) {
			$suffix = (isset($_GET['nbnew'])) ? htmlspecialchars($_GET['nbnew']).' '.$GLOBALS['lang']['rss_nouveau_flux'] : ''; // nb new RSS
			confirmation($GLOBALS['lang'][$_GET['msg']].$suffix);
		}
	}
	// message rouge
	if (isset($_GET['errmsg'])) {
		if (array_key_exists($_GET['errmsg'], $GLOBALS['lang'])) {
			no_confirmation($GLOBALS['lang'][$_GET['errmsg']]);
		}
	}
}


function moteur_recherche() {
	$requete='';
	if (isset($_GET['q'])) {
		$requete = htmlspecialchars(stripslashes($_GET['q']));
	}
	$return = '<form action="?" method="get" id="search">'."\n";
	$return .= '<input id="q" name="q" type="search" size="20" value="'.$requete.'" placeholder="'.$GLOBALS['lang']['placeholder_search'].'" accesskey="f" />'."\n";
//	$return .= '<label for="q">'.'</label>'."\n";
	$return .= '<button id="input-rechercher" type="submit">'.$GLOBALS['lang']['rechercher'].'</button>'."\n";
	if (isset($_GET['mode'])) {
		$return .= '<input id="mode" name="mode" type="hidden" value="'.htmlspecialchars(stripslashes($_GET['mode'])).'"/>'."\n";
	}
	$return .= '</form>'."\n\n";
	return $return;
}



/* From DB : returns a HTML list with the feeds (the left panel) */
function feed_list_html() {
	// counts unread feeds in DB
	$feeds_nb = rss_count_feed();
	$total_unread = $total_favs = 0;
	foreach ($feeds_nb as $feed) {
		$total_unread += $feed['nbrun'];
		$total_favs += $feed['nbfav'];
	}

	// First item : link all feeds
	$html = "\t\t".'<li class="all-feeds active-site"><a href="#" onclick="return RssWall.sortAll();">'.$GLOBALS['lang']['rss_label_all_feeds'].' <span id="global-post-counter" data-nbrun="'.$total_unread.'" class="counter">('.$total_unread.')</span></a></li>'."\n";

	// Next item : favorites items
	$html .= "\t\t".'<li class="fav-feeds"><a href="#" onclick="return RssWall.sortFavs();">'.$GLOBALS['lang']['rss_label_favs_feeds'].' <span id="favs-post-counter" data-nbrun="'.$total_favs.'" class="counter">('.$total_favs.')</span></a></li>'."\n";

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
		foreach ($folder as $j => $feed) {
			$li_html .= "\t\t".'<li class="feed-site" data-nbrun="'.$feed['nbrun'].'" data-feed-hash="'.crc32($feed['link']).'" title="'.$feed['link'].'">';
			$li_html .= '<a href="#" '.(($feed['iserror'] > 2) ? 'class="feed-error" ': ' ' ).'onclick="return RssWall.sortItemsBySite(\''.crc32($feed['link']).'\');" style="background-image: url(cache/get.php?w=favicon&amp;q='.parse_url($feed['link'], PHP_URL_HOST).')">'.$feed['title'].'</a>';
			$li_html .= '<span class="counter">('.$feed['nbrun'].')</span>';
			$li_html .= '</li>'."\n";
			$folder_count += $feed['nbrun'];
		}

		if ($i != '') {
			$html .= "\t\t".'<li class="feed-folder" data-nbrun="'.$folder_count.'" data-folder="'.$i.'">'."\n";
			$html .= "\t\t\t".'<span class="feed-folder-title">'."\n";
			$html .= "\t\t\t\t".'<a href="#" onclick="return RssWall.sortItemsByFolder(\''.$i.'\');">'.$i.'<span class="counter">('.$folder_count.')</span></a>'."\n";
			$html .= "\t\t\t\t".'<a href="#" onclick="return hideFolder(this)" class="unfold">unfold</a>'."\n";
			$html .= "\t\t\t".'</span>'."\n";
			$html .= "\t\t\t".'<ul>'."\n\t\t";
		}
		$html .= $li_html;
		if ($i != '') {
			$html .= "\t\t\t".'</ul>'."\n";
			$html .= "\t\t".'</li>'."\n";
		}

	}
	return $html;
}


function php_lang_to_js($a) {
	$frontend_str = array();
	$frontend_str['rssJsAlertNewLink'] = $GLOBALS['lang']['rss_jsalert_new_link'];
	$frontend_str['rssJsAlertNewLinkFolder'] = $GLOBALS['lang']['rss_jsalert_new_link_folder'];
	$frontend_str['confirmFeedClean'] = $GLOBALS['lang']['confirm_feed_clean'];
	$frontend_str['errorPhpAjax'] = $GLOBALS['lang']['error_phpajax'];
	$frontend_str['questionQuitPage'] = $GLOBALS['lang']['question_quit_page'];
	$frontend_str['questionCleanRss'] = $GLOBALS['lang']['question_clean_rss'];

	$sc = 'var BTlang = '.json_encode($frontend_str).';';

	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}
