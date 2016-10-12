<?php

    session_start();
    
    // Vérification variable session loc (page précédente) pour remplissage avec index.php si vide
	if (!array_key_exists('loc', $_SESSION) || $_SESSION['loc'] == '')
		$_SESSION['loc'] = "$_SERVER[HTTP_HOST]/pages/index.php";

    include_once('functions.php');
    include_once('info.php');

    // Privilège/login par défaut (non-connecté)
    $privilege = 3;
    $login = '';
    
    // Connexion à la BDD avec compte check_user
	if (!$connect = new mysqli($hostname, $DBLogin[4], $DBPass[4], $database)) {
		echo $connect -> error;
		exit;
	}
	// Vérification session courante (functions.php)
    $line = checkSession($connect);
    if ($line) {
		$user = $_SESSION['login'];
		/* Si date d'expiration du cookie dépassé (1h), efface la ligne correspondante dans la table + données
		 * session courante + cookie et averti l'utilisateur */
		if (strtotime($line['date']) < time() - 3600) {
			$page = "Location: $_SESSION[loc]";
			deleteSession($connect, $user); // functions.php
			displayErreur($page, 200); // functions.php
		} else {
			// Réinitialisation SID et id session => meilleur sécurité session
			$sid = hash('sha256', openssl_random_pseudo_bytes(32));
			$query = "UPDATE sessions SET id='$sid' WHERE user='$user'";
			if (!$connect -> query($query)) {
				echo $connect -> error;
				exit;
			} else {
				$privilege = $line['privilege'];
				$login = $user;
				$_SESSION['sid'] = $sid;
				session_regenerate_id(true);
			}
		}
	}
	$connect -> close();
    
    
    // Connexion à la base de donnée avec niveau de privilège correspondant à l'utilisateur
    $loginDB = $DBLogin[$privilege];
    $passDB = $DBPass[$privilege];
    if (!$connect = new mysqli($hostname, $loginDB, $passDB, $database)) {
        $error = $connect -> error;
        echo $error;
    }
    if (!$connect -> set_charset('utf8')) {
        $error = $connect -> error;
        echo $error;
    }
