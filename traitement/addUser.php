<?php
	
	session_start();

	include_once('functions.php');
	include_once('info.php');
	
	// liste d'actions (renvoi à une fonction)
	$actions = array('creerUser', 'checkLogin');
	
	// Vérification $_POST et action
	if (!$_POST || !array_key_exists('action', $_POST) || !in_array($_POST['action'], $actions)) {
		echo 0;
		exit;
	}
	// Vérification $_POST['login']
	if (!array_key_exists('login', $_POST) || preg_match('/^[A-Z0-9_]{4,20}$/i', $_POST['login']) != 1)
		displayErreur($page, 2);
		
	// Connection BDD avec accès user et sessions
	if (!$connect = new mysqli($hostname, $DBLogin[4], $DBPass[4], $database)) {
		echo $connect -> error;
		exit;
	}
	if (!$connect -> set_charset('utf8')) {
        echo $connect -> error;
		exit;
    }
	
	// Renvoi vers fonction
	if ($_POST['action'] == 'creerUser')
		creerUser($connect);
	else
		checkLogin($connect);	
		
	// Vérifie si login dispo pour vérif ajax : 0 = false (pas dispo) 1 = true(dispo)
	function checkLogin($connect) {
		// verif $_POST et login
		if (!$_POST || !array_key_exists('login', $_POST) || preg_match('/^[a-z0-9_]{4,20}$/i', $_POST['login']) != 1) {
			echo 0;
			exit;
		}
		$login = $_POST['login'];
		$query = "SELECT * FROM user WHERE login='$login'";
		$result = $connect -> query($query);
		if (!$result || $result -> num_rows != 0) // login déjà existant
			echo 0;
		else // Login dispo
			echo 1;
	}
	
	// Crée l'utilisateur
	function creerUser($connect) {
		// Vérification variable session loc (page précédente) pour remplissage avec index.php si vide
		if (!array_key_exists('loc', $_SESSION) || $_SESSION['loc'] == '')
			$_SESSION['loc'] = "$_SERVER[HTTP_HOST]/pages/index.php";
		$page = "Location: $_SESSION[loc]";

		// Vérification $_POST['pass1'] et $_POST['pass2']
		if (!array_key_exists('pass1', $_POST) || !array_key_exists('pass2', $_POST) || preg_match('/^[A-Z0-9_]{4,20}$/i', $_POST['pass1']) != 1 || $_POST['pass1'] != $_POST['pass2'])
			displayErreur($page, 3);
		// Vérif existence nom, prenom, mail, informations dans $_POST
		if (!array_key_exists('nom', $_POST) || !array_key_exists('prenom', $_POST) || !array_key_exists('mail', $_POST) || !array_key_exists('informations', $_POST))
			displayErreur($page, 4);
		// Vérif conformité mail
		if (preg_match('/^[a-z0-9_\.-]+@[a-z0-9_\.-]+\.[a-z]{2,6}$/i', $_POST['mail']) != 1)
			displayErreur($page, 5);


		// Envoie de la requête d'insertion (htmlspecialchars pour éviter interprétation des carac spéciaux en html)
		$stmt = $connect -> prepare("INSERT INTO user (login,pass,nom,prenom,mail,informations) VALUES (?, ?, ?, ?, ?, ?)");
		if (!$stmt)
			displayErreur($page, 6);
		$stmt -> bind_param('ssssss', $login, $pass, $nom, $prenom, $mail, $informations);
		if (!$stmt)
			displayErreur($page, 7);
		$login = $_POST['login'];
		$pass = hash('sha256', $_POST['pass1']);
		$nom = htmlspecialchars($_POST['nom'], ENT_QUOTES, "UTF-8");
		$prenom = htmlspecialchars($_POST['prenom'], ENT_QUOTES, "UTF-8");
		$mail = $_POST['mail'];
		$informations = htmlspecialchars($_POST['informations'], ENT_QUOTES, "UTF-8");
		$stmt -> execute();
		if (!$stmt)
			displayErreur($page, 8);
		$stmt -> close();
		
		createSession($connect, $login); // functions.php
		
		displayMessage($page, 2);
	}
