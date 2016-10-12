<?php
    include_once('../traitement/connect.php');
    include_once('head.php');
    include_once('menuHaut.php');
    
	// Récupérer adresse page courante dans $_SESSION['loc']
	saveCurrentUrl();
    
	// Oeuvres les plus récentes
    $query = "	SELECT 	O.id AS id, 
                        O.titre AS titre, 
                        I.repertoire AS repertoire, 
                        I.miniature AS fichier, 
                        DATE_FORMAT(O.date, '\le %d/%m/%Y \à %H:%i') AS date,
                        O.artiste AS artiste, 
                        (SELECT COUNT(*) FROM notes WHERE oeuvre_id=O.id) AS nbNotes,
                        (SELECT AVG(valeur) FROM notes WHERE oeuvre_id=O.id) AS moy,
                        (SELECT COUNT(*) FROM commentaires WHERE O.id=oeuvre_id) AS nbComment 
				FROM oeuvres O, images I
				WHERE O.id=I.oeuvre AND I.position=0
				ORDER BY O.id DESC LIMIT 5 OFFSET 0";
    $result = $connect -> query($query);
    if (!$result) {
        $error = $connect -> error;
        echo $error;
    }
    $oeuvresRecentes = array();
    if ($result -> num_rows > 0)
        while ($line = $result -> fetch_assoc())
            array_push($oeuvresRecentes, $line);
    
    // Oeuvres les mieux notées
    $query = "	SELECT 	O.id AS id, 
                        O.titre AS titre, 
                        I.repertoire AS repertoire, 
                        I.miniature AS fichier, 
                        DATE_FORMAT(O.date, '\le %d/%m/%Y \à %H:%i') AS date,
                        O.artiste AS artiste, 
                        (SELECT COUNT(*) FROM notes WHERE oeuvre_id=O.id) AS nbNotes,
                        (SELECT AVG(valeur) FROM notes WHERE oeuvre_id=O.id) AS moy,
                        (SELECT COUNT(*) FROM commentaires WHERE O.id=oeuvre_id) AS nbComment 
				FROM oeuvres O, images I
				WHERE O.id=I.oeuvre AND I.position=0
				ORDER BY moy DESC LIMIT 5 OFFSET 0";
    $result = $connect -> query($query);
    if (!$result) {
        $error = $connect -> error;
        echo $error;
    }
    $oeuvresNotes = array();
    if ($result -> num_rows > 0)
        while ($line = $result -> fetch_assoc())
            array_push($oeuvresNotes, $line);
    
    // Oeuvres les plus commentées
    $query = "	SELECT 	O.id AS id, 
                        O.titre AS titre, 
                        I.repertoire AS repertoire, 
                        I.miniature AS fichier, 
                        DATE_FORMAT(O.date, '\le %d/%m/%Y \à %H:%i') AS date,
                        O.artiste AS artiste, 
                        (SELECT COUNT(*) FROM notes WHERE oeuvre_id=O.id) AS nbNotes,
                        (SELECT AVG(valeur) FROM notes WHERE oeuvre_id=O.id) AS moy,
                        (SELECT COUNT(*) FROM commentaires WHERE O.id=oeuvre_id) AS nbComment 
				FROM oeuvres O, images I
				WHERE O.id=I.oeuvre AND I.position=0
				ORDER BY nbComment DESC LIMIT 5 OFFSET 0";
    
    $result = $connect -> query($query);
    if (!$result) {
        $error = $connect -> error;
        echo $error;
    }
    $oeuvresCommentaires = array();
    if ($result -> num_rows > 0)
        while ($line = $result -> fetch_assoc())
            array_push($oeuvresCommentaires, $line);


  
    // Affichage bloc principal
    echo "  <div id='mainBloc'>
                <div id='indexPresentation'>
                    Bienvenue au Forum de l'Art.<br/>
                    L'endroit où l'on peut apprécier ou partager des oeuvres d'art.
                </div>
                <div id='bloc1'>
					<div class='blocTitre'>Oeuvres les plus récentes</div>
					<div class='indexDisplayBloc'>";
    
	// Affichage Oeuvres récentes
	$len = count($oeuvresRecentes);
	for ($i=0;$i<$len;$i++) {
		$lienImage = "../images/".$oeuvresRecentes[$i]['repertoire']."/".$oeuvresRecentes[$i]['fichier']."?time=".time();
		$id = $oeuvresRecentes[$i]['id'];
		$artiste = $oeuvresRecentes[$i]['artiste'];
		$titre = $oeuvresRecentes[$i]['titre'];
		$date = $oeuvresRecentes[$i]['date'];
		$moy = round($oeuvresRecentes[$i]['moy'],1);
		$nbNotes = $oeuvresRecentes[$i]['nbNotes'];
		$nbComment = $oeuvresRecentes[$i]['nbComment'];
		echo "      	<a class='oeuvreBloc' href='./voirOeuvre.php?id=$id'>
							<div class='oeuvreBlocImage'><img src='$lienImage' alt='$titre' /></div>
							<div class='oeuvreBlocTitre'>$titre<span class='oeuvreBlocArtiste'> ($artiste)</span></div>
							<div class='oeuvreBlocInfo'>$date<br/>
							$moy/10 ($nbNotes votes)<br/>
							($nbComment commentaires)</div>
						</a>";
	}
	echo "   		</div>
				</div>
				<div id='bloc2'>
					<div class='blocTitre'>Oeuvres les plus populaires</div>
					<div class='indexDisplayBloc'>";
	
    // Affichage oeuvres les mieux notées
	$len = count($oeuvresNotes);
	for ($i=0;$i<$len;$i++) {
		$lienImage = "../images/".$oeuvresNotes[$i]['repertoire']."/".$oeuvresNotes[$i]['fichier']."?time=".time();
		$id = $oeuvresNotes[$i]['id'];
		$artiste = $oeuvresNotes[$i]['artiste'];
		$titre = $oeuvresNotes[$i]['titre'];
		$date = $oeuvresNotes[$i]['date'];
		$moy = round($oeuvresNotes[$i]['moy'],1);
		$nbNotes = $oeuvresNotes[$i]['nbNotes'];
		$nbComment = $oeuvresNotes[$i]['nbComment'];
		echo "      	<a class='oeuvreBloc' href='./voirOeuvre.php?id=$id'>
							<div class='oeuvreBlocImage'><img src='$lienImage' alt='$titre' /></div>
							<div class='oeuvreBlocTitre'>$titre<span class='oeuvreBlocArtiste'> ($artiste)</span></div>
							<div class='oeuvreBlocInfo'>$date<br/>
							$moy/10 ($nbNotes votes)<br/>
							($nbComment commentaires)</div>
						</a>";
	}
	echo "     		</div>
				</div>
				<div id='bloc3'>
					<div class='blocTitre'>Oeuvres les plus commentées</div>
					<div class='indexDisplayBloc'>";
	
    // Affichage oeuvres les plus commentées
	$len = count($oeuvresCommentaires);
	for ($i=0;$i<$len;$i++) {
		$lienImage = "../images/".$oeuvresCommentaires[$i]['repertoire']."/".$oeuvresCommentaires[$i]['fichier']."?time=".time();
		$id = $oeuvresCommentaires[$i]['id'];
		$artiste = $oeuvresCommentaires[$i]['artiste'];
		$titre = $oeuvresCommentaires[$i]['titre'];
		$date = $oeuvresCommentaires[$i]['date'];
		$moy = round($oeuvresCommentaires[$i]['moy'],1);
		$nbNotes = $oeuvresCommentaires[$i]['nbNotes'];
		$nbComment = $oeuvresCommentaires[$i]['nbComment'];
		echo "      	<a class='oeuvreBloc' href='./voirOeuvre.php?id=$id'>
							<div class='oeuvreBlocImage'><img src='$lienImage' alt='$titre' /></div>
							<div class='oeuvreBlocTitre'>$titre<span class='oeuvreBlocArtiste'> ($artiste)</span></div>
							<div class='oeuvreBlocInfo'>$date<br/>
							$moy/10 ($nbNotes votes)<br/>
							($nbComment commentaires)</div>
						</a>";
	}
    echo "     		</div>
				</div>
            </div>";
            
    $connect -> close();

	// Affichage pied de page
    include_once('footer.php');
