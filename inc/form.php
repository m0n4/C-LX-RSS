<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

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

