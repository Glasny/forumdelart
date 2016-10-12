<?php

	include('../traitement/connect.php');
	// Renvoi si user != artiste
	if ($privilege != 1 || $login == '') {
		header("Location: ./index.php");
		exit;
	}
	include('head.php');
	// css et js spécifiques à la page
	echo "	<link rel='stylesheet' type='text/css' href='../css/addModifOeuvres.css' />
			<script src='../js/addModifOeuvres.js'></script>";
    include('menuHaut.php');
    
    $error = false;
    
    // Vérification id
    if (!$_GET || !array_key_exists('id', $_GET) || preg_match('/^[0-9]{1,10}$/', $_GET['id']) != 1)
		$error = true;
	$oId = $_GET['id'];
	// Récupération des données de l'oeuvre
	$query = "SELECT titre, description, categorie FROM oeuvres WHERE id=$oId AND artiste='$login'";
	$result = $connect -> query($query);
	if (!$result || $result -> num_rows != 1)
		$error = true;
	else {
		$line = $result -> fetch_assoc();
		$titre = $line['titre'];
		$description = $line['description'];
		$oCat = $line['categorie'];
		// Récupération images correspondantes à l'oeuvre
		$query = "SELECT id, miniature, repertoire FROM images WHERE oeuvre=$oId ORDER BY position";
		$result = $connect -> query($query);
		if (!$result || $result -> num_rows == 0)
			$error = true;
		else {
			$images = array();
			$nbImg = $result -> num_rows;
			while ($line = $result -> fetch_assoc())
				array_push($images, $line);
		}
	}
   
    
    // Récupérer catégories pour select
	$query = "SELECT * FROM categories ORDER BY nom";
    $result = $connect -> query($query);
	if (!$result || $result -> num_rows == 0) {
		echo $connect -> error; 
		exit;
	}
	$categories = array();
	while ($line = $result -> fetch_assoc())
		array_push($categories, $line);
	/* Création array position catégorie "enfant" par id catégorie "parent" pour distinguer parents/enfants dans select */
	$len = count($categories);
	$categorieByParentId = array();
	for ($i=0; $i<$len; $i++) {
		$parent = $categories[$i]['parent'];
		if ($parent != null) {
			if (!array_key_exists($parent, $categorieByParentId))
				$categorieByParentId[$parent] = array();
			array_push($categorieByParentId[$parent], $i);
		}
	}
	// Création des options du select
	$selectCategories = '';
	for ($i=0; $i<$len; $i++) {
		$parent = $categories[$i]['parent'];
		$nom = $categories[$i]['nom'];
		$id = $categories[$i]['id'];
		if (is_null($parent)) { // Catégories parents
			if ($oCat == $id)
				$selectCategories .= "<option value='$id' style='font-weight:bold;' selected><strong>$nom</strong></option>";
			else
				$selectCategories .= "<option value='$id' style='font-weight:bold;'><strong>$nom</strong></option>";
			if (array_key_exists($id, $categorieByParentId)) { // Vérif catégories enfants
				$len2 = count($categorieByParentId[$id]);
				for ($j=0; $j<$len2; $j++) { // Catégories enfants
					$pos = $categorieByParentId[$id][$j];
					$nom2 = $categories[$pos]['nom'];
					$id2 = $categories[$pos]['id'];
					if ($oCat == $id2)
						$selectCategories .= "<option value='$id2' selected>- $nom2</option>";
					else
						$selectCategories .= "<option value='$id2'>- $nom2</option>";
				}
			}
		}
	}

		

	/* Vérification formulaire avant envoi + traitement images sur addModifOeuvres.js 
	 * Images importées dans input file redimensionnées dans canvas puis code stocké dans input hidden du formulaire (pas de multipart/form-data nécessaire) */
	echo "  <div id='mainBloc'>
				<div id='pageTitre'>Modifier une Oeuvre</div>
				<div id='oeuvreFormBloc'>";
	if ($error)
		echo "		<div id='errorDisplay'>Erreur de traitement</div>";
	else {
		echo "		<form method='POST' action='../traitement/oeuvres.php' onsubmit='return confirmModifOeuvre(this);'>
						<div class='formLine'><label for='login'>Titre de l'oeuvre :</label><input type='text' name='titre' value='$titre' required /></div>
						<div class='formLine'><label for='categorie'>Catégorie :</label><select name='categorie'>$selectCategories</select></div>
						<div class='formLine'><label for='description' class='textareaLabel'>Description :</label><textarea name='description'>$description</textarea></div>
						<div id='imgInfo'></div>";
		// Affichage des images déjà enregistrées (ajout bouton .suppImg => ajoute .imgId dans old, supprime la balise img, affiche input .modifImg
		for ($i=0; $i<$nbImg; $i++) {
			$j = $i+1;
			$id = $images[$i]['id'];
			$lien = '../images/'.$images[$i]['repertoire'].'/'.$images[$i]['miniature']."?time=".time();
			echo "		<div class='formLine'>
							<label>Image $j :</label>
							<input type='file' accept='image/*' class='modifImg uploadImg' id='$i' />
							<img src='$lien' />
							<canvas id='canvasMin$i'></canvas>
							<div class='cancelImg bouton'>Retirer</div>
							<div class='suppImg bouton'>Supprimer</div>
							<div class='imgId'>$id</div>
						</div>";
		}
		// Si nombre d'images de l'oeuvre < 3, insertion lignes "normales"
		if ($nbImg<3) {
			for ($i=$nbImg; $i<3; $i++) {
				$j = $i+1;
				echo "	<div class='formLine'>
							<label>Image $j :</label>
							<input type='file' accept='image/*' class='uploadImg' id='$i' />
							<canvas id='canvasMin$i'></canvas>
							<div class='cancelImg bouton'>Retirer</div>
						</div>";
			}
		}
		// id des images à supprimer stockées dans #imgOld
		echo "			<input type='hidden' name='artiste' value='$login'/>
						<input type='hidden' name='id' value='$oId'/>
						<input type='hidden' name='action' value='modifier'/>
						<input type='hidden' name='old' id='imgOld' value=''/>
						<input type='hidden' name='img0' id='srcImg0' value=''/>
						<input type='hidden' name='min0' id='srcMin0' value=''/>
						<input type='hidden' name='img1' id='srcImg1' value=''/>
						<input type='hidden' name='min1' id='srcMin1' value=''/>
						<input type='hidden' name='img2' id='srcImg2' value=''/>
						<input type='hidden' name='min2' id='srcMin2' value=''/>
						<div class='formBouton'>
							<a class='centerBouton bouton' href='./gererOeuvres.php'>Annuler</a>
							<input class='centerBouton bouton' type='submit' value='Valider' />
						</div>
						<div id='creerCompteErrorBloc'></div>
					</form>
					<canvas id='canvasImg'></canvas>";
	}
	echo "		</div>
			</div>";
			
	 include('footer.php');
					
