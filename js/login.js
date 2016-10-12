$(document).ready(function(){
	
	// Vérification que l'utilisateur accepte les cookies
	if (!navigator.cookieEnabled) {
		var text = "<img src='../images/attention.svg' alt=' '/>Pour pouvoir profiter de toutes les fonctionnalités de ce site, veuillez autoriser les cookies dans les paramètres de votre navigateur.";
		$('#topMenu').after($('<div>', {id: 'cookieError', 'html': text}));
	}
	
	// Affichage bloc connection
	$("#seConnecter").click(function() {
		var etat = $('#connectBloc').css('display');
		if (etat == 'none') {
			$("#connectBloc").show(200);
			$('#login').val('').focus();
			$('#pass').val('');
		} else
			fermerLogin();
	});
	$('#annulerLogin').click(function() {
		fermerLogin();
	});
	
	
	// Disparition progressive des messages de confirmation et erreur
	var loc = $("#msgBloc");
	if (loc.find('span').length > 0) {
		setTimeout(function() {
			loc.hide(400);
		}, 3000);
	}
});

function fermerLogin() {
	var loc = $('#connectBloc');
	if (loc.prop('class') == 'forceAffichage')
		loc.prop('class', '');
	$("#connectBloc").hide(200);
}
	
