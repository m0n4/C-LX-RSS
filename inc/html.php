<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***


function afficher_html_head($titre, $page_css_class) {
	$html = '<!DOCTYPE html>'."\n";
	$html .= '<html>'."\n";
	$html .= '<head>'."\n";
	$html .= "\t".'<meta charset="UTF-8" />'."\n";
	$html .= "\t".'<link type="text/css" rel="stylesheet" href="style/style.css.php" />'."\n";
	$html .= "\t".'<meta name="viewport" content="initial-scale=1.0, user-scalable=yes" />'."\n";
	$html .= "\t".'<title>'.$titre.' | '.BLOGOTEXT_NAME.'</title>'."\n";
	$html .= '</head>'."\n";
	$html .= '<body id="body" class="'.$page_css_class.'">'."\n\n";
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
	$html .= '</html>';
	echo $html;
}

/// menu haut panneau admin /////////
function afficher_topnav($titre, $html_sub_menu) {
	$tab = pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_BASENAME);
	if (strlen($titre) == 0) $titre = BLOGOTEXT_NAME;

	// TOP MENU

	$html = '<div id="header">'."\n";
	$html .= '<div id="top">'."\n";

	// left nav
	$html .= "\t".'<div id="nav">'."\n";
	$html .= "\t\t".'<ul>'."\n";
	$html .= "\t\t\t".'<li><a href="index.php" id="lien-rss">'.ucfirst($GLOBALS['lang']['label_feeds']).'</a></li>'."\n";
	$html .= "\t\t".'</ul>'."\n";	$html .= "\t".'</div>'."\n";

	// h1 title
	$html .=  "\t".'<h1><a href="'.$tab.'">'.$titre.'</a></h1>'."\n";

	// search field
	if (!in_array($tab, array('preferences.php', 'maintenance.php'))) {
		$html .= moteur_recherche();
	}

	// notif icons
	$html .= get_notifications();

	// right nav
	$html .= "\t".'<div id="nav-acc">'."\n";
	$html .= "\t\t".'<ul>'."\n";
	$html .= "\t\t\t".'<li><a href="preferences.php" id="lien-preferences">'.$GLOBALS['lang']['preferences'].'</a></li>'."\n";
	$html .= "\t\t\t".'<li><a href="index.php" id="lien-site">'.$GLOBALS['lang']['lien_blog'].'</a></li>'."\n";
	$html .= "\t\t\t".'<li><a href="logout.php" id="lien-deconnexion">'.$GLOBALS['lang']['deconnexion'].'</a></li>'."\n";
	$html .= "\t\t".'</ul>'."\n";
	$html .= "\t".'</div>'."\n";

	$html .= '</div>'."\n";

	// SECONDS MENU BAR (for RSS, notes, agenda…)
	$html .= $html_sub_menu;


	// Popup node
	if (isset($_GET['msg']) and array_key_exists($_GET['msg'], $GLOBALS['lang']) ) {
		$message = $GLOBALS['lang'][$_GET['msg']];
		$message .= (isset($_GET['nbnew'])) ? htmlspecialchars($_GET['nbnew']).' '.$GLOBALS['lang']['rss_nouveau_flux'] : ''; // nb new RSS
		$html .= '<div class="confirmation">'.$message.'</div>'."\n";

	} elseif (isset($_GET['errmsg']) and array_key_exists($_GET['errmsg'], $GLOBALS['lang'])) {
		$message = $GLOBALS['lang'][$_GET['errmsg']];
		$html .= '<div class="no_confirmation">'.$message.'</div>'."\n";
	}

	$html .= '</div>'."\n\n";

	echo $html;
}



function get_notifications() {
	$html = '';
	$lis = '';
	$hasNotifs = 0;

	// get last RSS posts
	if (isset($_COOKIE['lastAccessRss']) and is_numeric($_COOKIE['lastAccessRss'])) {
		$query = 'SELECT count(ID) AS nbr FROM rss WHERE bt_date >=?';
		$array = array(date('YmdHis', $_COOKIE['lastAccessRss']));
		$nb_new = liste_elements_count($query, $array);
		if ($nb_new > 0) {
			$hasNotifs += $nb_new;
			$lis .= "\t\t\t".'<li><a href="feed.php">'.$nb_new .' new RSS entries</a></li>'."\n";
		}
	}

	// get last Comments
	if (isset($_COOKIE['lastAccessComments']) and is_numeric($_COOKIE['lastAccessComments'])) {
		$query = 'SELECT count(ID) AS nbr FROM commentaires WHERE bt_id >=?';
		$array = array(date('YmdHis', $_COOKIE['lastAccessComments']));
		$nb_new = liste_elements_count($query, $array);
		if ($nb_new > 0) {
			$hasNotifs += $nb_new;
			$lis .= "\t\t\t".'<li><a href="commentaires.php">'.$nb_new .' new comments</a></li>'."\n";
		}
	}

	// get near events
	if (isset($_COOKIE['lastAccessAgenda']) and is_numeric($_COOKIE['lastAccessAgenda'])) {
		$query = 'SELECT count(ID) AS nbr FROM agenda WHERE bt_date >=? AND bt_date <=?';
		$array = array( date('YmdHis', $_COOKIE['lastAccessAgenda']), date('YmdHis', ($_COOKIE['lastAccessAgenda']+24*60*60)) );
		$nb_new = liste_elements_count($query, $array);
		if ($nb_new > 0) {
			$hasNotifs += $nb_new;
			$lis .= "\t\t\t".'<li><a href="agenda.php">'.$nb_new .' near events</a></li>'."\n";
		}
	}

	$lis .= ($lis) ? '' : "\t\t\t".'<li>'.$GLOBALS['lang']['note_no_notifs'].'</li>'."\n";


	$html .= "\t".'<div id="notif-icon"'.($hasNotifs ? ' class="hasNotifs" data-nb-notifs="'.$hasNotifs.'"' : '').'>'."\n";
	$html .= "\t\t".'<ul>'."\n";
	$html .= $lis;
	$html .= "\t\t".'</ul>'."\n";
	$html .= "\t".'</div>'."\n";

	return $html;
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


function moteur_recherche() {
	$requete='';
	if (isset($_GET['q'])) {
		$requete = htmlspecialchars(stripslashes($_GET['q']));
	}
	$return = "\t".'<form action="?" method="get" id="search">'."\n";
	$return .= "\t\t".'<input id="q" name="q" type="search" size="20" value="'.$requete.'" placeholder="'.$GLOBALS['lang']['placeholder_search'].'" accesskey="f" />'."\n";
	//$return .= "\t\t".'<label for="q">'.'</label>'."\n";
	$return .= "\t\t".'<button id="input-rechercher" type="submit">'.$GLOBALS['lang']['rechercher'].'</button>'."\n";
	if (isset($_GET['mode'])) {
		$return .= "\t\t".'<input id="mode" name="mode" type="hidden" value="'.htmlspecialchars(stripslashes($_GET['mode'])).'"/>'."\n";
	}
	$return .= "\t".'</form>'."\n";
	return $return;
}

function php_lang_to_js($a) {
	$frontend_str = array();
	$frontend_str['maxFilesSize'] = min(return_bytes(ini_get('upload_max_filesize')), return_bytes(ini_get('post_max_size')));
	$frontend_str['rssJsAlertNewLink'] = $GLOBALS['lang']['rss_jsalert_new_link'];
	$frontend_str['rssJsAlertNewLinkFolder'] = $GLOBALS['lang']['rss_jsalert_new_link_folder'];
	$frontend_str['confirmFeedClean'] = $GLOBALS['lang']['confirm_feed_clean'];
	$frontend_str['confirmCommentSuppr'] = $GLOBALS['lang']['confirm_comment_suppr'];
	$frontend_str['confirmNotesSaved'] = $GLOBALS['lang']['confirm_note_enregistree'];
	$frontend_str['confirmEventsSaved'] = $GLOBALS['lang']['confirm_agenda_updated'];
	$frontend_str['activer'] = $GLOBALS['lang']['activer'];
	$frontend_str['desactiver'] = $GLOBALS['lang']['desactiver'];
	$frontend_str['supprimer'] = $GLOBALS['lang']['supprimer'];
	$frontend_str['save'] = $GLOBALS['lang']['enregistrer'];
	$frontend_str['add_title'] = $GLOBALS['lang']['label_add_title'];
	$frontend_str['add_description'] = $GLOBALS['lang']['label_add_description'];
	$frontend_str['add_location'] = $GLOBALS['lang']['label_add_location'];
	$frontend_str['cancel'] = $GLOBALS['lang']['annuler'];
	$frontend_str['errorPhpAjax'] = $GLOBALS['lang']['error_phpajax'];
	$frontend_str['errorCommentSuppr'] = $GLOBALS['lang']['error_comment_suppr'];
	$frontend_str['errorCommentValid'] = $GLOBALS['lang']['error_comment_valid'];
	$frontend_str['questionQuitPage'] = $GLOBALS['lang']['question_quit_page'];
	$frontend_str['questionCleanRss'] = $GLOBALS['lang']['question_clean_rss'];
	$frontend_str['questionSupprComment'] = $GLOBALS['lang']['question_suppr_comment'];
	$frontend_str['questionSupprArticle'] = $GLOBALS['lang']['question_suppr_article'];
	$frontend_str['questionSupprFichier'] = $GLOBALS['lang']['question_suppr_fichier'];
	$frontend_str['questionSupprNote'] = $GLOBALS['lang']['question_suppr_note'];
	$frontend_str['questionSupprEvent'] = $GLOBALS['lang']['question_suppr_event'];
	$frontend_str['notesLabelTitle'] = $GLOBALS['lang']['label_titre'];
	$frontend_str['notesLabelContent'] = $GLOBALS['lang']['label_contenu'];
	$frontend_str['createdOn'] = $GLOBALS['lang']['label_creee_le'];
	$frontend_str['lmmjvsd'] = $GLOBALS['lang']['days_initials'];
	$frontend_str['questionPastEvents'] = $GLOBALS['lang']['question_show_past_events'];
	$frontend_str['entireDay'] = $GLOBALS['lang']['question_entire_day'];

	$sc = 'var BTlang = '.json_encode($frontend_str).';';

	if ($a == 1) {
		$sc = "\n".'<script type="text/javascript">'."\n".$sc."\n".'</script>'."\n";
	}
	return $sc;
}
