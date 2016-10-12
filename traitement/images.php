<?php

	// ajoutImages : sert pour requête d'ajout et de modif d'oeuvre
	// suppImages : sert pour requête de suppresion et de modif d'oeuvre ou de suppression d'artiste


	// Ajouter images ($images => array images/miniatures, $id = id de l'oeuvre, $imgPos = array de positions d'image disponibles => Pour compatibilité modification d'oeuvre)
	function ajoutImages($connect, $page, $images, $id, $imgPos) {
		$dirNum = 1;
        $nbFichier = 0;
        $len = count($images);
        foreach ($images as $k => $v) { // Itération des fichiers img et min
			// Sélection du répertoire d'enregistrement des images (pas plus de 100 images par répertoire)
			while (true) {
				$dir = "../images/img$dirNum";
				// Première itération de la boucle for, parcours des répertoires existant jusqu'à trouver la première place disponible
				if ($nbFichier == 0) {
					if (is_dir($dir)) {
						// Si répertoire existe, comptage nombre de fichiers
						$handler = opendir($dir);
						while($entry = readdir($handler)) {
							if(is_file($dir.'/'.$entry))
								$nbFichier++;
						}
						closedir($handler);
					} else {
						// Si répertoire n'existe pas, création du nouveau répertoire et sortie de la boucle
						mkdir($dir);
						// Création du index.php (redirection vers répertoire pages)
						$handler = fopen($dir.'/index.php', 'w');
						fwrite($handler, '<?php  header("Location: ../../pages/index.php");');
						fclose($handler);
						break;
					}
				}
				if ($nbFichier >= 100) {
					// Si taille répertoire >= 100 => Rép plein, vérification du suivant
					$nbFichier = 0;
					$dirNum++;
				} else // Sinon places disponibles dans le répertoire => sortie de la boucle
					break;
			}
			// +2 fichiers pour prendre en compte ceux qui vont être enregistré dans le répertoire
			$nbFichier += 2;
			// Vérification emplacements disponibles dans le répertoire (important car non prévisible à cause des suppressions d'images)
			for ($j=1; $j<=50; $j++) {
				if(!is_file($dir."/img$j.jpeg"))
					break;
			}
			// On enregistre img et min sous img$j.jpeg et min$j.jpeg dans le rep sélectionné
			$loc = $dir.'/img'.$j.'.jpeg';
			createImgFile($loc, $v['img']);
			$loc = $dir.'/min'.$j.'.jpeg';
	        createImgFile($loc, $v['min']);
	        // Récupération de la position de l'image pour l'oeuvre (en fonction des positions dispo si modification d'oeuvre)
	        $pos = $imgPos[$k];
	        // Enregistrement des images en BDD
	        $query = "INSERT INTO images(oeuvre, fichier, miniature, repertoire, position) VALUES ($id, 'img$j.jpeg', 'min$j.jpeg', 'img$dirNum', $pos)";
	        $connect -> query($query);
	        if (!$connect)
				displayErreur($page, 30);
		}
	}
	
	// Création des fichiers image
	function createImgFile($loc, $img) {
		$img = str_replace('data:image/jpeg;base64,', '', $img);
		$img = base64_decode($img);
        $handler = fopen($loc, 'w');
        fwrite($handler, $img);
        fclose($handler);
    }
    
    // Suppresion des images ($id images séparées par , )
    function suppImages($connect, $page, $id) {
		$query = "SELECT fichier, miniature, repertoire FROM images WHERE id IN ($id)";
		$result = $connect -> query($query);
		if (!$result || $result -> num_rows == 0)
			displayErreur($page, 31);
		// Suppression des fichiers correspondants
		while ($line = $result -> fetch_assoc()) {
			$loc = '../images/'.$line['repertoire'].'/'.$line['fichier'];
			if (file_exists($loc))
				unlink($loc);
			$loc = '../images/'.$line['repertoire'].'/'.$line['miniature'];
			if (file_exists($loc))
				unlink($loc);
		}
		// Suppression de la base
		$query = "DELETE FROM images WHERE id IN ($id)";
		$connect -> query($query);
		if (!$connect)
			displayErreur($page, 32);
	}
