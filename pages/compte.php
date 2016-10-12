<?php

	include_once('../traitement/connect.php');
	include_once('head.php');
	// css et js spécifiques à la page
	echo "	<link rel='stylesheet' type='text/css' href='../css/compte.css' />
			<script src='../js/compte.js'></script>";
    include_once('menuHaut.php');
    
    // Récupérer adresse page courante dans $_SESSION['loc']
	saveCurrentUrl();
    
	// Vérification utilisateur connecté et validité format login
	if (array_key_exists('user', $_GET) && preg_match('/^[A-Z0-9_]{4,20}$/i', $_GET['user']) == 1) {
		// Récupération informations du compte user
		$pageLogin = $_GET['user'];		
		$query = "SELECT nom, prenom, informations, mail, privilege, DATE_FORMAT(date_creation, '%d/%m/%Y') AS date_creation 
					FROM user WHERE login='$pageLogin'";
		$result = $connect -> query($query);
		if ($result && $result -> num_rows == 1) {
			$compteUser = $result -> fetch_assoc();
			$pageNom = $compteUser['nom'];
			$pagePrenom = $compteUser['prenom'];
			$pageDescri = $compteUser['informations'];
			$pageDescri2 = nl2br($pageDescri);
			$pageMail = $compteUser['mail'];
			$privilege = $compteUser['privilege'];
			$pageDate = $compteUser['date_creation'];
			// Récupération nb d'oeuvres du user si applicable (création d'un lien le cas échéant)
			$query = "SELECT count(*) AS nb FROM oeuvres WHERE artiste='$pageLogin'";
			$result = $connect -> query($query);
			if ($result && $result -> num_rows == 1) {
				$nbOeuvres = $result -> fetch_assoc();
				$nbOeuvres = $nbOeuvres['nb'];
			} else
				$nbOeuvres = 0;
			// Récupération commentaires du user
			$comment = array();
			$query = "SELECT oeuvre_id, contenu, DATE_FORMAT(C.date, '\le %d/%m/%Y \à %H:%i') AS date, titre 
			FROM commentaires C, oeuvres O WHERE O.id=oeuvre_id AND auteur_id='$pageLogin' ORDER BY date DESC";
			$result = $connect -> query($query);
			if ($result && $result -> num_rows > 0) {
				while ($line = $result -> fetch_assoc())
					array_push($comment, $line);
			}
			// Récupération suivi artistes
			if ($login == $pageLogin) {
				$suivi = array();
				$query = "SELECT artiste_id FROM suivi WHERE membre_id='$login'";
				$result = $connect -> query($query);
				if ($result && $result -> num_rows > 0)
					while ($line = $result -> fetch_assoc())
						array_push($suivi, $line['artiste_id']);
			} elseif ($privilege == 1) { // Visite de la page d'un artiste : vérification suivi
				$query = "SELECT * FROM suivi WHERE artiste_id='$pageLogin' AND membre_id='$login'";
				$result = $connect -> query($query);
				if ($result && $result -> num_rows == 1) // L'artiste est suivi par le visiteur
					$isSuivi = true;
				else
					$isSuivi = false;
			}
			
			
			// Affichage tableau de bord -> liens sur compte.js
		    echo "  <div id='mainBloc'>
		                <div id='leftColumn'>
							<div id='leftColHead'>
								<div id='leftColTitre'>$pageLogin</div>
								<div id='leftColDate'>Compte crée le $pageDate</div>
							</div>
							<div id='leftColMain'>
								<div id='leftColInfo' class='bouton'>Informations</div>
								<div id='leftColComment' class='bouton'>Commentaires</div>";
			if ($login == $pageLogin)
				echo "			<div id='leftColSuivi' class='bouton'>Artistes Suivis</div>";
			// Affichage oeuvres de l'user (si existent) -> Renvoi sur page oeuvres.php
			if ($nbOeuvres > 0)
				echo "			<a id='leftColOeuvres' class='bouton' href='./listeOeuvres.php?artiste=$pageLogin'>Oeuvres ($nbOeuvres)</a>";
			echo "			</div>
						</div>";
			// Affichage panneau central informations (changement de page sur compte.js)
			echo "		<div id='centerColumn'>
							<div id='centerColInfo'>
								<div class='formTitre'>Informations générales</div>
								<div class='formLine'>Nom : <span class='b'>$pageNom</span></div>
								<div class='formLine'>Prénom : <span class='b'>$pagePrenom</span></div>
								<div class='formLine'>Mail : <span class='b'>$pageMail</span></div>
								<div class='formLine'>Description :</div>";
			if ($pageDescri != '')
				echo "			<div id='centerColDescri'>$pageDescri2</div>";
			else
				echo "			<div id='centerColDescri'>Pas d'informations.</div>";
			// Si utilisateur regarde son propre compte, affichage menu édition des informations
			if ($login == $pageLogin)
				echo "			<button id='editInfo' class='centerBouton bouton'>Éditer informations</button>
								<button id='editPass' class='centerBouton bouton'>Changer de mot de passe</button>";
			elseif ($privilege == 1 && $login != '') { // Visiteur connecté sur page artiste
				if (!$isSuivi) // Artiste non suivi par visiteur
					echo "		<div id='suiviBloc'>
									<div id='suiviBlocTitre'>Souhaitez-vous être averti lorsque cet artiste publie une oeuvre ?</div>
									<form method='POST' action='../traitement/suivi.php'>
										<input type='hidden' name='artiste' value='$pageLogin' />
										<input type='hidden' name='action' value='ajouter' />
										Cliquez ici pour suivre cet artiste : <input type='submit' value='Suivre' class='centerBouton bouton' />
									</form>
								</div>";
				else // Artiste suivi par le visiteur
					echo "		<div id='suiviBloc'>
									<div id='suiviBlocTitre'>Vous suivez cet artiste et serez prévenu lorsque celui-ci publie une oeuvre.</div>
									<form method='POST' action='../traitement/suivi.php'>
										<input type='hidden' name='artiste' value='$pageLogin' />
										<input type='hidden' name='action' value='annuler' />
										Pour vous désabonner : <input type='submit' value='Ne plus suivre' class='centerBouton bouton' />
									</form>
								</div>";
			}
			echo "			</div>";
			// Affichage panneau commentaires
			echo "			<div id='centerColComment'>
								<div class='formTitre'>Commentaires de $pageLogin :</div>
								<div class='centerColAffichage'>";
			$len = count($comment);
			if ($len > 0) {
				for ($i=0; $i<$len; $i++) {
					$idOeuvre = $comment[$i]['oeuvre_id'];
					$titreOeuvre = $comment[$i]['titre'];
					$date = $comment[$i]['date'];
					$contenu = $comment[$i]['contenu'];
					// Si commentaire trop long, on ne prend que le début
					if (strlen($contenu) > 76)
						$contenu = substr($comment[$i]['contenu'], 0, 70).'  [...]';
					echo "			<div class='centerCommentLine'>
										<div>
											<a href='./voirOeuvre.php?id=$idOeuvre' class='centerCommentLien'>$titreOeuvre</a>
											<span class='commentDate'>Écrit le : $date</span>
										</div>
										<div class='centerCommentContenu'>$contenu</div>
									</div>";
				}
			} else
				echo "				<div class='centerColMessage'>Vous n'avez écrit aucun commentaire</div>";
			echo "				</div>
							</div>";
			/* Si connecté sur son propre compte : pages suivi, édition infos et pass (vérifications formulaires sur compte.js) */
			if ($login == $pageLogin) {
				echo "		<div id='centerColEditInfo'>
								<form method='POST' action='../traitement/editCompte.php' onsubmit='return confirmModifInfo(this);'>
									<div class='formTitre'>Modifier vos informations</div>
									<div class='formLine'><label for='nom'>Nom :</label><input type='text' name='nom' id='inputNom' value='$pageNom'/></div>
									<div class='formLine'><label for='prenom'>Prénom :</label><input type='text' name='prenom' id='inputPrenom' value='$pagePrenom'/></div>
									<div class='formLine'><label for='mail'>Mail :</label><input type='text' name='mail' id='inputMail' value='$pageMail' required /></div>
									<div class='formLine'><label for='informations' class='textareaLabel'>À propos de vous :</label><textarea name='informations' id='inputInformations'>$pageDescri</textarea></div>
									<input type='hidden' name='action' value='editInfo'/>
									<div class='formBouton'>
										<span class='centerBouton cancelEdit bouton'>Annuler</span>
										<input class='centerBouton' type='submit' value='Valider' />
									</div>
									<div id='editInfoErrorBloc'></div>
								</form>
							</div>
							<div id='centerColEditPass'>
								<form method='POST' action='../traitement/editCompte.php' onsubmit='return confirmModifPass(this);'>
									<div class='formTitre'>Modifier votre mot de passe</div>
									<div class='formLine'><label for='pass'>Mot de passe actuel :</label><input type='password' id='oldPass' name='pass' required /></div>
									<div class='formLine'><label for='new1'>Nouveau mot de passe :</label><input type='password' id='newPass1' name='new1' required /></div>
									<div class='formLine'><label for='new2'>Confirmer mot de passe :</label><input type='password' id='newPass2' name='new2' required /></div>
									<input type='hidden' name='action' value='editPass'/>
									<input type='hidden' name='login' value='$pageLogin'/>
									<div class='formBouton'>
										<span class='centerBouton bouton cancelEdit'>Annuler</span>
										<input class='centerBouton' type='submit' value='Valider' />
									</div>
									<div id='editPassErrorBloc'></div>
								</form>
							</div>
							<div id='centerColSuivi'>
								<div class='formTitre'>Vous suivez actuellement :</div>
								<div class='centerColAffichage'>";
				$len = count($suivi);
				if ($len > 0) {
					for ($i=0; $i<$len; $i++) {
						$artiste = $suivi[$i];
						echo "		<a href='./compte.php?user=$artiste'>$artiste</a>";
					}
				} else
					echo "			<div class='centerColMessage'>Vous ne suivez aucun artiste</div>";
				echo "			</div>
							</div>";
			}
			echo " 		</div>
					</div>";
		} else
			echo "<div id='mainBloc'><div id='errorDisplay'>Page introuvable</div></div>";
	} else
		header('Location: ./index.php');
	
	
	// Affichage pied de page
    include_once('footer.php');
