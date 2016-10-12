<?php

	include_once('connect.php');
	
	$page = "Location: $_SESSION[loc]";
	
	// Si login/sid non valide => renvoi vers index
	if ($login == '')
		displayErreur($page, 201);
	
	// liste d'actions (renvoi à une fonction)
	$actions = array('ajouter', 'modifier', 'supprimer');
	// Vérification $_POST
	if (!$_POST)
		displayErreur($page, 1);
	// Vérification action
	if (!array_key_exists('action', $_POST) || !in_array($_POST['action'], $actions))
		displayErreur($page, 2);
	// Vérification oeuvre
	if (!array_key_exists('oeuvre', $_POST) || preg_match('/^[0-9]{1,10}$/', $_POST['oeuvre']) != 1)
		displayErreur($page, 3);

	$oeuvre = $_POST['oeuvre'];
	
	// Renvoi vers fonction
	if ($_POST['action'] == 'ajouter')
		ajoutCommentaire($connect, $page, $login, $oeuvre);
	elseif ($_POST['action'] == 'modifier')
		modifCommentaire($connect, $page, $login);
	else
		suppCommentaire($connect, $page, $login);
		
		
	// Ajouter commentaire
	function ajoutCommentaire($connect, $page, $login, $oeuvre) {
		if (!array_key_exists('contenu', $_POST))
			displayErreur($page, 4);
		$query = "INSERT INTO commentaires(auteur_id, oeuvre_id, contenu) VALUES (?, ?, ?)";
		// Requête préparée pour éviter injection SQL (htmlspecialchars pour éviter interprétation des carac spéciaux en html)
		$stmt = $connect -> prepare($query);
		if (!$stmt)
			displayErreur($page, 5);
		$stmt -> bind_param('sis', $login, $oeuvre, $contenu);
		if (!$stmt)
			displayErreur($page, 6);
		$contenu = htmlspecialchars($_POST['contenu'], ENT_QUOTES, "UTF-8");
		$stmt -> execute();
		if (!$stmt)
			displayErreur($page, 7);
		$stmt -> close();
		displayMessage($page, 5);
	}
		
	// Modifier commentaire
	function modifCommentaire($connect, $page, $login) {
		if (!array_key_exists('contenu', $_POST))
			displayErreur($page, 8);
		if (!array_key_exists('id', $_POST) || preg_match('/^[0-9]{1,10}$/', $_POST['id']) != 1)
			displayErreur($page, 9);
		$id = $_POST['id'];
		$contenu = htmlspecialchars($_POST['contenu'], ENT_QUOTES, "UTF-8");
		if ($login == 'admin')
			$query = "UPDATE commentaires SET contenu=? WHERE id=$id";
		else // Si utilisateur != admin sécurité supplémentaire => vérif login et id commentaire correspondent
			$query = "UPDATE commentaires SET contenu=? WHERE auteur_id='$login' AND id=$id";
		// Requête préparée pour éviter injection SQL (htmlspecialchars pour éviter interprétation des carac spéciaux en html)
		$stmt = $connect -> prepare($query);
		if (!$stmt)
			displayErreur($page, 10);
		$stmt -> bind_param('s', $contenu);
		if (!$stmt)
			displayErreur($page, 11);
		$stmt -> execute();
		if (!$stmt)
			displayErreur($page, 12);
		$stmt -> close();
		displayMessage($page, 1);
	}
	
	// Supprimer commentaire
	function suppCommentaire($connect, $page, $login) {
		if (!array_key_exists('id', $_POST) || preg_match('/^[0-9]{1,10}$/', $_POST['id']) != 1)
			displayErreur($page, 13);
		$id = $_POST['id'];
		if ($login == 'admin')
			$query = "DELETE FROM commentaires WHERE id=$id";
		else // Si utilisateur != admin sécurité supplémentaire => vérif login et id commentaire correspondent
			$query = "DELETE FROM commentaires WHERE auteur_id='$login' AND id=$id";
		$connect -> query($query);
		if (!$connect)
			displayErreur($page, 14);
		displayMessage($page, 6);
	}
