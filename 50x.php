<?php
    include_once('./traitement/connect.php');
    include_once('./pages/head.php');
    include_once('./pages/menuHaut.php');
    
    
  
    // Affichage bloc principal
    echo "  <div id='mainBloc'>
                <div id='errorDisplay'>
					Le serveur a rencontré une erreur interne.<br/>
					Veuillez contacter l'administrateur.
                </div>
            </div>";
            
    $connect -> close();

	// Affichage pied de page
    include_once('./pages/footer.php');



