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
	// Remplissage des options du select
	$selectCategories = '';
	for ($i=0; $i<$len; $i++) {
		$parent = $categories[$i]['parent'];
		$nom = $categories[$i]['nom'];
		$id = $categories[$i]['id'];
		if (is_null($parent)) { // Catégories parents
			$selectCategories .= "<option value='$id' style='font-weight:bold;'><strong>$nom</strong></option>";
			if (array_key_exists($id, $categorieByParentId)) { // Vérif catégories enfants
				$len2 = count($categorieByParentId[$id]);
				for ($j=0; $j<$len2; $j++) { // Catégories enfants
					$pos = $categorieByParentId[$id][$j];
					$nom2 = $categories[$pos]['nom'];
					$id2 = $categories[$pos]['id'];
					$selectCategories .= "<option value='$id2'>- $nom2</option>";
				}
			}
		}
	}

		

	/* Vérification formulaire avant envoi + traitement images sur addModifOeuvres.js 
	 * Images importées dans input file redimensionnées dans canvas puis code stocké dans input hidden du formulaire (pas de multipart/form-data nécessaire) */
	echo "  <div id='mainBloc'>
				<div id='pageTitre'>Ajouter une Oeuvre</div>
				<div id='oeuvreFormBloc'>
					<form method='POST' action='../traitement/oeuvres.php' onsubmit='return confirmAddOeuvre(this);'>
						<div class='formLine'><label for='login'>Titre de l'oeuvre :</label><input type='text' name='titre' required autofocus /></div>
						<div class='formLine'><label for='categorie'>Catégorie :</label><select name='categorie'>$selectCategories</select></div>
						<div class='formLine'><label for='description' class='textareaLabel'>Description :</label><textarea name='description'></textarea></div>
						<div id='imgInfo'></div>
						<div class='formLine'>
							<label>Image 1 :</label>
							<input type='file' accept='image/*' class='uploadImg' id='0' />
							<canvas id='canvasMin0'></canvas>
							<div class='cancelImg bouton'>Retirer</div>
						</div>
						<div class='formLine'>
							<label>Image 2 :</label>
							<input type='file' accept='image/*' class='uploadImg' id='1' />
							<canvas id='canvasMin1'></canvas>
							<div class='cancelImg bouton'>Retirer</div>
						</div>
						<div class='formLine'>
							<label>Image 3 :</label>
							<input type='file' accept='image/*' class='uploadImg' id='2' />
							<canvas id='canvasMin2'></canvas>
							<div class='cancelImg bouton'>Retirer</div>
						</div>
						<input type='hidden' name='artiste' value='$login'/>
						<input type='hidden' name='action' value='ajouter'/>
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
					<canvas id='canvasImg'></canvas>
				</div>
			</div>";
			
			
	 include('footer.php');
					
