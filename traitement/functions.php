<?php


	// Notification erreur
	function displayErreur($page, $err) {
		if (!strpos($page, '?'))
			$page = $page."?erreur=$err";
		else
			$page = $page."&erreur=$err";
		header($page);
		exit;
	}
	
	// Renvoi avec message
	function displayMessage($page, $msg) {
		if (!strpos($page, '?'))
			$page = $page."?message=$msg";
		else
			$page = $page."&message=$msg";
		header($page);
		exit;
	}
	
	// Récupérer adresse page courante dans $_SESSION['loc']
	function saveCurrentUrl() {
		$url = 'http://'.$_SERVER['HTTP_HOST'];
		$page = explode('?', $_SERVER['REQUEST_URI']);
		$url .= $page[0];
		// récupération paramètres $_GET sauf message & erreur
		if (count($page) > 1) {
			$page = explode('&', $page[1]);
			$len = count($page);
			// Parcours de chaque paramètre get
			for ($i=0; $i<$len; $i++) {
				// Ne garde pas les erreur ou message
				if (strpos($page[$i], 'erreur=') === false && strpos($page[$i], 'message=') === false) {
					if (strpos($page[$i], '?') === false)
						$url .= '?';
					else
						$url .= '&';
					$url .= $page[$i];
				}
			}
		}
		$_SESSION['loc']= $url;
	}
	
	// Vérif session
	function checkSession($connect) {
		// Vérification existence login et sid
		if (!array_key_exists('login', $_SESSION) || !array_key_exists('sid', $_SESSION))
			return false;
		$user = $_SESSION['login'];
		$sid = $_SESSION['sid'];
        // Vérification conformité login (4-20 carac alphanum ou _ ) et sid (sha256 -> 64 carac hexa)
        if (preg_match('/^[A-Z0-9_]{4,20}$/i', $user) != 1 || preg_match('/^[a-f0-9]{64}$/', $sid) != 1)
			return false;
		// Vérification conformité couple login/sid
		$query = "SELECT U.privilege, S.date FROM sessions S, user U WHERE S.user=U.login AND S.user='$user' AND S.id='$sid'";
		$result = $connect -> query($query);
		if (!$result || $result -> num_rows == 0)
			return false;
		// Si résultat, renvoi privilege et date (pour traitement dans connect.php
		return $result->fetch_assoc();
	}
		
	// Création de session
	function createSession($connect, $login) {
		// Efface ancienne session si existante
		$query = "DELETE FROM sessions WHERE user='$login'";
		$connect -> query($query);
		// Création nouvelle session
		$sid = hash('sha256', openssl_random_pseudo_bytes(32));
		$query = "INSERT INTO sessions(id,user) VALUES ('$sid', '$login')";
		if (!$connect -> query($query))
			return false;
		else {
			setcookie(session_name(), NULL, 60*60);
			$_SESSION['login'] = $login;
			$_SESSION['sid'] = $sid;
			return true;
		}
	}
	
	
	// Supprimer session
	function deleteSession($connect, $login) {
		setcookie(session_name(), NULL, -1);
		// Vérification concordance $login (vérifié avec sid) et $_SESSION['login']
		if ($login != $_SESSION['login'])
			return false;
		$_SESSION = array();
		$query = "DELETE FROM sessions WHERE user='$login'";
		if (!$connect -> query($query))
			return false;
		else
			return true;
	}
	
	// Vérification login / pass
	function verifPass($connect) {
		// Vérification $_POST
		if (!$_POST)
			return false;
		// Vérification $_POST['login']
		if (!array_key_exists('login', $_POST) || preg_match('/^[A-Z0-9_]{4,20}$/i', $_POST['login']) != 1)
			return false;
		// Vérif $_POST['pass']
		if (!array_key_exists('pass', $_POST) || preg_match('/^[A-Z0-9_]{4,20}$/i', $_POST['pass']) != 1)
			return false;
		// Envoi de requête select pour vérifier login/pass
		$pass = hash('sha256', $_POST['pass']);
		$login = $_POST['login'];
		$query = "SELECT * FROM user WHERE pass='$pass' AND login='$login'";
		$result = $connect -> query($query);
		if ($result && $result -> num_rows == 1) // Si 1 ligne retournée, login/pass valide
			return true;
		else
			return false;
	}
	
	// Envoi de mail
	function sendMail($to, $sujet, $contenu) {
		// En-têtes: MIME, contenu, charset, FROM
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
		$headers .= 'From: forumdesartistes@contact.com' . "\r\n";
		mail($to, $sujet, $contenu, $headers);
	}
