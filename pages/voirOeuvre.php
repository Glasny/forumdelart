<?php

    include_once('../traitement/connect.php');
    include_once('head.php');
    // css spécifique à la page
	echo "	<link rel='stylesheet' type='text/css' href='../css/voirOeuvre.css' />
			<script src='../js/voirOeuvre.js'></script>";
    include_once('menuHaut.php');
    
    // Récupérer adresse page courante dans $_SESSION['loc']
	saveCurrentUrl();
    
    $error = false;
    
	// Récupération id de l'oeuvre
	if ($_GET && array_key_exists('id', $_GET) && preg_match('/^[0-9]{1,10}$/', $_GET['id']) == 1)
		$id = $_GET['id'];
	else
		$error = true;
	
	if (!$error) {
		// Récupération informations oeuvre: id, titre, nom, description, date, artiste, nbNotes, moy, nbComment
		$query = "	SELECT 	O.id, titre, C.nom AS categorie, description,
							DATE_FORMAT(O.date, '\le %d/%m/%Y \à %H:%i') AS date,
	                        artiste, 
	                        (SELECT COUNT(*) FROM notes WHERE oeuvre_id=O.id) AS nbNotes,
	                        (SELECT AVG(valeur) FROM notes WHERE oeuvre_id=O.id) AS moy,
	                        (SELECT COUNT(*) FROM commentaires WHERE O.id=oeuvre_id) AS nbComment 
					FROM oeuvres O, categories C
					WHERE O.id=$id AND O.categorie=C.id";
		$result = $connect -> query($query);
		if (!$result) {
			echo $connect -> error; 
			exit;
		}
		if ($result -> num_rows != 1)
			$error = true;
		else {
			$oeuvre = $result -> fetch_assoc();
			$oId = $oeuvre['id'];
			$oTitre = $oeuvre['titre'];
			$oCat = $oeuvre['categorie'];
			$oDescri = nl2br($oeuvre['description']);
			$oDate = $oeuvre['date'];
			$oArtiste = $oeuvre['artiste'];
			$oNbNotes = $oeuvre['nbNotes'];
			$oMoy = round($oeuvre['moy'],1);
			$oNbComment = $oeuvre['nbComment'];
			// Récupération images
			$query = "SELECT repertoire, fichier, miniature FROM images WHERE oeuvre=$id ORDER BY position";
			$result = $connect -> query($query);
			if (!$result) {
				echo $connect -> error; 
				exit;
			}
			if ($result -> num_rows == 0)
				$error = true;
			else {
				$images = array();
				while ($line = $result -> fetch_assoc())
					array_push($images, $line);
				$lenImg = count($images);
				$img = '';
				if ($lenImg > 1) {
					$min = "<div id='listeMiniatures'>";
					for ($i=0; $i<$lenImg; $i++) {
						$img .= "<img src='../images/".$images[$i]['repertoire']."/".$images[$i]['fichier']."?time=".time()."' alt='image$i' />";
						$min .= "<img src='../images/".$images[$i]['repertoire']."/".$images[$i]['miniature']."?time=".time()."' alt='miniature$i' />";
					}
					$min .= "</div>";
				} else {
					$min = '';
					$img .= "<img src='../images/".$images[0]['repertoire']."/".$images[0]['fichier']."' alt='image' />";
				}
				// Récupération commentaires
				$query = "SELECT id, auteur_id, contenu, DATE_FORMAT(date, '\le %d/%m/%Y \à %H:%i') AS date FROM commentaires WHERE oeuvre_id=$id ORDER BY id";
				$result = $connect -> query($query);
				if ($result && $result -> num_rows > 0) {
					$commentaires = array();
					while ($line = $result -> fetch_assoc())
						array_push($commentaires, $line);
					$nbComment = count($commentaires);
				} else
					$nbComment = 0;
				// Si utilisateur connecté, vérif si il a déjà noté l'oeuvre
				if ($login != '' && $login != $oArtiste) {
					$query = "SELECT * FROM notes WHERE oeuvre_id=$oId AND auteur_id='$login'";
					$result = $connect -> query($query);
					if ($result && $result -> num_rows > 0)
						$note = 'fait';
					else
						$note = 'non';
				} else
					$note = false;
			}
		}
	}
	
	echo "	<div id='mainBloc'>";
	// Notification d'erreur
	if ($error)
		echo "	<div id='errorDisplay'>Erreur de traitement</div>
			</div>";
	else {
		// Affichage image principale
		echo "	<div id='oeuvreDisplay'>
					<div id='oeuvreImages'>
						<div id='mainWindow'>$img<div id='loupe'></div></div>$min";
		// Affichage informations : titre, date, categorie, artiste(lien), description
		echo "		</div>
					<div id='oeuvreInfo'>
						<div id='oeuvreDate'>Oeuvre ajoutée $oDate</div>
						<div class='oeuvreInfoLine'>
							<span id='oeuvreTitre'>$oTitre</span>
							<div id='oeuvreCategorie'>$oCat</div>
						</div>
						<div class='oeuvreInfoLine'>
							<a id='oeuvreArtiste' href='./compte.php?user=$oArtiste'>$oArtiste</a>
						</div>
						<div class='oeuvreInfoLine'>
							<div class='titrePartie'>À propos de l'oeuvre:</div>
							<div id='oeuvreDescri'>$oDescri</div>
						</div>
					</div>
				</div>";
		// Affichage notes :  moyenne + si user connecté et pas encore noté: peut noter l'oeuvre. Si déjà noté, le fait savoir
		echo "	<div class='oeuvrePartie' id='partieNotes'>
					<div class='titrePartie'>Notes</div>
					<div id='curNotes'>
						<span id='moyNotes'>$oMoy/10</span> 
						<span id='nbVotes'>($oNbNotes votes)</span>
					</div>";
		if ($note == 'fait')
			echo "	<div id='userNote'>Vous avez noté cette oeuvre</div>";
		elseif ($note == 'non') {
			echo "	<div id='userNote'>
						<form method='POST' action='../traitement/noter.php'>
							Noter cette oeuvre:
							<select name='note'>";
			for ($i=10; $i>=0; $i--)
				echo "			<option value='$i'>$i</option>";
			echo "			</select> /10
							<input type='hidden' name='oeuvre' value='$oId' />
							<input type='submit' value='Valider' />
						</form>
					</div>";
		}
		echo "	</div>";
		// Affichage commentaires
		echo "	<div class='oeuvrePartie' id='partieCommentaires'>
					<div class='titrePartie'>Commentaires</div>
					<div id='oeuvreCommentaires'>";
		if ($nbComment == 0) // Pas de commentaires
			echo "		<div id='noComment'>Pas de commentaires</div>";
		else {
			for ($i=0; $i<$nbComment; $i++) {
				$id = $commentaires[$i]['id'];
				$auteur = $commentaires[$i]['auteur_id'];
				$contenu = nl2br($commentaires[$i]['contenu']);
				$contenu2 = $commentaires[$i]['contenu'];
				$date = $commentaires[$i]['date'];
				echo "	<div class='commentBloc'>
							<div class='commentHead'>
								<a class='commentAuteur' href='./compte.php?user=$auteur'>$auteur</a>
								<span class='commentDate'>$date</span>
							</div>
							<div class='commentContenu'>$contenu</div>";
				// Si admin ou auteur du commentaire, possibilité d'édition/suppression
				if ($login == $auteur || $privilege == 0)
					echo "	<form class='editForm' method='POST' action='../traitement/commentaires.php'>
								<textarea name='contenu' required>$contenu2</textarea>
								<input type='hidden' name='id' value='$id' />
								<input type='hidden' name='oeuvre' value='$oId' />
								<input type='hidden' name='action' value='modifier' />
								<input type='submit' class='validerEdit' value='Valider' />
							</form>
							<button class='cancelEdit'>Annuler</button>
							<div class='commentBottom'>
								<button class='editComment'>Modifier</button>
								<form method='POST' action='../traitement/commentaires.php' onsubmit='return confirmSupp(this);'>
									<input type='hidden' name='id' value='$id' />
									<input type='hidden' name='oeuvre' value='$oId' />
									<input type='hidden' name='action' value='supprimer' />
									<input type='submit' class='deleteComment' value='Supprimer' />
								</form>
							</div>";
				echo "	</div>";
			}
		}
		if ($login != '') // Si user connecté: Possibilité d'ajout d'un commentaire
			echo "		<div id='postComment'>
							<form method='POST' action='../traitement/commentaires.php'>
								<textarea name='contenu' placeholder='Votre commentaire...' required></textarea>
								<input type='hidden' name='oeuvre' value='$oId' />
								<input type='hidden' name='action' value='ajouter' />
								<input type='submit' value='Ajouter commentaire' />
							</form>
						</div>";
				
		echo "		</div>
				</div>
			</div>
			<div id='vuePleinEcran'>
				<img id='voirGauche' src='../images/gauche.svg' alt='voirGauche' />
				<img id='voirDroite' src='../images/droite.svg' alt='voirDroite' />
				<div id='blocVuePleinEcran'>$img</div>
				<img id='fermerPleinEcran' src='../images/fermer.svg' alt='fermerPleinEcran' />
			</div>";
	}
	
	// pied de page
	include_once('footer.php');
