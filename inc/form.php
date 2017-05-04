<?php
// *** LICENSE ***
//
// This file is part of C60.
// Since 2016, by Timo Van Neerden.
// C60 is free software, under MIT/X11 Licence.

/// formulaires GENERIQUES //////////

function form_select($id, $choix, $defaut, $label) {
	$form = '<label for="'.$id.'">'.$label.'</label>'."\n";
	$form .= "\t".'<select id="'.$id.'" name="'.$id.'">'."\n";
	foreach ($choix as $valeur => $mot) {
		$form .= "\t\t".'<option value="'.$valeur.'"'.(($defaut == $valeur) ? ' selected="selected" ' : '').'>'.$mot.'</option>'."\n";
	}
	$form .= "\t".'</select>'."\n";
	$form .= "\n";
	return $form;
}

function form_select_no_label($id, $choix, $defaut) {
	$form = '<select id="'.$id.'" name="'.$id.'">'."\n";
	foreach ($choix as $valeur => $mot) {
		$form .= "\t".'<option value="'.$valeur.'"'.(($defaut == $valeur) ? ' selected="selected" ' : '').'>'.$mot.'</option>'."\n";
	}
	$form .= '</select>'."\n";
	return $form;
}

function hidden_input($nom, $valeur, $id=0) {
	$id = ($id === 0) ? '' : ' id="'.$nom.'"';
	$form = '<input type="hidden" name="'.$nom.'"'.$id.' value="'.$valeur.'" />'."\n";
	return $form;
}

/// formulaires PREFERENCES //////////

function select_yes_no($name, $defaut, $label) {
	$choix = array(
		'1' => $GLOBALS['lang']['oui'],
		'0' => $GLOBALS['lang']['non']
	);
	$form = '<label for="'.$name.'" >'.$label.'</label>'."\n";
	$form .= '<select id="'.$name.'" name="'.$name.'">'."\n" ;
	foreach ($choix as $option => $label) {
		$form .= "\t".'<option value="'.htmlentities($option).'"'.(($option == $defaut) ? ' selected="selected" ' : '').'>'.htmlentities($label).'</option>'."\n";
	}
	$form .= '</select>'."\n";
	return $form;
}

function form_checkbox($name, $checked, $label) {
	$checked = ($checked) ? "checked " : '';
	$form = '<input type="checkbox" id="'.$name.'" name="'.$name.'" '.$checked.' class="checkbox-toggle" />'."\n" ;
	$form .= '<label for="'.$name.'" >'.$label.'</label>'."\n";
	return $form;
}


function form_format_date($defaut) {
	$jour_l = jour_en_lettres(date('d'), date('m'), date('Y'));
	$mois_l = mois_en_lettres(date('m'));
	$formats = array (
		'0' => date('d').'/'.date('m').'/'.date('Y'),             // 05/07/2011
		'1' => date('m').'/'.date('d').'/'.date('Y'),             // 07/05/2011
		'2' => date('d').' '.$mois_l.' '.date('Y'),               // 05 juillet 2011
		'3' => $jour_l.' '.date('d').' '.$mois_l.' '.date('Y'),   // mardi 05 juillet 2011
		'4' => $jour_l.' '.date('d').' '.$mois_l,                 // mardi 05 juillet
		'5' => $mois_l.' '.date('d').', '.date('Y'),              // juillet 05, 2011
		'6' => $jour_l.', '.$mois_l.' '.date('d').', '.date('Y'), // mardi, juillet 05, 2011
		'7' => date('Y').'-'.date('m').'-'.date('d'),             // 2011-07-05
		'8' => substr($jour_l,0,3).'. '.date('d').' '.$mois_l,    // ven. 14 janvier
	);
	$form = "\t".'<label>'.$GLOBALS['lang']['pref_format_date'].'</label>'."\n";
	$form .= "\t".'<select name="format_date">'."\n";
	foreach ($formats as $option => $label) {
		$form .= "\t\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>'."\n";
	}
	$form .= "\t".'</select>'."\n";
	return $form;
}

function form_fuseau_horaire($defaut) {
	$all_timezones = timezone_identifiers_list();
	$liste_fuseau = array();
	$cities = array();
	foreach($all_timezones as $tz) {
		$spos = strpos($tz, '/');
		if ($spos !== FALSE) {
			$continent = substr($tz, 0, $spos);
			$city = substr($tz, $spos+1);
			$liste_fuseau[$continent][] = array('tz_name' => $tz, 'city' => $city);
		}
		if ($tz == 'UTC') {
			$liste_fuseau['UTC'][] = array('tz_name' => 'UTC', 'city' => 'UTC');
		}
	}
	$form = '<label>'.$GLOBALS['lang']['pref_fuseau_horaire'].'</label>'."\n";
	$form .= '<select name="fuseau_horaire">'."\n";
	foreach ($liste_fuseau as $continent => $zone) {
		$form .= "\t".'<optgroup label="'.ucfirst(strtolower($continent)).'">'."\n";
		foreach ($zone as $fuseau) {
			$form .= "\t\t".'<option value="'.htmlentities($fuseau['tz_name']).'"';
			$form .= ($defaut == $fuseau['tz_name']) ? ' selected="selected"' : '';
				$timeoffset = date_offset_get(date_create('now', timezone_open($fuseau['tz_name'])) );
				$formated_toffset = '(UTC'.(($timeoffset < 0) ? '–' : '+').str2(floor((abs($timeoffset)/3600))) .':'.str2(floor((abs($timeoffset)%3600)/60)) .') ';
			$form .= '>'.$formated_toffset.' '.htmlentities($fuseau['city']).'</option>'."\n";
		}
		$form .= "\t".'</optgroup>'."\n";
	}
	$form .= '</select>'."\n";
	return $form;
}

function form_format_heure($defaut) {
	$formats = array (
		'0' => date('H\:i\:s'),		// 23:56:04
		'1' => date('H\:i'),			// 23:56
		'2' => date('h\:i\:s A'),	// 11:56:04 PM
		'3' => date('h\:i A'),		// 11:56 PM
	);
	$form = '<label>'.$GLOBALS['lang']['pref_format_heure'].'</label>'."\n";
	$form .= '<select name="format_heure">'."\n";
	foreach ($formats as $option => $label) {
		$form .= "\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.htmlentities($label).'</option>'."\n";
	}
	$form .= "\t".'</select>'."\n";
	return $form;
}

function form_langue($defaut) {
	$form = '<label>'.$GLOBALS['lang']['pref_langue'].'</label>'."\n";
	$form .= '<select name="langue">'."\n";
	foreach ($GLOBALS['langs'] as $option => $label) {
		$form .= "\t".'<option value="'.htmlentities($option).'"'.(($defaut == $option) ? ' selected="selected" ' : '').'>'.$label.'</option>'."\n";
	}
	$form .= '</select>'."\n";
	return $form;
}

function form_langue_install($label) {
	$ret = '<label for="langue">'.$label;
	$ret .= '<select id="langue" name="langue">'."\n";
	foreach ($GLOBALS['langs'] as $option => $label) {
		$ret .= "\t".'<option value="'.htmlentities($option).'">'.$label.'</option>'."\n";
	}
	$ret .= '</select></label>'."\n";
	echo $ret;
}



/* form config RSS feeds: allow changing feeds (title, url) or remove a feed */
function afficher_form_rssconf($errors='') {
	if (!empty($errors)) {
		echo erreurs($errors);
	}
	$out = '';
	// form add new feed.
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

	// Form edit + list feeds.
	$out .= '<form id="form-rss-config" method="post" action="feed.php?config">'."\n";
	$out .= '<ul>'."\n";
	foreach($GLOBALS['liste_flux'] as $i => $flux) {
		$out .= "\t".'<li>'."\n";
		$out .= "\t\t".'<span'.( ($flux['iserror'] > 2) ? ' class="feed-error" title="('.$flux['iserror'].' last requests were errors.)" ' : ''  ).'>'."\n";
		$out .= "\t\t\t".'<label for="i_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_titre_flux'].'</label>'."\n";
		$out .= "\t\t\t".'<input id="i_'.$flux['checksum'].'" name="i_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['title']).'">'."\n";
		$out .= "\t\t".'</span>'."\n";
		$out .= "\t\t".'<span>'."\n";
		$out .= "\t\t\t".'<label for="j_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_url_flux'].'</label>'."\n";
		$out .= "\t\t\t".'<input id="j_'.$flux['checksum'].'" name="j_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['link']).'">'."\n";
		$out .= "\t\t".'</span>'."\n";
		$out .= "\t\t".'<span>'."\n";
		$out .= "\t\t\t".'<label for="l_'.$flux['checksum'].'">'.$GLOBALS['lang']['rss_label_dossier'].'</label>'."\n";
		$out .= "\t\t\t".'<input id="l_'.$flux['checksum'].'" name="l_'.$flux['checksum'].'" type="text" class="text" value="'.htmlspecialchars($flux['folder']).'">'."\n";
		$out .= "\t\t\t".'<input class="remove-feed" name="k_'.$flux['checksum'].'" type="hidden" value="1">'."\n";
		$out .= "\t\t".'</span>'."\n";
		$out .= "\t\t".'<span>'."\n";
		$out .= "\t\t\t".'<button type="button" class="submit button-cancel" onclick="unMarkAsRemove(this)">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
		$out .= "\t\t\t".'<button type="button" class="submit button-delete" onclick="markAsRemove(this)">'.$GLOBALS['lang']['supprimer'].'</button>'."\n";
		$out .= "\t\t".'</span>';
		$out .= "\t".'</li>'."\n";
	}
	$out .= '</ul>'."\n";
	$out .= '<p class="submit-bttns">'."\n";
	$out .= "\t".'<button class="submit button-submit" type="submit" name="send">'.$GLOBALS['lang']['envoyer'].'</button>'."\n";
	$out .= '</p>'."\n";
	$out .= hidden_input('token', new_token());
	$out .= hidden_input('verif_envoi', 1);
	$out .= '</form>'."\n";

	return $out;
}


/* FORMULAIRE NORMAL DES PRÉFÉRENCES */
function afficher_form_prefs($erreurs = '') {
	$submit_box = '<div class="submit-bttns">'."\n";
	$submit_box .= hidden_input('_verif_envoi', '1');
	$submit_box .= hidden_input('token', new_token());
	$submit_box .= '<button class="submit button-cancel" type="button" onclick="annuler(\'preferences.php\');" >'.$GLOBALS['lang']['annuler'].'</button>'."\n";
	$submit_box .= '<button class="submit button-submit" type="submit" name="enregistrer">'.$GLOBALS['lang']['enregistrer'].'</button>'."\n";
	$submit_box .= '</div>'."\n";


	echo '<form id="preferences" method="post" action="'.basename($_SERVER['SCRIPT_NAME']).'" >' ;
		echo erreurs($erreurs);
		$fld_user = '<div role="group" class="pref">'; /* no fieldset because browset can’t style them correctly */
		$fld_user .= '<div class="form-legend"><legend class="legend-user">'.$GLOBALS['lang']['prefs_legend_utilisateur'].'</legend></div>'."\n";

		$fld_user .= '<div class="form-lines">'."\n";
		$fld_user .= '<p>'."\n";
		$fld_user .= "\t".'<label for="auteur">'.$GLOBALS['lang']['pref_auteur'].'</label>'."\n";
		$fld_user .= "\t".'<input type="text" id="auteur" name="auteur" size="30" value="'.(empty($GLOBALS['auteur']) ? htmlspecialchars(USER_LOGIN) : $GLOBALS['auteur']).'" class="text" />'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= "\t".'<label for="email">'.$GLOBALS['lang']['pref_email'].'</label>'."\n";
		$fld_user .= "\t".'<input type="text" id="email" name="email" size="30" value="'.$GLOBALS['email'].'" class="text" />'."\n";
		$fld_user .= '</p>'."\n";

		$fld_user .= '<p>'."\n";
		$fld_user .= "\t".'<label for="racine">'.$GLOBALS['lang']['pref_racine'].'</label>'."\n";
		$fld_user .= "\t".'<input type="text" id="racine" name="racine" size="30" value="'.$GLOBALS['racine'].'" class="text" />'."\n";
		$fld_user .= '</p>'."\n";
		$fld_user .= '</div>'."\n";
		$fld_user .= $submit_box;

		$fld_user .= '</div>';
	echo $fld_user;

		$fld_securite = '<div role="group" class="pref">';
		$fld_securite .= '<div class="form-legend"><legend class="legend-securite">'.$GLOBALS['lang']['prefs_legend_securite'].'</legend></div>'."\n";

		$fld_securite .= '<div class="form-lines">'."\n";
		$fld_securite .= '<p>'."\n";
		$fld_securite .= "\t".'<label for="identifiant">'.$GLOBALS['lang']['pref_identifiant'].'</label>'."\n";
		$fld_securite .= "\t".'<input type="text" id="identifiant" name="identifiant" size="30" value="'.htmlspecialchars(USER_LOGIN).'" class="text" />'."\n";
		$fld_securite .= '</p>'."\n";

		$fld_securite .= '<p>'."\n";
		$fld_securite .= "\t".'<label for="mdp">'.$GLOBALS['lang']['pref_mdp'].'</label>';
		$fld_securite .= "\t".'<input type="password" id="mdp" name="mdp" size="30" value="" class="text" autocomplete="off" />'."\n";
		$fld_securite .= '</p>'."\n";

		$fld_securite .= '<p>'."\n";
		$fld_securite .= "\t".'<label for="mdp_rep">'.$GLOBALS['lang']['pref_mdp_nouv'].'</label>';
		$fld_securite .= "\t".'<input type="password" id="mdp_rep" name="mdp_rep" size="30" value="" class="text" autocomplete="off" />'."\n";
		$fld_securite .= '</p>'."\n";
		$fld_securite .= '</div>';

		$fld_securite .= $submit_box;

		$fld_securite .= '</div>';
	echo $fld_securite;

		$fld_dateheure = '<div role="group" class="pref">';
		$fld_dateheure .= '<div class="form-legend"><legend class="legend-dateheure">'.$GLOBALS['lang']['prefs_legend_langdateheure'].'</legend></div>'."\n";

		$fld_dateheure .= '<div class="form-lines">'."\n";
		$fld_dateheure .= '<p>'."\n";
		$fld_dateheure .= form_langue($GLOBALS['lang']['id']);
		$fld_dateheure .= '</p>'."\n";

		$fld_dateheure .= '<p>'."\n";
		$fld_dateheure .= form_format_date($GLOBALS['format_date']);
		$fld_dateheure .= '</p>'."\n";

		$fld_dateheure .= '<p>'."\n";
		$fld_dateheure .= form_format_heure($GLOBALS['format_heure']);
		$fld_dateheure .= '</p>'."\n";

		$fld_dateheure .= '<p>'."\n";
		$fld_dateheure .= form_fuseau_horaire($GLOBALS['fuseau_horaire']);
		$fld_dateheure .= '</p>'."\n";
		$fld_dateheure .= '</div>'."\n";

		$fld_dateheure .= $submit_box;

		$fld_dateheure .= '</div>';
	echo $fld_dateheure;

		/* TODO
		- Open=read ? + button to mark as read in HTML
		- Export OPML
		*/
		$fld_cfg_rss = '<div role="group" class="pref">';
		$fld_cfg_rss .= '<div class="form-legend"><legend class="legend-rss">'.$GLOBALS['lang']['prefs_legend_configrss'].'</legend></div>'."\n";
		$fld_cfg_rss .= '<div class="form-lines">'."\n";

		$fld_cfg_rss .= '<p>'."\n";
		$a = explode('/', dirname($_SERVER['SCRIPT_NAME']));
		$fld_cfg_rss .= '<label>'.$GLOBALS['lang']['pref_label_crontab_rss'].'</label>'."\n";
		$fld_cfg_rss .= '<a onclick="prompt(\''.$GLOBALS['lang']['pref_alert_crontab_rss'].'\', \'0 *  *   *   *   wget --spider -qO- '.$GLOBALS['racine'].$a[count($a)-1].'/_rss.ajax.php?guid='.BLOG_UID.'&refresh_all'.'\');return false;" href="#">Afficher ligne Cron</a>';
		$fld_cfg_rss .= '</p>'."\n";

		$fld_cfg_rss .= '<p>'."\n";
		$fld_cfg_rss .= "\t".'<label>'.$GLOBALS['lang']['pref_rss_go_to_imp-export'].'</label>'."\n";
		$fld_cfg_rss .= "\t".'<a href="maintenance.php">'.$GLOBALS['lang']['label_import-export'].'</a>'."\n";
		$fld_cfg_rss .= '</p>'."\n";

		$fld_cfg_rss .= '<p>'."\n";
		$fld_cfg_rss .= '</p>'."\n";

		$fld_cfg_rss .= '</div>'."\n";

		$fld_cfg_rss .= $submit_box;
		$fld_cfg_rss .= '</div>';
		echo $fld_cfg_rss;


		$fld_maintenance = '<div role="group" class="pref">';
		$fld_maintenance .= '<div class="form-legend"><legend class="legend-sweep">'.$GLOBALS['lang']['titre_maintenance'].'</legend></div>'."\n";

		$fld_maintenance .= '<div class="form-lines">'."\n";
		$fld_maintenance .= '<p>'."\n";
		$fld_maintenance .= form_checkbox('check_update', $GLOBALS['check_update'], $GLOBALS['lang']['pref_check_update'] );
		$fld_maintenance .= '</p>'."\n";

		$fld_maintenance .= '<p>'."\n";
		$fld_maintenance .= "\t".'<label>'.$GLOBALS['lang']['pref_go_to_maintenance'].'</label>'."\n";
		$fld_maintenance .= "\t".'<a href="maintenance.php">Maintenance</a>'."\n";
		$fld_maintenance .= '</p>'."\n";
		$fld_maintenance .= '</div>'."\n";

		$fld_maintenance .= $submit_box;

		$fld_maintenance .= '</div>';
	echo $fld_maintenance;

	// check if a new Blogotext version is available (code from Shaarli, by Sebsauvage).
	// Get latest version number at most once a day.
	if ($GLOBALS['check_update'] == 1) {
		$version_file = 'config/version.txt';
		if ( !is_file($version_file) or (filemtime($version_file) < time()-(24*60*60)) ) {
			$version_hit_url = 'http://lehollandaisvolant.net/blogotext/version.php'; // NOT VALID ANYMORE: FIXME
			$response = request_external_files(array($version_hit_url), 6, false);
			$last_version = $response[$version_hit_url]['body'];
			// If failed, nevermind. We don't want to bother the user with that.
			if (empty($last_version)) {
				file_put_contents($version_file, BLOGOTEXT_VERSION); // touch
			} else {
				file_put_contents($version_file, $last_version); // rewrite file
			}
		}

		// Compare versions:
		$newestversion = file_get_contents($version_file);
		if (version_compare($newestversion, BLOGOTEXT_VERSION) == 1) {
				$fld_update = '<div role="group" class="pref">';
				$fld_update .= '<div class="form-legend"><legend class="legend-update">'.$GLOBALS['lang']['maint_chk_update'].'</legend></div>'."\n";
				$fld_update .= '<div class="form-lines">'."\n";
				$fld_update .= '<p>'."\n";
				$fld_update .= "\t".'<label>'.$GLOBALS['lang']['maint_update_youisbad'].' ('.$newestversion.'). '.$GLOBALS['lang']['maint_update_go_dl_it'].'</label>'."\n";
				$fld_update .= "\t".'<a href="http://lehollandaisvolant.net/blogotext/">lehollandaisvolant.net/blogotext</a>.';
				$fld_update .= '</p>'."\n";
				$fld_update .= '</div>'."\n";
				$fld_update .= '</div>'."\n";
			echo $fld_update;
		}
	}

	echo '</form>'."\n";
}
