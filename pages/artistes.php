<?php

    include('../traitement/connect.php');
    include('head.php');
    // css spécifique à la page
    echo "	<link rel='stylesheet' type='text/css' href='../css/artistes.css' />";
    include('menuHaut.php');
    
    // Récupérer adresse page courante dans $_SESSION['loc']
	saveCurrentUrl();
    
    $limit = 30; // Nb d'artistes par page
    // page à afficher si définie dans $_GET
	if ($_GET && array_key_exists('page', $_GET) && preg_match('/^[0-9]{1,3}$/', $_GET['page']) == 1)
		$page = intval($_GET['page']);
	else
		$page = 0;
	
	$offset = $page*$limit;
	
	// Nombre total d'artistes
	$query = "SELECT count(*) as nb FROM user WHERE privilege=1";
	$result = $connect -> query($query);
	if (!$result || $result -> num_rows == 0) {
		echo $connect -> error; 
		exit;
	} else {
		$line = $result -> fetch_assoc();
		$totalArtistes = $line['nb']; // total nb d'artistes
		$nbPages = ceil($totalArtistes/$limit); // nb de pages correspondant
		if ($totalArtistes < $offset) // Valeur page incorrecte => affichage à partir de 0
			$offset = 0;
	}

	$error = false;
	$artistes = array();
	
	$query = "SELECT login FROM user WHERE privilege=1 ORDER BY login LIMIT $limit OFFSET $offset";
	$result = $connect -> query($query);
	if (!$result || $result -> num_rows == 0)
		$error = true;
	else {
		$nbArtistes = $result -> num_rows;
		while ($line = $result -> fetch_assoc())
			array_push($artistes, $line['login']);
	}


	// Affichage bloc principal
    echo "  <div id='mainBloc'>
                <div id='pageTitre'>Les Artistes</div>
                <div id='listeArtistes'>";
	if ($error) 	// Si erreur ou pas d'artistes
		echo "		<div id='listeError'>Erreur d'affichage</div>
				</div>
			</div>";
	else {
		// Affichage première colonne
		echo "		<div class='listeColonne'>";
		if ($nbArtistes < 10)
			$l = $nbArtistes;
		else
			$l = 10;
		for ($i=0; $i<$l; $i++) {
			$login = $artistes[$i];
			echo "		<a href='./compte.php?user=$login'>$login</a>";
		}
		echo "		</div>";
		// Affichage deuxième colonne
		if ($nbArtistes > 10) {
			echo "	<div class='listeColonne'>";
			if ($nbArtistes < 20)
				$l = $nbArtistes;
			else
				$l = 20;
			for ($i=10; $i<$l; $i++) {
				$login = $artistes[$i];
				echo "	<a href='./compte.php?user=$login'>$login</a>";
			}
			echo "	</div>";
			// Affichage troisième colonne
			if ($nbArtistes > 20) {
				echo "<div class='listeColonne'>";
				if ($nbArtistes < 30)
					$l = $nbArtistes;
				else
					$l = 30;
				for ($i=20; $i<$l; $i++) {
					$login = $artistes[$i];
					echo "	<a href='./compte.php?user=$login'>$login</a>";
				}
				echo "</div>";
			}
		}
		echo "	</div>";
		// affichage liens pages
		if ($nbPages > 1) {
			echo "<div id='liensPages'>Pages :";
			for ($i=0; $i<$nbPages; $i++) {
				$j = $i+1;
				if ($page == $i)
					echo "<a href='./artistes.php?page=$i' id='selectedPage'>$j</a>";
				else
					echo "<a href='./artistes.php?page=$i'>$j</a>";
			}
			echo "</div>";
		}
		echo "</div>";
	}
	
	// pied de page
	include('footer.php');
