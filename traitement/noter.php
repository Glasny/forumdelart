<?php

	include_once('connect.php');
	
	$page = "Location: $_SESSION[loc]";
	
	// Si login/sid non valide => renvoi
	if ($login == '')
		displayErreur($page, 201);
	
	

	// Vérification $_POST
	if (!$_POST)
		displayErreur($page, 1);
	// Vérification note
	if (!array_key_exists('note', $_POST) || preg_match('/^[0-9]{1}|(10)$/', $_POST['note']) != 1)
		displayErreur($page, 2);
	// Vérification oeuvre
	if (!array_key_exists('oeuvre', $_POST) || preg_match('/^[0-9]{1,10}$/', $_POST['oeuvre']) != 1)
		displayErreur($page, 3);

	$oeuvre = $_POST['oeuvre'];
	$valeur = $_POST['note'];

	// Ajout note
	$query = "INSERT INTO notes VALUES ('$login', $oeuvre, $valeur)";
	$connect -> query($query);
	if (!$connect)
		displayErreur($page, 4);
	else
		displayMessage($page, 4);
