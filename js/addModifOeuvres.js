$(document).ready(function() {
	/* Lors d'un import d'image par input file:
	 * 1. Vérification image
	 * 2. Récupération données de l'image
	 * 3. Redimension et import de l'image dans canvas #canvasImg (invisible)
	 * 4. Enregistrement des données converties : jpeg, maxW 1000 maxH 750 dans input hidden #srcImg$id
	 * 5. Redimension et import de l'image dans canvas #canvasMin$id (visible)
	 * 6. Enregistrement des données converties : jpeg, maxW 160 maxH 120 dans input hidden #srcMin$id
	 * 7. Lors de l'envoi, les données utilisées sur le serveur sont celles présentes dans #srcImg et #srcMin
	 *  => Taille envoi moindre et réduction de la charge sur le serveur */
	$('.uploadImg').change(function() {
		var id = $(this).attr('id');
		var img = $(this).get(0).files[0];
		if (checkImage(img)) {
			$(this).hide()
			saveImg(img,id);
			$('#canvasMin'+id).css('display', 'inline-block');
			$('#canvasMin'+id).parent().find('.cancelImg').css('display', 'inline-block');
		} else {
			$('#imgInfo').text('Type de fichier non valide');
			$(this).replaceWith($(this).val('').clone(true));
		}
	});
	
	// Retirer image : cache canvas et bouton annulation, régénère input file et le réaffiche, vide input #srcMin et #srcImg
	$('.cancelImg').click(function() {
		$(this).hide();
		$(this).parent().find('canvas').hide();
		var upload = $(this).parent().find('.uploadImg');
		upload.replaceWith(upload.val('').clone(true).show());
		var id = upload.prop('id');
		$('#srcMin'+id).val('');
		$('#srcImg'+id).val('');
	});
	
	/* Supprimer image déjà importée (modification d'oeuvre): 
	 * - Récupération #imgId et ajout dans #imgOld
	 * - Suppression balise img (miniature de l'image actuelle
	 * - Suppression bouton .suppImg : $(this)
	 * - Affichage input file .uploadImg */
	$('.suppImg').click(function() {
		var id = $(this).parent().find('.imgId').text();
		var old = $('#imgOld').val();
		if (old == '')
			$('#imgOld').val(id);
		else
			$('#imgOld').val(old+','+id);
		$(this).parent().find('img').remove();
		$(this).parent().find('.uploadImg').show();
		$(this).remove();
	});
});

// Confirmer type fichier image dans input file
function checkImage(file) {
	var extImages = ['jpg','png','gif','jpeg','tiff'];
	var ext = file.name.split(".");
	if ($.inArray(ext[ext.length-1].toLowerCase(), extImages) == -1)  // Vérif nom de l'extension
		return false;
	var type = file.type.split('/');  // Vérif type de fichier
	if (type.length != 2 || type[0] != "image" || $.inArray(type[1].toLowerCase(), extImages) == -1)
		return false;
	return true;
}

// Insertion image et miniature dans canvas correspondants
function saveImg (file, id) {
	var reader = new FileReader();
	var src = reader.readAsDataURL(file);
	reader.onload = function(src) {
		var imgData = src.target.result;
		img = new Image();
		img.src = imgData;
		img.onload = function() {
			// Dimensions max image et miniature
			var maxH = 750;
			var maxW = 1000;
			var minH = 120;
			var minW = 160;
			var maxR = maxW/maxH; // Ratio largeur/hauteur à comparer avec image
			// Dimensions img importée et ratio
			var imgW = img.width;
			var imgH = img.height;
			var imgR = imgW/imgH;
			// Import de l'image dans #imgCanvas
			// Redimension de l'image par la largeur
			if (imgR >= maxR && imgW > maxW) {
				var coef = maxW/imgW;
				img.width = maxW;
				img.height *= coef;
			 // Redimension de l'image par la hauteur
			}else if (imgR < maxR && imgH > maxH) {
				var coef = maxH/imgH;
				img.height = maxH;
				img.width *= coef;
			}
			var canvas = document.getElementById("canvasImg");
			// Redimension canvas au format de l'image
			canvas.height = img.height;
			canvas.width = img.width;
			// Affichage image taille normale dans #canvasImg
			renderImg(img, canvas);
			// Récupération dataURL du canvas #
			var imgData = canvas.toDataURL('image/jpeg', 0.9);
			$('#srcImg'+id).val(imgData);
			// Import de la miniature dans le canvas miniature correspondant
			// changement à taille miniature
			if (imgR >= maxR)
				var coef = minW/img.width;
			else
				var coef = minH/img.height;
			img.width *= coef;
			img.height *= coef;
			var canvas = document.getElementById("canvasMin"+id);
			// Redimensionner canvas miniature
			canvas.height = img.height;
			canvas.width = img.width;
			// Affichage miniature dans canvas
			renderImg(img, canvas);
			// Récupération dataURL du canvas miniature
			var imgData = canvas.toDataURL('image/jpeg', 1);
			$('#srcMin'+id).val(imgData);
		}
	}
}

// Dessiner image dans un canvas
function renderImg(img, canvas) {
	// Nettoyage du canvas, puis dessin de l'image
	var ctx = canvas.getContext("2d");
	ctx.clearRect(0, 0, canvas.width, canvas.height);
	ctx.save();
	// Ajout arrière plan blanc pour images avec transparence (.png)
	ctx.fillStyle = "#ffffff";
	ctx.fillRect(0,0, img.width,img.height);
	ctx.drawImage(img, 0, 0, img.width, img.height);
	ctx.restore();
}

// Validation formulaire ajouter oeuvre => Vérification présence img
function confirmAddOeuvre(form) {
	for (var i=0; i<3; i++) {
		if ($('#srcImg'+i).val() != '' && $('#srcMin'+i).val() != '')
			return true;
	}
	$('#imgInfo').text('Veuillez enregistrer au moins une image');
	return false;
}

// Validation formulaire modif oeuvre => Vérification présence img
function confirmModifOeuvre(form) {
	check = false;
	$('.uploadImg').each(function() {
		if ($(this).val() != '' || $(this).parent().find('img').length != 0) {
			check = true;
			return false;
		}
	});
	if (!check)
		$('#imgInfo').text('Veuillez enregistrer au moins une image');
	return check;
}
