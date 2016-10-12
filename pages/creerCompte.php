<?php
	
	include('../traitement/connect.php');
	// Si utilisateur connecté => redirection
	if ($login != '') {
		header("Location: ./index.php");
		exit;
	}
	include('head.php');
	// css et js spécifiques à la page
	echo "	<link rel='stylesheet' type='text/css' href='../css/compte.css' />
			<script src='../js/compte.js'></script>";
    include('menuHaut.php');
	
	$page = $_SESSION['loc'];
	
	// Vérification formulaire sur compte.js
	echo "  <div id='mainBloc'>
				<div id='pageTitre'>Créer un compte</div>
				<div id='creerCompteBloc'>
					<form method='POST' action='../traitement/addUser.php' onsubmit='return confirmAddUser(this);'>
						<div class='formLine'><label for='login'>Login :</label><input type='text' name='login' id='formLogin' required placeholder='Choisissez votre login' autofocus /></div>
						<div class='formLine'><label for='pass1'>Mot de passe :</label><input type='password' name='pass1' id='pass1' required /></div>
						<div class='formLine'><label for='pass2'>Confirmer :</label><input type='password' name='pass2' id='pass2' required /></div>
						<div class='formLine'><label for='nom'>Nom :</label><input type='text' name='nom' id='nom'/></div>
						<div class='formLine'><label for='prenom'>Prenom :</label><input type='text' name='prenom' id='prenom'/></div>
						<div class='formLine'><label for='mail'>Mail :</label><input type='text' name='mail' id='mail' required /></div>
						<div class='formLine'><label for='informations' class='textareaLabel'>À propos de vous :</label><textarea name='informations' id='informations'></textarea></div>
						<input type='hidden' name='action' value='creerUser' />
						<div class='formBouton'>
							<a class='centerBouton bouton' href='$page'>Annuler</a>
							<input class='centerBouton' type='submit' value='Valider' />
						</div>
						<div id='creerCompteErrorBloc'></div>
					</form>
				</div>
			</div>";
			
			
	 include('footer.php');
					
