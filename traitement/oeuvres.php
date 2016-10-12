<?php

	include_once('connect.php');
	include_once('images.php');
	
	$page = "Location: $_SESSION[loc]";
	
	// Erreur login ou user != artiste => renvoi
	if ($login == '' || $privilege != 1 || $login != $_POST['artiste'])
		displayErreur($page, 201);
	
	// liste d'actions (renvoi à une fonction)
	$actions = array('ajouter', 'modifier', 'supprimer');
	// Vérification $_POST
	if (!$_POST)
		displayErreur($page, 1);
	// Vérification action
	if (!array_key_exists('action', $_POST) || !in_array($_POST['action'], $actions))
		displayErreur($page, 2);

	
	// Renvoi vers fonction
	if ($_POST['action'] == 'ajouter')
		ajoutOeuvre($connect, $page, $login);
	elseif ($_POST['action'] == 'modifier')
		modifOeuvre($connect, $page, $login);
	else
		suppOeuvre($connect, $page, $login);
		
		
	// Ajouter Oeuvre
	function ajoutOeuvre($connect, $page, $login) {
		// Vérification des valeurs de $_POST et récupération images et miniatures
		$images = checkOeuvreParams($page); 
		// Récupération des valeurs de champs (htmlspecialchars pour éviter erreurs d'affichage html)
		$titre = htmlspecialchars($_POST['titre'], ENT_QUOTES, "UTF-8");
		$categorie = $_POST['categorie'];
		$description = htmlspecialchars($_POST['description'], ENT_QUOTES, "UTF-8");
		if (count($images) == 0) // Aucune image importée
			displayErreur($page, 9);
		// Insertion de l'oeuvre (informations sans les images)
		$query = "INSERT INTO oeuvres(titre, categorie, artiste, description) VALUES (?, ?, ?, ?)";
		// Requête préparée pour éviter injection SQL
		$stmt = $connect -> prepare($query);
		if (!$stmt)
			displayErreur($page, 10);
		$stmt -> bind_param('siss', $titre, $categorie, $login, $description);
		if (!$stmt)
			displayErreur($page, 11);
		$stmt -> execute();
		if (!$stmt)
			displayErreur($page, 12);
		$stmt -> close();
		// Récupération id de l'oeuvre (id max)
		$result = $connect -> query("SELECT max(id) AS id FROM oeuvres");
		if (!$result || $result -> num_rows != 1)
			displayErreur($page, 13);
		$line = $result -> fetch_assoc();
		$id = $line['id'];
		// Création fichiers images et lignes images en BDD (images.php)
		ajoutImages($connect, $page, $images, $id, array(0,1,2));
		// Vérification si artiste suivi, envoi mail pour prévenir
		$query = "SELECT mail FROM suivi, user WHERE login=membre_id AND artiste_id='$login'";
		$result = $connect -> query($query);
		if (!$result)
			displayErreur($page, 21);
		if ($result -> num_rows > 0) { // L'artiste est suivi
			// Récupération adresses mails des destinataires
			$mailTo = '';
			while ($line = $result -> fetch_assoc()) {
				if ($mailTo != '')
					$mailTo .= ', ';
				$mailTo .= $line['mail'];
			}
			// Sujet du mail
			$sujet = "Nouvelle oeuvre de $login";
			// Corps du mail
			$message = "
			<!DOCTYPE html>
			<html>
				<head>
					<title>Nouvelle oeuvre de $login</title>
				</head>
				<body>
					<h1>$login vient d'ajouter une oeuvre:</h1>
					<p>Si vous souhaitez la voir: <a href='http://vps133660.ovh.net:8080/pages/oeuvre.php?id=$id'>Cliquez ici !</a></p>
				</body>
			</html>";
			// Envoi mail (functions.php)
			sendMail($mailTo, $sujet, $message);
		}
		displayMessage($page, 3);
	}
		
	// Modifier oeuvre
	function modifOeuvre($connect, $page, $login) {
		// Vérification id de l'oeuvre
		if (!array_key_exists('id', $_POST) || preg_match('/^[0-9]{1,10}$/', $_POST['id']) != 1)
			displayErreur($page, 14);
		$id = $_POST['id'];
		// Vérification old (id des images à supprimer séparées par des , )
		if (!array_key_exists('old', $_POST) || preg_match('/^([0-9]{1,10}(,[0-9]{1,10}){0,2})?$/', $_POST['old']) != 1)
			displayErreur($page, 15);
		$old = $_POST['old'];
		$images = checkOeuvreParams($page);
		// Vérification $id et $login correspondent
		$query = "SELECT * FROM oeuvres WHERE artiste='$login' AND id=$id";
		$result = $connect -> query($query);
		if (!$result || $result -> num_rows == 0)
			displayErreur($page, 16);
		$titre = htmlspecialchars($_POST['titre'], ENT_QUOTES, "UTF-8");
		$categorie = $_POST['categorie'];
		$description = htmlspecialchars($_POST['description'], ENT_QUOTES, "UTF-8");
		// Requête préparée pour éviter injection SQL (htmlspecialchars pour éviter interprétation des carac spéciaux en html)
		$query = "UPDATE oeuvres SET titre=?, categorie=?, description=? WHERE id=$id";
		$stmt = $connect -> prepare($query);
		if (!$stmt)
			displayErreur($page, 17);
		$stmt -> bind_param('sis', $titre, $categorie, $description);
		if (!$stmt)
			displayErreur($page, 18);
		$stmt -> execute();
		if (!$stmt)
			displayErreur($page, 19);
		$stmt -> close();
		// Suppression des images correspondantes à $old (images.php)
		if ($old != '')
			suppImages($connect, $page, $old);
		$newLen = count($images);
		if ($newLen != 0) {
			// Vérification des positions disponibles pour les images à ajouter/modifier
			$query = "SELECT position FROM images WHERE oeuvre=$id";
			$result = $connect -> query($query);
			if (!$result || $result -> num_rows > 3-$newLen) // Si nb de résultat > 3 - nb d'img à ajouter : Pas assez de place => erreur
				displayErreur($page, 20);
			$dispoCheck = array(true, true, true);
			if ($result -> num_rows > 0) {
				while ($line = $result -> fetch_assoc()) // On passe à false les positions déjà occupées
					$dispoCheck[$line['position']] = false;
			}
			$dispo = array();
			for ($i=0; $i<3; $i++) { // On récupère les positions à true
				if ($dispoCheck[$i])
					array_push($dispo, $i);
			} // ajout des images en plus ou à modifier (images.php)
			ajoutImages($connect, $page, $images, $id, $dispo);
		}
		displayMessage($page, 7);
	}
	
	// Supprimer oeuvre
	function suppOeuvre($connect, $page, $login) {
		if (!array_key_exists('id', $_POST) || preg_match('/^[0-9]{1,10}$/', $_POST['id']) != 1)
			displayErreur($page, 21);
		$id = $_POST['id'];
		// Vérification $id et $login correspondent + récupération id images
		$query = "SELECT I.id FROM images I, oeuvres O WHERE O.id=oeuvre AND artiste='$login' AND oeuvre=$id";
		$result = $connect -> query($query);
		if (!$result || $result -> num_rows == 0)
			displayErreur($page, 22);
		$img = array();
		while ($line = $result -> fetch_assoc())
			array_push($img, $line['id']);
		// Suppresion images correspondantes (images.php)
		suppImages($connect, $page, implode(',', $img));
		// Suppresion ligne oeuvre
		$query = "DELETE FROM oeuvres WHERE id=$id";
		$connect -> query($query);
		if (!$connect)
			displayErreur($page, 23);
		displayMessage($page, 8);
	}
    
    // Vérification paramètres $_POST, récupération img et min en array ($images[$i]['img'], $images[$i]['min'])
    function checkOeuvreParams($page) {
		// Vérif existence de tous les champs du formulaire
		if (!array_key_exists('titre', $_POST) || !array_key_exists('categorie', $_POST) || !array_key_exists('description', $_POST))
			displayErreur($page, 4);
		if (!array_key_exists('img1', $_POST) || !array_key_exists('min1', $_POST) || !array_key_exists('img2', $_POST))
			displayErreur($page, 5);
		if (!array_key_exists('min2', $_POST) || !array_key_exists('img0', $_POST) || !array_key_exists('min0', $_POST))
			displayErreur($page, 6);
		// Vérif format categorie
		if (preg_match('/^[0-9]{1,10}$/', $_POST['categorie']) != 1)
			displayErreur($page, 7);
		// Vérification et création array img et min
		$images = array();
		for ($i=0; $i<3; $i++) {
			if ($_POST["img$i"] != '' && $_POST["min$i"] != '') {
				if (preg_match('/^data\:image\/jpeg;base64,[a-z0-9=\+\/]+$/i', $_POST["img$i"]) == 1 && preg_match('/^data\:image\/jpeg;base64,[a-z0-9=\+\/]+$/i', $_POST["min$i"]) == 1) {
					array_push($images, array('img' => $_POST["img$i"], 'min' => $_POST["min$i"]));
				} else // Erreur de format d'image
					displayErreur($page, 8);
			}
		}
		return $images;
	}
