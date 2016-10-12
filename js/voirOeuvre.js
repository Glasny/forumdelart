$(document).ready(function() {
	
	active = 0; // Initialisation pointeur image active
	
	// Place loupe en bas à droite de l'image courante dans #mainWindow
	$('#mainWindow>img:eq(0)').load(function() {
		repositionnerLoupe();
		$('#loupe').show();
	});
	
	// Affichage #mainWindow à partir de #listeMiniatures
	$('#listeMiniatures').on('click', 'img', function() {
		var pos = $(this).index();
		if (pos != active) {
			// Affichage de l'image correspondante dans #mainWindow
			var loc = $('#mainWindow');
			loc.find('img:eq('+active+')').hide(200, function() {
				loc.find('img:eq('+pos+')').show(200, function() {
					// Repositionner loupe (si changement format image)
					repositionnerLoupe();
				});
				active = pos;
			});
		}
	});
	
	// Positionner loupe en bas à droite de l'image
	function repositionnerLoupe() {
		$('#mainWindow').find('img').each(function() {
			// Cherche image active
			if ($(this).css('display') != 'none') {
				// Taille image + offset #mainwindow car centrage horizontal et vertical
				var top = $(this).height()+$(this).position().top-40;
				var left = $(this).width()+$(this).position().left-40;
				$('#loupe').css('top',top);
				$('#loupe').css('left',left);
				return false;
			}
		});
	}
	
	active = 0;

	// Si plus d'une image, affiche boutons de navigation vue plein écran
	if ($('#listeMiniatures').length > 0) {
		// Affichage boutons voir img gauche et droite
		$('#voirGauche').show();
		$('#voirDroite').show();
		// Initialisation handler voir img gauche
		$('#voirGauche').click(function() {
			// Ferme image courante
			$('#blocVuePleinEcran').find('img:eq('+active+')').hide();
			// Récupère index nouvelle image active
			active --;
			if (active < 0)
				active = $('#blocVuePleinEcran').find('img').length -1;
			// Affichage nouvelle image
			$('#blocVuePleinEcran').find('img:eq('+active+')').show();
		});
		// Initialisation handler voir img droite
		$('#voirDroite').click(function() {
			// Ferme image courante
			$('#blocVuePleinEcran').find('img:eq('+active+')').hide();
			// Récupère index nouvelle image active
			active ++;
			if (active > $('#blocVuePleinEcran').find('img').length -1)
				active = 0;
			// Affichage nouvelle image
			$('#blocVuePleinEcran').find('img:eq('+active+')').show();
		});
	}
	
	
	// Affichage #vuePleinEcran
	$('#mainWindow').on('click', 'img', function() {
		// Affiche image active
		$('#blocVuePleinEcran').find('img:eq('+active+')').show();
		// Ajout handler fermeture avec touche échap
		$(window).on('keyup.pleinEcran', (function(e) {
			if (e.keyCode == 27)
				fermerPleinEcran();
		}));
		// Affichage vue plein écran
		 $('#vuePleinEcran').show();
	});
	
	// Fermeture image sur clic #fermerPleinEcran
	$('#fermerPleinEcran').click(function() {
		fermerPleinEcran();
	});
	
	// Fermeture #vuePleinEcran
	function fermerPleinEcran() {
		$('#vuePleinEcran').hide(); // Cache #vuePleinEcran
		$(window).off('keyup.pleinEcran'); // Désactive handler appui sur échap
		$('#blocVuePleinEcran').find('img:eq('+active+')').hide(); // Cache l'image active
		// Replaçage du pointeur sur img affichée dans #mainWindow
		$('#mainWindow').find('img').each(function(index) {
			if ($(this).css('display') != 'none') {
				active = index;
				return false;
			}
		});
	}
	
	
	// Affichage édition de commentaire
	$('.editComment').click(function() {
		var loc = $(this).parents('.commentBloc');
		loc.find('.commentContenu').hide();
		loc.find('.editForm').css('display', 'inline');
		loc.find('.cancelEdit').show();
	});
	// Annuler édition
	$('.cancelEdit').click(function() {
		var loc = $(this).parents('.commentBloc');
		var comment = loc.find('.commentContenu');
		var text = comment.text().replace(/<br><\/br>/, "\n");
		var form = loc.find('.editForm');
		form.find('textarea').val(text);
		comment.show();
		form.hide();
		$(this).hide();
	});
});


	// Confirmer suppression du commentaire
	function confirmSupp(form) { 
		if (confirm('Veuillez confirmer la suppression : '))
			return true;
		else
			return false;
	}
