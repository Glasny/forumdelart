<?php

    session_start();

    include_once('functions.php');
    include_once('info.php');
    
    // Vérification variable session loc (page précédente) pour remplissage avec index.php si vide
	if (!array_key_exists('loc', $_SESSION) || $_SESSION['loc'] == '')
		$_SESSION['loc'] = "$_SERVER[HTTP_HOST]/pages/index.php";

	$page = "Location: $_SESSION[loc]";

	// Vérification action (post pour logIn, get pour logOut)
	if ($_POST && array_key_exists('action', $_POST) && $_POST['action'] == 'logIn')
		$action = $_POST['action'];
	elseif ($_GET && array_key_exists('action', $_GET) && $_GET['action'] == 'logOut')
		$action = $_GET['action'];
	else
		displayErreur($page, 1);

	// Connexion à la BDD avec compte check_user
	if (!$connect = new mysqli($hostname, $DBLogin[4], $DBPass[4], $database)) {
		echo $connect -> error;
		exit;
	}
	
	if ($action == 'logIn')
		logIn($connect, $page);
	else
		logOut($connect, $page);
	
	// Connexion
	function logIn($connect, $page) {
		if (verifPass($connect)) { // functions.php
			createSession($connect, $_POST['login']); // functions.php
			header($page);
		} else // Login / Pass non valide
			displayErreur($page, 'login');
	}
	
	// Déconnexion
	function logOut($connect, $page) {
		if (checkSession($connect)) // functions.php
	        deleteSession($connect, $_SESSION['login']); // functions.php
        header($page);
	}
