<?php

	include_once('connect.php');
	
	$page = "Location: $_SESSION[loc]";
	
	// Si login/sid non valide => renvoi vers index
	if ($login == '')
		displayErreur($page, 201);
	
	// liste d'actions (renvoi à une fonction)
	$actions = array('ajouter', 'annuler');
	// Vérification $_POST
	if (!$_POST)
		displayErreur($page, 1);
	// Vérification action
	if (!array_key_exists('action', $_POST) || !in_array($_POST['action'], $actions))
		displayErreur($page, 2);
	// Vérif artiste et artiste != login
	if (!array_key_exists('artiste', $_POST) || preg_match('/^[a-z0-9_]{4,20}$/i', $_POST['artiste']) != 1 || $login == $_POST['artiste'])
		displayErreur($page, 3);
	
	$artiste = $_POST['artiste'];
	
	// Renvoi vers fonction
	if ($_POST['action'] == 'ajouter')
		ajouterSuivi($connect, $page, $login, $artiste);
	else
		annulerSuivi($connect, $page, $login, $artiste);
		
		
	// Ajout d'un suivi
	function ajouterSuivi($connect, $page, $login, $artiste) {
		// Vérification artiste (privilege = 1)
		$query = "SELECT privilege FROM user WHERE login='$artiste'";
		$result = $connect -> query($query);
		if (!$result || $result -> num_rows != 1)
			displayErreur($page, 4);
		$line = $result -> fetch_assoc();
		$privilege = $line['privilege'];
		if ($privilege != 1)
			displayErreur($page, 5);
		// Ajout du suivi
		$query = "INSERT INTO suivi(artiste_id, membre_id) VALUES ('$artiste', '$login')";
		$connect -> query($query);
		if (!$connect)
			displayErreur($page, 6);
		header($page);
	}
	
	// Suppression d'un suivi
	function annulerSuivi($connect, $page, $login, $artiste) {
		$query = "DELETE FROM suivi WHERE artiste_id='$artiste' AND membre_id='$login'";
		$connect -> query($query);
		if (!$connect)
			displayErreur($page, 7);
		header($page);
	}
	
	
