<?php

    include('../traitement/connect.php');
    // Renvoi si user != artiste
    if ($privilege != 1 || $login == '') {
		header('Location: ./index.php');
		exit;
	}
    include('head.php');
    // css spécifique à la page
	echo "	<link rel='stylesheet' type='text/css' href='../css/gererOeuvres.css' />
			<script src='../js/gererOeuvres.js'></script>";
    include('menuHaut.php');
    
    // Récupérer adresse page courante dans $_SESSION['loc']
	saveCurrentUrl();
	
	// Récupération informations oeuvres (pas les images)
	$query = "	SELECT 	O.id AS id, 
                        O.titre AS titre, 
                        I.repertoire AS repertoire, 
                        I.miniature AS fichier, 
                        DATE_FORMAT(O.date, '\le %d/%m/%Y \à %H:%i') AS date,
                        (SELECT COUNT(*) FROM notes WHERE oeuvre_id=O.id) AS nbNotes,
                        (SELECT AVG(valeur) FROM notes WHERE oeuvre_id=O.id) AS moy,
                        (SELECT COUNT(*) FROM commentaires WHERE O.id=oeuvre_id) AS nbComment 
				FROM oeuvres O, images I
				WHERE O.id=I.oeuvre AND I.position=0 AND artiste='$login'
				ORDER BY O.id DESC";
	$result = $connect -> query($query);
	if (!$result) {
		echo $connect -> error;
		return false;
	} else {
		$oeuvres = array();
		$nbOeuvres = $result -> num_rows;
		while ($line = $result -> fetch_assoc())
			array_push($oeuvres, $line);
	}

	// Affichage bloc principal
    echo "  <div id='mainBloc'>
                <div id='pageTitre'>Espace de gestion de vos oeuvres</div>";
	// Affichage menu tri et filtres
	echo "		
				<a id='ajouterOeuvre' href='./addOeuvre.php' class='bouton'>Ajouter une oeuvre</a>
				<div id='listeOeuvres'>";
	if ($nbOeuvres == 0) // Pas d'oeuvres à afficher
		echo "		<div id='listeError'>Pas d'oeuvres enregistrées</div>";
	else {
		for ($i=0; $i<$nbOeuvres; $i++) {
			$lienImage = "../images/".$oeuvres[$i]['repertoire']."/".$oeuvres[$i]['fichier']."?time=".time();
			$id = $oeuvres[$i]['id'];
			$titre = $oeuvres[$i]['titre'];
			$date = $oeuvres[$i]['date'];
			$moy = round($oeuvres[$i]['moy'],1);
			$nbNotes = $oeuvres[$i]['nbNotes'];
			$nbComment = $oeuvres[$i]['nbComment'];
			echo "	<div class='gererOeuvreBloc'>
						<div class='gererBlocImg'><img src='$lienImage' /></div>
						<div class='gererBlocLigne'><span class='oeuvreTitre'>$titre</span><span class='oeuvreDate'>$date</span></div>
						<div class='gererBlocLigne'>Note actuelle: <strong>$moy</strong>/10 <span class='oeuvreVotes'>($nbNotes votes)</span></div>
						<div class='gererBlocLigne'><span class='comment'>$nbComment commentaires</div>
						<div class='gererBlocLigne'>
							<a href='./voirOeuvre.php?id=$id' class='bouton'>Voir l'oeuvre</a>
							<a href='./modifOeuvre.php?id=$id' class='bouton'>Modifier</a>
							<form method='POST' action='../traitement/oeuvres.php' onsubmit='return confirmSupp(this);'>
								<input type='hidden' name='id' value='$id'/>
								<input type='hidden' name='artiste' value='$login'/>
								<input type='hidden' name='action' value='supprimer'/>
								<input type='submit' value='Supprimer'/>
							</form>
						</div>
					</div>";
		}
	}
	echo "		</div>
			</div>";
	
	// pied de page
	include('footer.php');
