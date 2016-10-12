<?php
    include_once('./traitement/connect.php');
    include_once('./pages/head.php');
    include_once('./pages/menuHaut.php');
    
    
  
    // Affichage bloc principal
    echo "  <div id='mainBloc'>
                <div id='errorDisplay'>
					La page recherchée n'existe pas
                </div>
            </div>";
            
    $connect -> close();

	// Affichage pied de page
    include_once('./pages/footer.php');



