<?php

	include_once('connect.php');
	include_once('images.php'); // Pour suppression user
	
	$page = "Location: $_SESSION[loc]";
	
	// Si login/sid non valide => renvoi vers index
	if ($login == '')
		displayErreur($page, 201);
	
	// liste d'actions (renvoi à une fonction)
	$actions = array('editPass', 'editInfo', 'modifPrivilege', 'suppUser');
	// Vérification $_POST
	if (!$_POST)
		displayErreur($page, 1);
	// Vérification action
	if (!array_key_exists('action', $_POST) || !in_array($_POST['action'], $actions))
		displayErreur($page, 2);
	
	// Renvoi vers fonction (pour modifPrivilege et supUser $login et $privilege correspondent à Admin)
	if ($_POST['action'] == 'editPass')
		editPass($connect, $page, $login);
	elseif ($_POST['action'] == 'editInfo')
		editInfo($connect, $page, $login);
	elseif ($_POST['action'] == 'modifPrivilege')
		modifPrivilege($connect, $login, $privilege);
	else
		suppUser($connect, $login, $privilege);
		
		
	// Modif mot de passe
	function editPass ($connect, $page, $login) {
		// Vérification validité login/mdp actuel (functions.php)
		if (!verifPass($connect))
			displayErreur($page, 201);
		// Vérif nouveau pass (new1 et new2)
		if (!array_key_exists('new1', $_POST) || !array_key_exists('new2', $_POST) || $_POST['new1'] != $_POST['new2'] || preg_match('/^[A-Z0-9_]{4,20}$/i', $_POST['new1']) != 1)
			displayErreur($page, 3);
		// Vérif actuel != new
		if ($_POST['new1'] == $_POST['pass'])
			displayErreur($page, 4);
		$new = hash('sha256', $_POST['new1']);
		// modification du mot de passe
		$query = "UPDATE user SET pass='$new' WHERE login='$login'";
		$connect -> query($query);
		// Si erreur de requête: échec de la requête
		if (!$connect)
			displayErreur($page, 5);
		else // Réussite de la modification
			displayMessage($page, 1);
	}
		
		
	// Modif informations
	function editInfo ($connect, $page, $login) {
		// Vérif nom, prenom, mail, description dans $_POST
		if (!array_key_exists('nom', $_POST) || !array_key_exists('prenom', $_POST) || !array_key_exists('mail', $_POST) || !array_key_exists('informations', $_POST))
			displayErreur($page, 6);
		// Verif validité mail
		if (preg_match('/^[a-z0-9_\.-]+@[a-z0-9_\.-]+\.[a-z]{2,6}$/i', $_POST['mail']) != 1)
			displayErreur($page, 7);
		// Utilisation requête préparée pour éviter injection SQL (htmlspecialchars pour éviter interprétation des carac spéciaux en html)
		$stmt = $connect -> prepare("UPDATE user SET nom=?, prenom=?, mail=?, informations=? WHERE login='$login'");
		if (!$stmt)
			displayErreur($page, 8);
		$stmt -> bind_param('ssss', $nom, $prenom, $mail, $informations);
		if (!$stmt)
			displayErreur($page, 9);
		$nom = htmlspecialchars($_POST['nom'], ENT_QUOTES, "UTF-8");
		$prenom = htmlspecialchars($_POST['prenom'], ENT_QUOTES, "UTF-8");
		$mail = $_POST['mail'];
		$informations = htmlspecialchars($_POST['informations'], ENT_QUOTES, "UTF-8");
		$stmt -> execute();
		if (!$stmt)
			displayErreur($page, 10);
		$stmt -> close();
		displayMessage($page, 1);
	}
	
	// Modification privilege user ($login=admin)
	function modifPrivilege($connect, $login, $privilege) {
		$page = 'Location: ../pages/index.php';
		// Vérification admin, sinon renvoi sur index.php
		if ($privilege != 0 || $login != 'admin')
			displayErreur($page, 11);
		$page = 'Location: ../pages/gererUser.php';
		// Vérification login de l'utilisateur à modifier
		if (!array_key_exists('user', $_POST) || preg_match('/^[A-Z0-9_]{4,20}$/i', $_POST['user']) != 1)
			displayErreur($page, 12);
		// Vérification privilege
		if (!array_key_exists('privilege', $_POST) || preg_match('/^1|2$/', $_POST['privilege']) != 1)
			displayErreur($page, 13);
		$query= "UPDATE user SET privilege=$_POST[privilege] WHERE login='$_POST[user]'";
		$connect -> query($query);
		if (!$connect)
			displayErreur($page, 14);
		// Si promotion : Envoi mail pour prévenir
		if ($_POST['privilege'] == 1) {
			// Récupération mail user
			$query = "SELECT mail FROM user WHERE login='$_POST[user]'";
			$result = $connect -> query($query);
			if ($result && $result -> num_rows == 1) {
				$line = $result -> fetch_assoc();
				$mail = $line['mail'];
				$sujet = "Vous avez maintenant la possibilité d'ajouter vos propres oeuvres !";
				$message = "
				<!DOCTYPE html>
				<html>
					<head>
						<titre>Changement de statut</titre>
					</head>
					<body>
						<h1>Vous avez maintenant la possibilité de partager vos oeuvres</h1>
						<a href='http://vps133660.ovh.net:8080/pages/index.php'>Forum de l'Art</a>
					</body>
				</html>";
				// Envoi du mail (functions.php)
				sendMail($mail, $sujet, $message);
			}
		}
		displayMessage($page, 9);
	}
	
	// Suppression user ($login=admin)
	function suppUser($connect, $login, $privilege) {
		$page = 'Location: ../pages/index.php';
		// Vérification admin, sinon renvoi sur index.php
		if ($privilege != 0 || $login != 'admin')
			displayErreur($page, 15);
		$page = 'Location: ../pages/gererUser.php';
		// Vérification login de l'utilisateur à supprimer
		if (!array_key_exists('user', $_POST) || preg_match('/^[A-Z0-9_]{4,20}$/i', $_POST['user']) != 1)
			displayErreur($page, 16);
		$user = $_POST['user'];
		// Vérification si images de l'utilisateur à supprimer pour les supprimer avant
		$query = "SELECT I.id FROM oeuvres O, images I WHERE artiste='$user' AND oeuvre=O.id";
		$result = $connect -> query($query);
		if (!$result)
			displayErreur($page, 14);
		if ($result -> num_rows > 0) {
			$img = '';
			while ($line = $result -> fetch_assoc()) {
				if ($img != '')
					$img .= ',';
				$img .= $line['id'];
			}
			suppImages($connect, $page, $img); // dans images.php
		}
		$query = "DELETE FROM user WHERE login='$user'";
		$connect -> query($query);
		if (!$connect)
			displayErreur($page, 15);
		displayMessage($page, 10);
	}
