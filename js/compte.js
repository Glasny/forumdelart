$(document).ready(function(){
	
	// Initialisation: au chargement, contenu ouvert est #leftColInfo
	var page = 'centerColInfo';
	
	// Affichage #centerColInfo
	$('#leftColInfo').click(function() {
		if (page != 'centerColInfo') {
			$('#'+page).hide(100, function() {
				$('#centerColInfo').show(100);
				page = 'centerColInfo';
			});
		}
	});
	
	// Affichage #centerColComment
	$('#leftColComment').click(function() {
		if (page != 'centerColComment') {
			$('#'+page).hide(100, function() {
				$('#centerColComment').show(100);
			});
			page = 'centerColComment';
		}
	});
	// Affichage #centerColSuivi
	$('#leftColSuivi').click(function() {
		if (page != 'centerColSuivi') {
			$('#'+page).hide(100, function() {
				$('#centerColSuivi').show(100);
			});
			page = 'centerColSuivi';
		}
	});
	
	// Affichage #centerColEditInfo
	$('#editInfo').click(function() {
		if (page != 'centerColEditInfo') {
			$('#'+page).hide(100, function() {
				$('#centerColEditInfo').show(100);
			});
			page = 'centerColEditInfo';
		}
	});
	
	// Affichage #centerColEditPass
	$('#editPass').click(function() {
		if (page != 'centerColEditPass') {
			$('#'+page).hide(100, function() {
				$('#centerColEditPass').show(100);
				$('#oldPass').focus();
			});
			page = 'centerColEditPass';
		}
	});
	// Annule édition (renvoi à #centerColInfo)
	$('.cancelEdit').click(function() {
		$('#'+page).hide(100, function() {
			$('#centerColInfo').show(100);
		});
		page = 'centerColInfo';
	});

});

	// Validation formulaire editPass
	function confirmModifPass(form) {
		$('#editPassErrorBloc').empty();
		var regex = /^[a-z0-9_]{4,20}$/i;
		var error = false;
		// Vérification old au bon format
		var pass = $('#oldPass').val();
		var new1 = $('#newPass1').val();
		var new2 = $('#newPass2').val();
		if (!regex.test(pass)) {
			$('#editPassErrorBloc').append($('<div>', {'class': 'editCompteError', text: 'Mot de passe actuel non valide'}));
			error = true;
		}
		// Vérification new1 au bon format
		if (!regex.test(new1)) {
			$('#editPassErrorBloc').append($('<div>', {'class': 'editCompteError', text: 'Nouveau mot de passe non valide: 4 à 20 caractères alphanumériques ou _'}));
			error = true;
		}
		// Vérification new1=new2
		if (new1 != new2) {
			$('#editPassErrorBloc').append($('<div>', {'class': 'editCompteError', text: 'Les champs nouveau mot de passe et confirmation doivent correspondre'}));
			error = true;
		}
		// Vérification new1!=pass
		if (new1 == pass) {
			$('#editPassErrorBloc').append($('<div>', {'class': 'editCompteError', text: 'Le nouveau mot de passe ne doit pas correpondre à l\'ancien'}));
			error = true;
		}
		if (error)
			return false;
	}
	
	// Validation formulaire editInfo
	function confirmModifInfo(form) {
		$('#editInfoErrorBloc').empty();
		var mailVerif = /^[a-z0-9_\.-]+@[a-z0-9_\.-]+\.[a-z]{2,6}$/i;
		var mail = $('#inputMail').val();
		if (!mailVerif.test(mail)) {
			$('#editInfoErrorBloc').append($('<div>', {'class': 'editCompteError', text: 'Format mail non valide'}));
			return false;
		}
		return true;
	}
	
	// Validation formulaire editInfo
	function confirmAddUser(form) {
		$('.editCompteError').remove();
		var mailReg = /^[a-z0-9_\.-]+@[a-z0-9_\.-]+\.[a-z]{2,6}$/i; // Vérif mail
		var loginPassReg = /^[a-z0-9_]{4,20}$/i; // Vérif login pass
		// Récupération informations
		var login = $('#formLogin').val();
		var pass1 = $('#pass1').val();
		var pass2 = $('#pass2').val();
		var mail = $('#mail').val();
		var check = true; // Vérif si tout est ok
		// Vérif mail présent et format
		if (!mailReg.test(mail)) {
			check = false;
			$('#mail').after($('<span>', {'class': 'editCompteError', text: 'Format mail non valide'}));
		}
		// Vérif format login, pass
		if (!loginPassReg.test(login)) {
			check = false;
			$('#formLogin').after($('<span>', {'class': 'editCompteError', text: '4 à 20 caractères alphanumériques ou _'}));
		}
		if (!loginPassReg.test(pass1)) {
			check = false;
			$('#pass1').after($('<span>', {'class': 'editCompteError', text: '4 à 20 caractères alphanumériques ou _'}));
		}
		// Vérif les deux pass correspondent
		if (pass1 != pass2) {
			$('#pass2').after($('<span>', {'class': 'editCompteError', text: 'Erreur confirmation'}));
			check = false;
		}
		if (!check)
			return false;
		// Vérification login disponible
		result = $.ajax({
			url: '../traitement/addUser.php',
			type: 'POST',
			async: false,
			data: 'login='+login+'&action=checkLogin',
			success: function(result) {
				if (result == 1)
					check = true;
				else {
					$('#formLogin').after($('<span>', {'class': 'editCompteError', text: 'Login non disponible'}));
					check = false;
				}
					
			},
			error: function() {
				$('#creerCompteBloc').append($('<div>', {'class': 'editCompteError', text: 'Erreur de traitement'}));
				check = false;
			}
		});
		return check;
	}
