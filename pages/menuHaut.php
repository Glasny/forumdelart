<?php
	$url = "http://$_SERVER[HTTP_HOST]";
	// Affichage menu du haut
	echo "</head>
        <body>
			<div id='menuBackgroundLeft'></div>
			<div id='menuBackgroundRight'></div>
			<div id='topMenu'>
				<a id='menuTitre' href='$url/pages/index.php'></a>";
    // Affichage compte/page connection/déconnection
    if ($privilege < 3)
		echo "  <a id='toCompteUser' href='$url/pages/compte.php?user=$login'>Bienvenue, <span style='font-weight:bold;'>$login</span></a>
				<a id='logout' href='$url/traitement/logInOut.php?action=logOut'>Se déconnecter</a>";
    else {
		// affichage #connectBloc et vérification login/pass sur login.js
		echo "  <div id='seConnecter'>
					Se connecter
				</div>
				<a id='creerCompte' href='$url/pages/creerCompte.php'>Créer un compte</a>";
	}
    // Affichage menu horizontal (en fonction du niveau de droit)
    echo "      <div id='menuList'>
					<a href='$url/pages/index.php'>Accueil</a>
					<a href='$url/pages/listeOeuvres.php'>Oeuvres</a>
					<a href='$url/pages/artistes.php'>Artistes</a>";
    if ($privilege == 1)
        echo "      <a href='$url/pages/gererOeuvres.php'>Vos Oeuvres</a>";
    if ($privilege == 0)
        echo "      <a href='$url/pages/gererUser.php'>Gestion Utilisateurs</a>";
    echo "      	<a href='$url/pages/apropos.php'>À Propos du Site</a>
				</div>";
	// Formulaire de connection
	if ($privilege == 3) {
		echo "<div id='connectBloc'";
		// Si renvoi d'erreur login/pass affiche la fenêtre et affiche une erreur
		if (array_key_exists('erreur', $_GET) && $_GET['erreur'] == 'login')
			echo " class='forceAffichage'>
					<div id='loginError'>Login / Mot de passe non valide</div>";
		else
			echo ">";
		echo "	<form action='$url/traitement/logInOut.php' method='POST'>
					<div><span class='etiquette'>Votre login : </span><input type='text' name='login' id='login' class='connectInput' required ";
		// Si renvoi d'erreur login/passa autofocus sur login
		if (array_key_exists('erreur', $_GET) && $_GET['erreur'] == 'login')
			echo "autofocus ";
		echo "/></div> 	
					<div><span class='etiquette'>Mot de passe : </span><input type='password' name='pass' id='pass' class='connectInput' required /></div>
					<input type='hidden' name='action' value='logIn' />
					<div><span id='annulerLogin' class='bouton'>Annuler</span><input type='submit' value='Valider' /></div>
				</form>
			</div>";
	 }
	 echo " </div>";
	
	echo "	<div id='msgBloc'>";
	// Affichage messages d'erreur ou confirmation
	if (array_key_exists('erreur', $_GET) && preg_match('/^[0-9]{1,3}$/', $_GET['erreur'])) {
		$erreur = $_GET['erreur'];
		if ($erreur == '200')
			echo "<span id='msgErreur'>Votre session a expiré. Veuillez vous reconnecter</span>";
		elseif ($erreur == '201')
			echo "<span id='msgErreur'>Erreur d'identification</span>";
		else
			echo "<span id='msgErreur'>Erreur dans le traitement de la requête: N° ".$_GET['erreur'].". Veuillez prévenir l'administrateur<br/></span>";
	} if (array_key_exists('message', $_GET)) {
		if ($_GET['message'] == '1')
			echo "<span id='msgConfirm'>Modification effectuée</span>";
		elseif ($_GET['message'] == '2')
			echo "<span id='msgConfirm'>Votre compte est crée</span>";
		elseif ($_GET['message'] == '3')
			echo "<span id='msgConfirm'>Oeuvre ajoutée</span>";
		elseif ($_GET['message'] == '4')
			echo "<span id='msgConfirm'>Note enregistrée</span>";
		elseif ($_GET['message'] == '5')
			echo "<span id='msgConfirm'>Commentaire enregistré</span>";
		elseif ($_GET['message'] == '6')
			echo "<span id='msgConfirm'>Commentaire supprimé</span>";
		elseif ($_GET['message'] == '7')
			echo "<span id='msgConfirm'>Oeuvre modifiée</span>";
		elseif ($_GET['message'] == '8')
			echo "<span id='msgConfirm'>Oeuvre supprimée</span>";
		elseif ($_GET['message'] == '9')
			echo "<span id='msgConfirm'>Privilège modifié</span>";
		elseif ($_GET['message'] == '10')
			echo "<span id='msgConfirm'>Utilisateur supprimé</span>";
	}
	echo "	</div>";
