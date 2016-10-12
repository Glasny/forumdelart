<?php

    include('../traitement/connect.php');
    // Renvoi si user != admin
    if ($privilege != 0 || $login == '') {
		header('Location: ./index.php');
		exit;
	}
    include('head.php');
    // css spécifique à la page
	echo "	<link rel='stylesheet' type='text/css' href='../css/gererUser.css' />
			<script src='../js/gererUser.js'></script>";
    include('menuHaut.php');
    
    // Récupérer adresse page courante dans $_SESSION['loc']
	saveCurrentUrl();
	
	$limit = 30; // Nb d'user par page
    // page à afficher si définie dans $_GET
	if ($_GET && array_key_exists('page', $_GET) && preg_match('/^[0-9]{1,3}$/', $_GET['page']) == 1)
		$page = intval($_GET['page']);
	else
		$page = 0;
	
	$offset = $page*$limit;
	
	// Nombre total d'user
	$query = "SELECT count(*) as nb FROM user WHERE login<>'admin'";
	$result = $connect -> query($query);
	if (!$result || $result -> num_rows == 0) {
		echo $connect -> error; 
		exit;
	} else {
		$line = $result -> fetch_assoc();
		$totalUser = $line['nb']; // total nb d'user
		$nbPages = ceil($totalUser/$limit); // nb de pages correspondant
		if ($totalUser < $offset) // Valeur page incorrecte => affichage à partir de 0
			$offset = 0;
	}
	
	
	// Récupération login, privilège, date d'inscription de tous les utilisateurs
	$query = "	SELECT 	login, privilege, DATE_FORMAT(date_creation, '\le %d/%m/%Y \à %H:%i') AS date 
				FROM user WHERE login<>'admin' ORDER BY login LIMIT $limit OFFSET $offset";
	$result = $connect -> query($query);
	if (!$result) {
		echo $connect -> error;
		return false;
	} else {
		$user = array();
		$nbUser = $result -> num_rows;
		while ($line = $result -> fetch_assoc())
			array_push($user, $line);
	}

	// Affichage bloc principal
    echo "  <div id='mainBloc'>
                <div id='pageTitre'>Espace de gestion des utilisateurs</div>
				<div id='listeUsers'>";
	if ($nbUser == 0) // Pas d'user à afficher
		echo "		<div id='listeError'>Pas d'utilisateurs</div>
				</div>";
	else {
		for ($i=0; $i<$nbUser; $i++) {
			$login = $user[$i]['login'];
			$privilege = $user[$i]['privilege'];
			$date = $user[$i]['date'];
			echo "	<div class='userLine'>
						<a href='./compte.php?user=$login'>$login</a>
						<form method='POST' action='../traitement/editCompte.php' onsubmit='return confirmSupp(this);'>
							<input type='hidden' name='action' value='suppUser'/>
							<input type='hidden' name='user' value='$login'/>
							<input type='submit' value='Supprimer'/>
						</form>
						<form method='POST' action='../traitement/editCompte.php'>
							<select name='privilege' id='selPriv'>";
			if ($privilege == 2)
				echo "			<option value='2' selected>Visiteur</option>
								<option value='1'>Artiste</option>";
			else
				echo "			<option value='2'>Visiteur</option>
								<option value='1' selected>Artiste</option>";
			echo "			</select>
							<input type='hidden' name='action' value='modifPrivilege'/>
							<input type='hidden' name='user' value='$login'/>
							<input type='submit' value='Valider'/>
						</form>
						<span class='userDate'>Crée $date</span>
					</div>";
		}
		echo "		</div>";
		// affichage liens pages
		if ($nbPages > 1) {
			echo "<div id='liensPages'>Pages :";
			for ($i=0; $i<$nbPages; $i++) {
				$j = $i+1;
				if ($page == $i)
					echo "<a href='./gererUser.php?page=$i' id='selectedPage'>$j</a>";
				else
					echo "<a href='./gererUser.php?page=$i'>$j</a>";
			}
			echo "</div>";
		}
	}
	
	
	echo "	</div>";
	
	// pied de page
	include('footer.php');
