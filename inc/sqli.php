<?php
// *** LICENSE ***
// oText is free software.
//
// By Fred Nassar (2006) and Timo Van Neerden (since 2010)
// See "LICENSE" file for info.
// *** LICENSE ***

/*  Creates a new BlogoText base.
    if file does not exists, it is created, as well as the tables.
    if file does exists, tables are checked and created if not exists
*/
function create_tables() {
	if (file_exists(DIR_CONFIG.'mysql.php')) {
		include(DIR_CONFIG.'mysql.php');
	}
	$auto_increment = (DBMS == 'mysql') ? 'AUTO_INCREMENT' : ''; // SQLite doesn't need this, but MySQL does.
	$index_limit_size = (DBMS == 'mysql') ? '(15)' : ''; // MySQL needs a limit for indexes on TEXT fields.
	$if_not_exists = (DBMS == 'sqlite') ? 'IF NOT EXISTS' : ''; // MySQL doesn’t know this statement for INDEXES

	/* here bt_ID is a GUID, from the feed, not only a 'YmdHis' date string.*/
	$dbase_structure['rss'] = "CREATE TABLE IF NOT EXISTS rss
		(
			ID INTEGER PRIMARY KEY $auto_increment,
			bt_id TEXT,
			bt_date BIGINT,
			bt_title TEXT,
			bt_link TEXT,
			bt_feed TEXT,
			bt_content TEXT,
			bt_statut TINYINT,
			bt_bookmarked TINYINT,
			bt_folder TEXT
		); CREATE INDEX $if_not_exists dateidR ON rss ( bt_date, bt_id$index_limit_size );";

	/*
	* SQLite
	*
	*/
	switch (DBMS) {
		case 'sqlite':
				if (!is_file(SQL_DB)) {
					if (!creer_dossier(DIR_DATABASES)) {
						die('Impossible de creer le dossier databases (chmod?)');
					}
				}
				$file = SQL_DB;
				// open tables
				try {
					$db_handle = new PDO('sqlite:'.$file);
					$db_handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
					$db_handle->query("PRAGMA temp_store=MEMORY; PRAGMA synchronous=OFF; PRAGMA journal_mode=WAL;");


					$wanted_tables = array_keys($dbase_structure);
					foreach ($wanted_tables as $table_name) {
							$results = $db_handle->exec($dbase_structure[$table_name]);
					}
				} catch (Exception $e) {
					die('Erreur 1: '.$e->getMessage());
				}
			break;

		/*
		* MySQL
		*
		*/
		case 'mysql':
				try {

					$options_pdo[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
					$db_handle = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DB.";charset=utf8;sql_mode=PIPES_AS_CONCAT;", MYSQL_LOGIN, MYSQL_PASS, $options_pdo);
					// check each wanted table
					$wanted_tables = array_keys($dbase_structure);
					foreach ($wanted_tables as $table_name) {
							$results = $db_handle->query($dbase_structure[$table_name]."DEFAULT CHARSET=utf8");
							$results->closeCursor();
					}
				} catch (Exception $e) {
					die('Erreur 2: '.$e->getMessage());
				}
			break;
	}

	return $db_handle;
}


/* Open a base */
function open_base() {
	$handle = create_tables();
	$handle->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
	return $handle;
}


/* lists articles with search criterias given in $array. Returns an array containing the data*/
function liste_elements($query, $array, $data_type) {
	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute($array);
		$return = array();

		switch ($data_type) {
			case 'rss':
				while ($row = $req->fetch(PDO::FETCH_ASSOC)) {
					$return[] = $row;
				}
				break;
			default:
				break;
		}

		return $return;
	} catch (Exception $e) {
		die($query);
		die('Erreur 89208 : '.$e->getMessage());
	}
}

/* same as above, but return the amount of entries */
function liste_elements_count($query, $array) {
	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute($array);
		$result = $req->fetch();
		return $result['nbr'];
	} catch (Exception $e) {
		die('Erreur 0003: '.$e->getMessage());
	}
}

// returns or prints an entry of some element of some table (very basic)
function get_entry($table, $entry, $id, $retour_mode) {
	$query = "SELECT $entry FROM $table WHERE bt_id=?";
	try {
		$req = $GLOBALS['db_handle']->prepare($query);
		$req->execute(array($id));
		$result = $req->fetch();
		//echo '<pre>';print_r($result);
	} catch (Exception $e) {
		die('Erreur : '.$e->getMessage());
	}

	if ($retour_mode == 'return' and !empty($result[$entry])) {
		return $result[$entry];
	}
	if ($retour_mode == 'echo' and !empty($result[$entry])) {
		echo $result[$entry];
	}
	return '';
}



/* FOR COMMENTS : RETUNS nb_com per author */
function nb_entries_as($table, $what) {
	$result = array();
	$query = "SELECT count($what) AS nb, $what FROM $table GROUP BY $what ORDER BY nb DESC";
	try {
		$result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	} catch (Exception $e) {
		die('Erreur 0349 : '.$e->getMessage());
	}
}


/* Enregistre le flux dans une BDD.
   $flux est un Array avec les données dedans.
	$flux ne contient que les entrées qui doivent être enregistrées
	 (la recherche de doublons est fait en amont)
*/
function bdd_rss($flux, $what) {
	if ($what == 'enregistrer-nouveau') {
		try {
			$GLOBALS['db_handle']->beginTransaction();
			foreach ($flux as $post) {
				$req = $GLOBALS['db_handle']->prepare('INSERT INTO rss
				(  bt_id,
					bt_date,
					bt_title,
					bt_link,
					bt_feed,
					bt_content,
					bt_statut,
					bt_bookmarked,
					bt_folder
				)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
				$req->execute(array(
					$post['bt_id'],
					$post['bt_date'],
					$post['bt_title'],
					$post['bt_link'],
					$post['bt_feed'],
					$post['bt_content'],
					$post['bt_statut'],
					$post['bt_bookmarked'],
					$post['bt_folder']
				));
			}
			$GLOBALS['db_handle']->commit();
			return TRUE;
		} catch (Exception $e) {
			return 'Erreur 5867-rss-add-sql : '.$e->getMessage();
		}
	}
}

/* FOR RSS : RETUNS list of GUID in whole DB */
function rss_list_guid() {
	$result = array();
	$query = "SELECT bt_id FROM rss";
	try {
		$result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_COLUMN, 0);
		return $result;
	} catch (Exception $e) {
		die('Erreur 0329-rss-get_guid : '.$e->getMessage());
	}
}

/* FOR RSS : RETUNS nb of articles per feed */
function rss_count_feed() {
	$result = array();
	$query = "SELECT bt_feed, SUM(bt_statut) AS nbrun, SUM(bt_bookmarked) AS nbfav, SUM(CASE WHEN bt_date >= 20180527000000 AND bt_statut = 1 THEN 1 ELSE 0 END) AS nbtoday FROM rss GROUP BY bt_feed";

	//$query = "SELECT bt_feed, SUM(bt_statut) AS nbrun, SUM(bt_bookmarked) AS nbfav FROM rss GROUP BY bt_feed";
	try {
		$result = $GLOBALS['db_handle']->query($query)->fetchAll(PDO::FETCH_ASSOC);
		return $result;
	} catch (Exception $e) {
		die('Erreur 0329-rss-count_per_feed : '.$e->getMessage());
	}
}

/* FOR RSS : get $_POST and update feeds (title, url…) for feeds.php?config */
function traiter_form_rssconf() {
	$msg_param_to_trim = (isset($_GET['msg'])) ? '&msg='.$_GET['msg'] : '';
	$query_string = str_replace($msg_param_to_trim, '', $_SERVER['QUERY_STRING']);
	// traitement
	$GLOBALS['db_handle']->beginTransaction();
	foreach($GLOBALS['liste_flux'] as $i => $feed) {
		if (isset($_POST['i_'.$feed['checksum']])) {
			// feed marked to be removed
			if ($_POST['k_'.$feed['checksum']] == 0) {
				unset($GLOBALS['liste_flux'][$i]);
				try {
					$req = $GLOBALS['db_handle']->prepare('DELETE FROM rss WHERE bt_feed=?');
					$req->execute(array($feed['link']));
				} catch (Exception $e) {
					die('Error : Rss?conf RM-from db: '.$e->getMessage());
				}
			}
			// title, url or folders have changed
			else {
				// title has change
				$GLOBALS['liste_flux'][$i]['title'] = $_POST['i_'.$feed['checksum']];
				// folder has changed : update & change folder where it must be changed
				if ($GLOBALS['liste_flux'][$i]['folder'] != $_POST['l_'.$feed['checksum']]) {
					$GLOBALS['liste_flux'][$i]['folder'] = $_POST['l_'.$feed['checksum']];
					try {
						$req = $GLOBALS['db_handle']->prepare('UPDATE rss SET bt_folder=? WHERE bt_feed=?');
						$req->execute(array($_POST['l_'.$feed['checksum']], $feed['link']));
					} catch (Exception $e) {
						die('Error : Rss?conf Update-feed db: '.$e->getMessage());
					}
				}

				// URL has change
				if ($_POST['j_'.$feed['checksum']] != $GLOBALS['liste_flux'][$i]['link']) {
					$a = $GLOBALS['liste_flux'][$i];
					$a['link'] = $_POST['j_'.$feed['checksum']];
					unset($GLOBALS['liste_flux'][$i]);
					$GLOBALS['liste_flux'][$a['link']] = $a;
					try {
						$req = $GLOBALS['db_handle']->prepare('UPDATE rss SET bt_feed=? WHERE bt_feed=?');
						$req->execute(array($_POST['j_'.$feed['checksum']], $feed['link']));
					} catch (Exception $e) {
						die('Error : Rss?conf Update-feed db: '.$e->getMessage());
					}
				}
			}
		}
	}
	$GLOBALS['db_handle']->commit();

	// sort list with title
	$GLOBALS['liste_flux'] = array_reverse(tri_selon_sous_cle($GLOBALS['liste_flux'], 'title'));
	file_put_contents(FEEDS_DB, '<?php /* '.chunk_split(base64_encode(serialize($GLOBALS['liste_flux']))).' */');

	$redir = basename($_SERVER['SCRIPT_NAME']).'?'.$query_string.'&msg=confirm_feeds_edit';
	redirection($redir);

}


