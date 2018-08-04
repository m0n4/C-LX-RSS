<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

set_time_limit (180);
require_once 'inc/boot.php';
operate_session();
$GLOBALS['liste_flux'] = open_serialzd_file(FEEDS_DB);


/*
 * reconstruit la BDD des fichiers (en synchronisant la liste des fichiers sur le disque avec les fichiers dans BDD)
*/
function rebuilt_file_db() {

	/*
	*
	* BEGIN WITH LISTING FILES ACTUALLY ON DISC
	*/

	$img_on_disk = rm_dots_dir(scandir(DIR_IMAGES));
	// scans also subdir of img/* (in one single array of paths)
	foreach ($img_on_disk as $i => $e) {
		$subelem = DIR_IMAGES.$e;
		if (is_dir($subelem)) {
			unset($img_on_disk[$i]); // rm folder entry itself
			$subidir = rm_dots_dir(scandir($subelem));
			foreach ($subidir as $j => $im) {
				$img_on_disk[] = $e.'/'.$im;
			}
		}
	}
	foreach ($img_on_disk as $i => $e) {
		$img_on_disk[$i] = '/'.$e;
	}

	$docs_on_disc = rm_dots_dir(scandir(DIR_DOCUMENTS));

	// don’t cound thumbnails
	$img_on_disk = array_filter($img_on_disk, function($file){return (!((preg_match('#-thb\.jpg$#', $file)) or (strpos($file, 'index.html') == 4))); });

	/*
	*
	* THEN REMOVES FROM DATABASE THE IMAGES THAT ARE MISSING ON DISK
	*/

	$query = "SELECT bt_filename, bt_path, ID FROM images";
	$img_in_db = liste_elements($query, array(), 'images');

	$img_in_db_path = array(); foreach ($img_in_db as $i => $img) { $img_in_db_path[] = '/'.$img['bt_path'].'/'.$img['bt_filename']; }

	$img_to_rm_from_db = array();

	foreach ($img_in_db as $i => $img) {
		if (!in_array('/'.$img['bt_path'].'/'.$img['bt_filename'], $img_on_disk)) {
			$img_to_rm_from_db[] = $img['ID'];
		}
	}

	if (!empty($img_to_rm_from_db)) {
		try {
			$GLOBALS['db_handle']->beginTransaction();
			foreach($img_to_rm_from_db as $img) {
				$query = 'DELETE FROM images WHERE ID = ? ';
				$req = $GLOBALS['db_handle']->prepare($query);
				$req->execute(array($img));
			}
			$GLOBALS['db_handle']->commit();
		} catch (Exception $e) {
			$req->rollBack();
			die('Erreur 5798 on delete unexistant images : '.$e->getMessage());
		}
	}

	/*
	*
	* ADD THE IMAGSE THAT ARE ON DISK BUT NOT IN DATABASE, TO DATABASE
	*/

	try {
		$GLOBALS['db_handle']->beginTransaction();

		foreach ($img_on_disk as $file) {
			if (!in_array($file, $img_in_db_path)) {
				$filepath = DIR_IMAGES.$file;
				$time = filemtime($filepath);
				$id = date('YmdHis', $time);
				$checksum = sha1_file($filepath);
				$filesize = filesize($filepath);
				list($img_w, $img_h) = getimagesize($filepath);

				$req = $GLOBALS['db_handle']->prepare('INSERT INTO images
					(	bt_id,
						bt_type,
						bt_fileext,
						bt_filename,
						bt_filesize,
						bt_content,
						bt_folder,
						bt_checksum,
						bt_statut,
						bt_path,
						bt_dim_w,
						bt_dim_h
					)
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
				$req->execute(array(
					$id,
					'image',
					pathinfo($filepath, PATHINFO_EXTENSION),
					$file,
					$filesize,
					'',
					'',
					$checksum,
					0,
					substr($checksum, 0, 2),
					$img_w,
					$img_h
				));
				
				// crée une miniature de l’image
				create_thumbnail($filepath);
			}
		}

	} catch (Exception $e) {
		$req->rollBack();
		return 'Erreur 39787 ajout images du disque dans DB: '.$e->getMessage();
	}
}


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
	foreach($dossiers as $i => $dossier) {
		$dossiers[$i] = str_replace(BT_ROOT, '', $dossier); // FIXME : find cleaner way for '../';
	}
	$file = 'archive_site-'.date('Ymd').'-'.substr(md5(rand(10,99)),3,5).'.zip';
	$filepath = str_replace(BT_ROOT, '', DIR_BACKUP).$file;

	$zip = new ZipArchive;
	if ($zip->open(DIR_BACKUP.$file, ZipArchive::CREATE) === TRUE) {
		foreach ($dossiers as $dossier) {
			addFolder2zip($zip, $dossier);
		}
		$zip->close();
		if (is_file(DIR_BACKUP.$file)) return $filepath;
	}
	else return FALSE;
}

/* Crée la liste des RSS et met tout ça dans un fichier OPML */
function creer_fichier_opml() {
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

	// écriture du fichier
	$file = 'backup-data-'.date('Ymd-His').'.opml';
	$filepath = str_replace(BT_ROOT, '', DIR_BACKUP).$file;
	return (file_put_contents(DIR_BACKUP.$file, $html) === FALSE) ? FALSE : $filepath;

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

// DEBUT PAGE
afficher_html_head($GLOBALS['lang']['titre_maintenance'], "maintenance");
afficher_topnav($GLOBALS['lang']['titre_maintenance'], ''); #top

echo '<div id="axe">'."\n";
echo '<div id="page">'."\n";

// création du dossier des backups
creer_dossier(DIR_BACKUP, 0);


/*
 * Affiches les formulaires qui demandent quoi faire.
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
		echo "\t".'<p><label for="opml">'.$GLOBALS['lang']['bak_export_opml'].'</label>'.
			'<input type="radio" name="exp-format" value="opml"  id="opml"  onchange="switch_export_type(\'e_opml\')"  /></p>'."\n";
		echo "\t".'<p><label for="zip">'.$GLOBALS['lang']['bak_export_zip'].'</label>'.
			'<input type="radio" name="exp-format" value="zip"  id="zip"  onchange="switch_export_type(\'e_zip\')"  /></p>'."\n";
		echo '</fieldset>'."\n";

		// export data in zip
		echo '<fieldset id="e_zip">';
		echo '<legend class="legend-backup">'.$GLOBALS['lang']['maintenance_incl_quoi'].'</legend>';
		if (DBMS == 'sqlite')
		echo "\t".'<p>'.form_checkbox('incl-sqlit', 0, $GLOBALS['lang']['bak_incl_sqlit']).'</p>'."\n";
		echo "\t".'<p>'.form_checkbox('incl-confi', 0, $GLOBALS['lang']['bak_incl_confi']).'</p>'."\n";
		echo '</fieldset>'."\n";
		echo '<p class="submit-bttns">'."\n";
		echo "\t".'<button class="submit button-cancel" type="button" onclick="goToUrl(\'maintenance.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
		echo "\t".'<button class="submit button-submit" type="submit" name="do" value="export">'.$GLOBALS['lang']['valider'].'</button>'."\n";
		echo '</p>'."\n";
		echo hidden_input('token', $token);
	echo '</form>'."\n";

	// Form import
	$importformats = array(
		'rssopml' => $GLOBALS['lang']['bak_import_rssopml']
	);
	echo '<form action="maintenance.php" method="post" enctype="multipart/form-data" class="bordered-formbloc" id="form_import">'."\n";
		echo '<fieldset class="pref valid-center">';
		echo '<legend class="legend-backup">'.$GLOBALS['lang']['maintenance_import'].'</legend>';
		echo "\t".'<p>'.form_select_no_label('imp-format', $importformats, 'jsonbak');
		echo '<input type="file" name="file" id="file" class="text" /></p>'."\n";
		echo '</fieldset>'."\n";
		echo '<p class="submit-bttns">'."\n";
		echo "\t".'<button class="submit button-cancel" type="button" onclick="goToUrl(\'maintenance.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
		echo "\t".'<button class="submit button-submit" type="submit" name="valider">'.$GLOBALS['lang']['valider'].'</button>'."\n";
		echo '</p>'."\n";

		echo hidden_input('token', $token);
	echo '</form>'."\n";

	// Form optimi
	echo '<form action="maintenance.php" method="get" class="bordered-formbloc" id="form_optimi">'."\n";
		echo '<fieldset class="pref valid-center">';
		echo '<legend class="legend-sweep">'.$GLOBALS['lang']['maintenance_optim'].'</legend>';

		echo "\t".'<p>'.select_yes_no('opti-file', 0, $GLOBALS['lang']['bak_opti_miniature']).'</p>'."\n";
		if (DBMS == 'sqlite') {
			echo "\t".'<p>'.select_yes_no('opti-vacu', 0, $GLOBALS['lang']['bak_opti_vacuum']).'</p>'."\n";
		} else {
			echo hidden_input('opti-vacu', 0);
		}
		echo "\t".'<p>'.select_yes_no('opti-comm', 0, $GLOBALS['lang']['bak_opti_recountcomm']).'</p>'."\n";
		echo "\t".'<p>'.select_yes_no('opti-rss', 0, $GLOBALS['lang']['bak_opti_supprreadrss']).'</p>'."\n";
		echo '</fieldset>'."\n";
		echo '<p class="submit-bttns">'."\n";
		echo "\t".'<button class="submit button-cancel" type="button" onclick="goToUrl(\'maintenance.php\');">'.$GLOBALS['lang']['annuler'].'</button>'."\n";
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
		echo '<p class="submit-bttns"><button class="submit button-submit" type="button" onclick="goToUrl(\'maintenance.php\')">'.$GLOBALS['lang']['valider'].'</button></p>'."\n";
		echo '</fieldset>'."\n";
		echo '</div>'."\n";

	} else {
		// token : ok, go on !
		if (isset($_GET['do'])) {
			if ($_GET['do'] == 'export') {
				// Export a ZIP archive
				if (@$_GET['exp-format'] == 'zip') {
					$dossiers = array();
					if (isset($_GET['incl-sqlit'])) {
						$dossiers[] = DIR_DATABASES;
					}
					if (isset($_GET['incl-confi'])) {
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
							$req = $GLOBALS['db_handle']->prepare('DELETE FROM rss WHERE bt_statut=0 AND WHERE bt_bookmarked=0');
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
				switch($_POST['imp-format']) {
					case 'rssopml':
						$xml = file_get_contents($_FILES['file']['tmp_name']);
						$message['feeds'] = importer_opml($xml);
					break;
					default: die('nothing'); break;
				}
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

footer($begin);

