<?php
# *** LICENSE ***
# This file is part of BlogoText.
# http://lehollandaisvolant.net/blogotext/
#
# 2006      Frederic Nassar.
# 2010-2016 Timo Van Neerden.
#
# BlogoText is free software.
# You can redistribute it under the terms of the MIT / X11 Licence.
#
# *** LICENSE ***

require_once 'inc/inc.php';
operate_session();

$GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);
$GLOBALS['db_handle'] = open_base();

afficher_html_head($GLOBALS['lang']['titre_maintenance']);
echo '<div id="header">'."\n";
	echo '<div id="top">'."\n";
	afficher_msg();
	afficher_topnav('preferences.php', $GLOBALS['lang']['titre_maintenance']);
	echo '</div>'."\n";
echo '</div>'."\n";

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

// création du dossier des backups
creer_dossier(DIR_BACKUP, 0);

/* AJOUTE TOUS LES DOSSIERS DU TABLEAU $dossiers DANS UNE ARCHIVE ZIP */
function addFolder2zip($zip, $folder) {
	if ($handle = opendir($folder)) {
		while (FALSE !== ($entry = readdir($handle))) {
			if ($entry != "." and $entry != ".." and is_readable($folder.'/'.$entry)) {
				if (is_dir($folder.'/'.$entry)) addFolder2zip($zip, $folder.'/'.$entry);
				else $zip->addFile($folder.'/'.$entry, preg_replace('#^\.\./#', '', $folder.'/'.$entry));
		}	}
		closedir($handle);
	}
}

function creer_fichier_zip($dossiers) {
	$zipfile = DIR_BACKUP.'archive_site-'.date('Ymd').'-'.substr(md5(rand(10,99)),3,5).'.zip'; // TODO : path is absolute → make is relative
	$zip = new ZipArchive;
	if ($zip->open($zipfile, ZipArchive::CREATE) === TRUE) {
		foreach ($dossiers as $dossier) {
			addFolder2zip($zip, $dossier);
		}
		$zip->close();
		if (is_file($zipfile)) return $zipfile;
	}
	else return FALSE;
}

/* Crée la liste des RSS et met tout ça dans un fichier OPML */
function creer_fichier_opml() {
	$path = DIR_BACKUP.'backup-data-'.date('Ymd-His').'.opml';
	// sort feeds by folder
	$folders = array();
	foreach ($GLOBALS['liste_flux'] as $i => $feed) {
		$folders[$feed['folder']][] = $feed;
	}
	ksort($folders);

	$html  = '<?xml version="1.0" encoding="utf-8"?>'."\n";
	$html .= '<opml version="1.0">'."\n";
	$html .= "\t".'<head>'."\n";
	$html .= "\t\t".'<title>Newsfeeds '.BLOGOTEXT_NAME.' '.BLOGOTEXT_VERSION.' on '.date('Y/m/d').'</title>'."\n";
	$html .= "\t".'</head>'."\n";
	$html .= "\t".'<body>'."\n";

	function esc($a) {
		return htmlspecialchars($a, ENT_QUOTES, 'UTF-8');
	}

	foreach ($folders as $i => $folder) {
		$outline = '';
		foreach ($folder as $j => $feed) {
			$outline .= ($i ? "\t" : '')."\t\t".'<outline text="'.esc($feed['title']).'" title="'.esc($feed['title']).'" type="rss" xmlUrl="'.esc($feed['link']).'" />'."\n";
		}
		if ($i != '') {
			$html .= "\t\t".'<outline text="'.esc($i).'" title="'.esc($i).'" >'."\n";
			$html .= $outline;
			$html .= "\t\t".'</outline>'."\n";	
		} else {
			$html .= $outline;
		}
	}

	$html .= "\t".'</body>'."\n".'</opml>';

	return (file_put_contents($path, $html) === FALSE) ? FALSE : $path;
}

// Parse et importe un fichier de liste de flux OPML
function importer_opml($opml_content) {
	$GLOBALS['array_new'] = array();

	function parseOpmlRecursive($xmlObj) {
		// si c’est un sous dossier avec d’autres flux à l’intérieur : note le nom du dossier
		$folder = $xmlObj->attributes()->text;
		foreach($xmlObj->children() as $child) {
			if (!empty($child['xmlUrl'])) {
				$url = (string)$child['xmlUrl'];
				$title = ( !empty($child['text']) ) ? (string)$child['text'] : (string)$child['title'];
				$GLOBALS['array_new'][$url] = array(
					'link' => $url,
					'title' => ucfirst($title),
					'favicon' => 'style/rss-feed-icon.png',
					'checksum' => '0',
					'time' => '0',
					'folder' => (string)$folder,
					'iserror' => 0,
				);
			}
	 		parseOpmlRecursive($child);
		}
	}
	$opmlFile = new SimpleXMLElement($opml_content);
	parseOpmlRecursive($opmlFile->body);

	$old_len = count($GLOBALS['liste_flux']);
	$GLOBALS['liste_flux'] = array_reverse(tri_selon_sous_cle($GLOBALS['liste_flux'], 'title'));
	$GLOBALS['liste_flux'] = array_merge($GLOBALS['array_new'], $GLOBALS['liste_flux']);
	file_put_contents(FEEDS_DB, '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_flux']))).' */');

	return (count($GLOBALS['liste_flux']) - $old_len);
}


/*
 * Affiches les formulaires qui demandent quoi faire. (!isset($do))
 * Font le traitement dans les autres cas.
*/

// no $do nor $file : ask what to do
echo '<div id="maintenance-form">'."\n";
if (!isset($_GET['do']) and !isset($_FILES['file'])) {
	$token = new_token();
	$nbs = array('10'=>'10', '20'=>'20', '50'=>'50', '100'=>'100', '200'=>'200', '500'=>'500', '-1' => $GLOBALS['lang']['pref_all']);

	echo '<form action="maintenance.php" method="get" class="bordered-formbloc" id="form_todo">'."\n";
	echo '<label for="select_todo">'.$GLOBALS['lang']['maintenance_ask_do_what'].'</label>'."\n";
	echo '<select id="select_todo" name="select_todo" onchange="switch_form(this.value)">'."\n";
	echo "\t".'<option selected disabled hidden value=""></option>'."\n";
	echo "\t".'<option value="form_export">'.$GLOBALS['lang']['maintenance_export'].'</option>'."\n";
	echo "\t".'<option value="form_import">'.$GLOBALS['lang']['maintenance_import'].'</option>'."\n";
	echo "\t".'<option value="form_optimi">'.$GLOBALS['lang']['maintenance_optim'].'</option>'."\n";
	echo '</select>'."\n";
	echo '</form>'."\n";

	// Form export
	echo '<form action="maintenance.php" onsubmit="hide_forms(\'exp-format\')" method="get" class="bordered-formbloc" id="form_export">'."\n";
	// choose export what ?
		echo '<fieldset>'."\n";
		echo '<legend class="legend-backup">'.$GLOBALS['lang']['maintenance_export'].'</legend>';
		echo "\t".'<p><label for="zip">'.$GLOBALS['lang']['bak_export_zip'].'</label>'.
			'<input type="radio" name="exp-format" value="zip"  id="zip"  onchange="switch_export_type(\'e_zip\')"  /></p>'."\n";
		echo "\t".'<p><label for="opml">'.$GLOBALS['lang']['bak_export_opml'].'</label>'.
			'<input type="radio" name="exp-format" value="opml"  id="opml"  onchange="switch_export_type(\'e_opml\')"  /></p>'."\n";
		echo '</fieldset>'."\n";
		// export data in zip
		echo '<fieldset id="e_zip">';
		echo '<legend class="legend-backup">'.$GLOBALS['lang']['maintenance_incl_quoi'].'</legend>';
		if (DBMS == 'sqlite')
		echo "\t".'<p>'.select_yes_no('incl-sqlit', 0, $GLOBALS['lang']['bak_incl_sqlit']).'</p>'."\n";
		echo "\t".'<p>'.select_yes_no('incl-confi', 0, $GLOBALS['lang']['bak_incl_confi']).'</p>'."\n";
		echo '</fieldset>'."\n";
		echo '<p class="submit-bttns">'."\n";
		echo "\t".'<button class="submit button-cancel" type="button" onclick="annuler(\'maintenance.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
		echo "\t".'<button class="submit button-submit" type="submit" name="do" value="export">'.$GLOBALS['lang']['valider'].'</button>'."\n";
		echo '</p>'."\n";
		echo hidden_input('token', $token);
	echo '</form>'."\n";

	// Form import
	$importformats = array(
			'rssopml' => $GLOBALS['lang']['bak_import_rssopml'] );
	echo '<form action="maintenance.php" method="post" enctype="multipart/form-data" class="bordered-formbloc" id="form_import">'."\n";
		echo '<fieldset class="pref valid-center">';
		echo '<legend class="legend-backup">'.$GLOBALS['lang']['maintenance_import'].'</legend>';
		echo "\t".'<p>'.form_select_no_label('imp-format', $importformats, 'jsonbak');
		echo '<input type="file" name="file" id="file" class="text" /></p>'."\n";
		echo '</fieldset>'."\n";
		echo '<p class="submit-bttns">'."\n";
		echo "\t".'<button class="submit button-cancel" type="button" onclick="annuler(\'maintenance.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
		echo "\t".'<button class="submit button-submit" type="submit" name="valider">'.$GLOBALS['lang']['valider'].'</button>'."\n";
		echo '</p>'."\n";

		echo hidden_input('token', $token);
	echo '</form>'."\n";

	// Form optimi
	echo '<form action="maintenance.php" method="get" class="bordered-formbloc" id="form_optimi">'."\n";
		echo '<fieldset class="pref valid-center">';
		echo '<legend class="legend-sweep">'.$GLOBALS['lang']['maintenance_optim'].'</legend>';
		if (DBMS == 'sqlite') {
			echo "\t".'<p>'.select_yes_no('opti-vacu', 0, $GLOBALS['lang']['bak_opti_vacuum']).'</p>'."\n";
		} else {
			echo hidden_input('opti-vacu', 0);
		}
		echo "\t".'<p>'.select_yes_no('opti-rss', 0, $GLOBALS['lang']['bak_opti_supprreadrss']).'</p>'."\n";

		echo '</fieldset>'."\n";
		echo '<p class="submit-bttns">'."\n";
		echo "\t".'<button class="submit button-cancel" type="button" onclick="annuler(\'maintenance.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
		echo "\t".'<button class="submit button-submit" type="submit" name="do" value="optim">'.$GLOBALS['lang']['valider'].'</button>'."\n";
		echo '</p>'."\n";
		echo hidden_input('token', $token);
	echo '</form>'."\n";

// either $do or $file
// $do
} else {
	// vérifie Token
	if ($erreurs_form = valider_form_maintenance()) {
		echo '<div class="bordered-formbloc">'."\n";
		echo '<fieldset class="pref valid-center">'."\n";
		echo '<legend class="legend-backup">'.$GLOBALS['lang']['bak_restor_done'].'</legend>';
		echo erreurs($erreurs_form);
		echo '<p class="submit-bttns"><button class="submit button-submit" type="button" onclick="annuler(\'maintenance.php\')">'.$GLOBALS['lang']['valider'].'</button></p>'."\n";
		echo '</fieldset>'."\n";
		echo '</div>'."\n";

	} else {
		// token : ok, go on !
		if (isset($_GET['do'])) {
			if ($_GET['do'] == 'export') {
				// Export a ZIP archive
				if (@$_GET['exp-format'] == 'zip') {
					$dossiers = array();
					if (@$_GET['incl-sqlit'] == 1) {
						$dossiers[] = DIR_DATABASES;
					}
					if ($_GET['incl-confi'] == 1) {
						$dossiers[] = DIR_CONFIG;
					}
					$file_archive = creer_fichier_zip($dossiers);

				// Export a OPML rss lsit
				} elseif (@$_GET['exp-format'] == 'opml') {
					$file_archive = creer_fichier_opml();
				} else {
					echo 'nothing to do';
				}

				// affiche le formulaire de téléchargement et de validation.
				if (!empty($file_archive)) {
					echo '<form action="maintenance.php" method="get" class="bordered-formbloc">'."\n";
					echo '<fieldset class="pref valid-center">';
					echo '<legend class="legend-backup">'.$GLOBALS['lang']['bak_succes_save'].'</legend>';

					echo '<p><a href="'.$file_archive.'" download>'.$GLOBALS['lang']['bak_dl_fichier'].'</a></p>'."\n";
					echo '<p class="submit-bttns"><button class="submit button-submit" type="submit">'.$GLOBALS['lang']['valider'].'</button></p>'."\n";
					echo '</fieldset>'."\n";
					echo '</form>'."\n";
				}

			} elseif ($_GET['do'] == 'optim') {
					// vacuum SQLite DB
					if ($_GET['opti-vacu'] == 1) {
						try {
							$req = $GLOBALS['db_handle']->prepare('VACUUM');
							$req->execute();
						} catch (Exception $e) {
							die('Erreur 1429 vacuum : '.$e->getMessage());
						}
					}
					// delete old RSS entries
					if ($_GET['opti-rss'] == 1) {
						try {
							$req = $GLOBALS['db_handle']->prepare('DELETE FROM rss WHERE bt_statut=0');
							$req->execute(array());
						} catch (Exception $e) {
							die('Erreur : 7873 : rss delete old entries : '.$e->getMessage());
						}
					}
					echo '<form action="maintenance.php" method="get" class="bordered-formbloc">'."\n";
					echo '<fieldset class="pref valid-center">';
					echo '<legend class="legend-backup">'.$GLOBALS['lang']['bak_optim_done'].'</legend>';
					echo '<p class="submit-bttns"><button class="submit button-submit" type="submit">'.$GLOBALS['lang']['valider'].'</button></p>'."\n";
					echo '</fieldset>'."\n";
					echo '</form>'."\n";

			} else {
				echo 'nothing to do.';
			}

		// $file
		} elseif (isset($_POST['valider']) and !empty($_FILES['file']['tmp_name']) ) {
				$message = array();
				$xml = file_get_contents($_FILES['file']['tmp_name']);
				$message['feeds'] = importer_opml($xml);

				if (!empty($message)) {
					echo '<form action="maintenance.php" method="get" class="bordered-formbloc">'."\n";
					echo '<fieldset class="pref valid-center">';
					echo '<legend class="legend-backup">'.$GLOBALS['lang']['bak_restor_done'].'</legend>';
					echo '<ul>';
					foreach ($message as $type => $nb) echo '<li>'.$GLOBALS['lang']['label_'.$type].' : '.$nb.'</li>'."\n";
					echo '</ul>';
					echo '<p class="submit-bttns"><button class="submit button-submit" type="submit">'.$GLOBALS['lang']['valider'].'</button></p>'."\n";
					echo '</fieldset>'."\n";
					echo '</form>'."\n";
				}

		} else {
			echo 'nothing to do.';
		}
	}
}

echo '</div>'."\n";


echo "\n".'<script src="style/javascript.js" type="text/javascript"></script>'."\n";
echo '<script type="text/javascript">';
echo 'var ia = document.getElementById(\'incl-artic\');';
echo "ia.addEventListener('change', function() { document.getElementById('nb-artic').style.display = ( ia.value == 1 ? 'inline-block' : 'none'); });";

echo 'var il = document.getElementById(\'incl-links\');';
echo "il.addEventListener('change', function() { document.getElementById('nb-links').style.display = ( il.value == 1 ? 'inline-block' : 'none'); });";

echo '</script>';

footer($begin);
