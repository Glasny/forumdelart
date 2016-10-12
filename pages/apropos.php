<?php

	include('../traitement/connect.php');
	include('head.php');
	// css et js spécifiques à la page
	echo "	<link rel='stylesheet' type='text/css' href='../css/apropos.css' />";
    include('menuHaut.php');
    
    // Récupérer adresse page courante dans $_SESSION['loc']
	saveCurrentUrl();

	// Vérification formulaire sur compte.js
	echo "  <div id='mainBloc'>
				<div id='pageTitre'>Site Web - projet de NFA021 et NFA084</div>
				<div id='aproposBloc'>
					<div class='aproposLine'><span class='lineTitle'>Réalisation :</span>Thomas Swank</div>
					<div class='aproposLine'><span class='lineTitle'>Contact :</span>tomswank@gmail.com</div>
					<div class='aproposLine'><span class='lineTitle'>Technologies utilisées :</span>HTML, CSS, PHP, JavaScript (JQuery)</div>
					<div class='aproposLine'><span class='lineTitle'>Hébergement :</span>VPS chez OVH</div>
				</div>
			</div>";
			
			
	 include('footer.php');
					
