<?php

    include('../traitement/connect.php');
    include('head.php');
    // css spécifique à la page
	echo "	<link rel='stylesheet' type='text/css' href='../css/listeOeuvres.css' />
			<script src='../js/listeOeuvres.js'></script>";
    include('menuHaut.php');
    
    // Récupérer adresse page courante dans $_SESSION['loc']
	saveCurrentUrl();
    
    
    // Récupérer catégories pour options de filtre
	$query = "SELECT * FROM categories ORDER BY nom";
    $result = $connect -> query($query);
	if (!$result || $result -> num_rows == 0) {
		echo $connect -> error; 
		exit;
	} else {
		$categories = array();
		while ($line = $result -> fetch_assoc())
			array_push($categories, $line);
		/* Création array position catégorie "enfant" par id catégorie "parent" pour ajouter catégories enfant dans restriction 
		 * et tri filtre par catégories */
		$lenCategories = count($categories);
		$categorieByParentId = array();
		for ($i=0; $i<$lenCategories; $i++) {
			$parent = $categories[$i]['parent'];
			if ($parent != null) {
				if (!array_key_exists($parent, $categorieByParentId))
					$categorieByParentId[$parent] = array();
				array_push($categorieByParentId[$parent], $i);
			}
		}
	}
	
	// Récupérer logins d'artistes pour options de filtre
	$query = "SELECT DISTINCT artiste FROM oeuvres";
    $result = $connect -> query($query);
	if (!$result || $result -> num_rows == 0) {
		echo $connect -> error; 
		exit;
	} else {
		$artistes = array();
		while ($line = $result -> fetch_assoc())
			array_push($artistes, $line['artiste']);
	}
    
    
    $limit = 12;  // Nb d'oeuvres par page
    $page = 0;
    $categorie = '';
    $artiste = '';
    $tri = 'Dates';
    $ordre = 'id DESC'; // tri par défaut
    // Options de tri
    $triArray = array('Dates'=>'id DESC', 'Notes'=>'moy DESC', 'Commentaires'=>'nbComment DESC', 'Artiste'=>'artiste', 'Titre'=>'titre');
    // récupération page, categorie, artiste, tri
	if ($_GET) {
		if (array_key_exists('page', $_GET) && preg_match('/^[0-9]{1,3}$/', $_GET['page']) == 1)
			$page = intval($_GET['page']);
		if (array_key_exists('categorie', $_GET) && preg_match('/^[0-9]{1,3}$/', $_GET['categorie']) == 1)
			$categorie = intval($_GET['categorie']);
		if (array_key_exists('artiste', $_GET) && preg_match('/^[a-z0-9_]{4,20}$/i', $_GET['artiste']) == 1)
			$artiste = $_GET['artiste'];
		if (array_key_exists('tri', $_GET) && array_key_exists($_GET['tri'], $triArray)) {
			$tri = $_GET['tri'];
			$ordre = $triArray[$_GET['tri']];
		}
	}
	
	if ($artiste == '' && $categorie == '')
		$restriction = '';
	else {
		$restriction = 'WHERE ';
		if ($artiste != '')
			$restriction .= "artiste='$artiste'";
		if ($categorie != '') {
			// Vérification et ajout catégories "enfants"
			$selectCategories = array(); // catégorie sélectionnée + catégories "enfants"
			array_push($selectCategories, $categorie);
			if (array_key_exists($categorie, $categorieByParentId)) {
				$len = count($categorieByParentId[$categorie]);
				for ($i=0; $i<$len; $i++)
					array_push($selectCategories, $categories[$categorieByParentId[$categorie][$i]]['id']);
			}
			if ($artiste != '')
				$restriction .= ' AND ';
			$restriction .= 'categorie IN ('.implode(',', $selectCategories).')';
		}
	}


	$offset = $page*$limit;
	
	
	// Nombre total d'oeuvres
	$query = "SELECT count(*) as nb FROM oeuvres $restriction";
	$result = $connect -> query($query);
	if (!$result || $result -> num_rows == 0) {
		echo $connect -> error; 
		exit;
	} else {
		$line = $result -> fetch_assoc();
		$totalOeuvres = $line['nb']; // total nb d'oeuvres
		$nbPages = ceil($totalOeuvres/$limit); // nb de pages correspondant
		if ($totalOeuvres < $offset) // Valeur page incorrecte => affichage à partir de 0
			$offset = 0;
	}

	$error = false;
	$oeuvres = array();
	
	if ($restriction != '')
		$restriction .= " AND"; // Pour ajout à la requête de base
	else
		$restriction = "WHERE";
	
	
	$query = "	SELECT 	O.id AS id, 
                        O.titre AS titre, 
                        I.repertoire AS repertoire, 
                        I.miniature AS fichier, 
                        DATE_FORMAT(O.date, '\le %d/%m/%Y \à %H:%i') AS date,
                        O.artiste AS artiste, 
                        (SELECT COUNT(*) FROM notes WHERE oeuvre_id=O.id) AS nbNotes,
                        (SELECT AVG(valeur) FROM notes WHERE oeuvre_id=O.id) AS moy,
                        (SELECT COUNT(*) FROM commentaires WHERE O.id=oeuvre_id) AS nbComment 
				FROM oeuvres O, images I
				$restriction O.id=I.oeuvre AND I.position=0
				ORDER BY $ordre LIMIT $limit OFFSET $offset";
	$result = $connect -> query($query);
	if (!$result || $result -> num_rows == 0)
		$error = true;
	else {
		$nbOeuvres = $result -> num_rows;
		while ($line = $result -> fetch_assoc())
			array_push($oeuvres, $line);
	}

	// Affichage bloc principal
    echo "  <div id='mainBloc'>
                <div id='pageTitre'>Les Oeuvres</div>";
    // Affichage sélection de pages si applicable     
	if ($nbPages > 1) {
		echo "	<div id='liensPages'>Pages :";
		for ($i=0; $i<$nbPages; $i++) {
			$j = $i+1;
			if ($page == $i)
				echo "	<a href='./listeOeuvres.php?page=$i&artiste=$artiste&categorie=$categorie&tri=$tri' id='selectedPage'>$j</a>";
			else
				echo "	<a href='./listeOeuvres.php?page=$i&artiste=$artiste&categorie=$categorie&tri=$tri'>$j</a>";
		}
		echo "	</div>";
	}
	// Affichage menu tri et filtres
	echo "		<div id='listeOeuvresBloc'>	
					<div id='listeOeuvresMenu'>
						<form method='GET' action='./listeOeuvres.php'>
							<div id='menuTri'>
								<span class='menuTitre2'>Trier par</span>
								<select name='tri'>";
	// Insertion options de tri
	foreach ($triArray as $k => $v) {
		if ($tri == $k)
			echo "					<option value='$k' selected>$k</option>";
		else
			echo "					<option value='$k'>$k</option>";
	}
	echo "						</select>
								<input type='submit' value='Appliquer' />
							</div>
							<div id='menuSepare'></div>
							<div id='menuFiltres'>
								<div class='menuTitre'>Filtres</div>
								<div class='menuTitre2'>Artistes</div>
								<select name='artiste'>
									<option value=''>Sélectionner</option>";
	// Insertion options de tri par artistes
	$len = count($artistes);
	for ($i=0; $i<$len; $i++) {
		$nom = $artistes[$i];
		if ($nom == $artiste)
			echo "					<option value='$nom' selected>$nom</option>";
		else
			echo "					<option value='$nom'>$nom</option>";
	}

	echo "						</select>
								<div class='menuTitre2'>Catégories</div>
								<select id='selectCategorie' name='categorie'>
									<option value=''>Toutes</option>";
	// Insertion options de tri par catégorie
	for ($i=0; $i<$lenCategories; $i++) {
		$parent = $categories[$i]['parent'];
		$nom = $categories[$i]['nom'];
		$id = $categories[$i]['id'];
		if (is_null($parent)) { // Catégories parents
			if ($id == $categorie)
				echo "					<option value='$id' style='font-weight:bold;' selected>$nom</option>";
			else
				echo "					<option value='$id' style='font-weight:bold;'>$nom</option>";
			if (array_key_exists($id, $categorieByParentId)) { // Insertion catégories enfants
				$len = count($categorieByParentId[$id]);
				for ($j=0; $j<$len; $j++) { // Catégories enfants
					$pos = $categorieByParentId[$id][$j];
					$nom2 = $categories[$pos]['nom'];
					$id2 = $categories[$pos]['id'];
					if ($id2 == $categorie)
						echo "			<option value='$id2' selected>- $nom2</option>";
					else
						echo "			<option value='$id2'>- $nom2</option>";
				}
			}
		}
	}
	echo "						</select>
								<button id='resetMenu'>Réinitialiser</button>
								<input type='submit' value='Appliquer' />
							</div>
						</form>
					</div>
					<div id='listeOeuvres'>";
	if ($error) 	// Si erreur ou pas d'artistes
		echo "			<div id='listeError'>Pas d'oeuvres correspondantes</div>";
	else {
		for ($i=0; $i<$nbOeuvres; $i++) {
			$lienImage = "../images/".$oeuvres[$i]['repertoire']."/".$oeuvres[$i]['fichier']."?time=".time();
			$id = $oeuvres[$i]['id'];
			$artiste = $oeuvres[$i]['artiste'];
			$titre = $oeuvres[$i]['titre'];
			$date = $oeuvres[$i]['date'];
			$moy = round($oeuvres[$i]['moy'],1);
			$nbNotes = $oeuvres[$i]['nbNotes'];
			$nbComment = $oeuvres[$i]['nbComment'];
		echo "      	<a class='oeuvreListeBloc' href='./voirOeuvre.php?id=$id'>
							<div class='oeuvreBlocImage'><img src='$lienImage' alt='$titre' /></div>
							<div class='oeuvreBlocTitre'>$titre<span class='oeuvreBlocArtiste'> ($artiste)</span></div>
							<div class='oeuvreBlocInfo'>$date<br/>
							$moy/10 ($nbNotes votes)<br/>
							($nbComment commentaires)</div>
						</a>";
		}
	}
	echo "			</div>
				</div>
			</div>";
	
	// pied de page
	include('footer.php');
